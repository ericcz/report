delimiter //
DROP PROCEDURE IF EXISTS cspcustomer_get//
create procedure cspcustomer_get(
out chRet int) 
label_pro:BEGIN
	set chRet=0;
	if not exists(select table_name from information_schema.tables where table_name='customer') then
		leave label_pro;
	end if;
	set @a=(select count(id) from customer where subscribeDate>=current_date());
	set chRet=@a;
END//

DROP PROCEDURE IF EXISTS csphighchart_get//
create procedure csphighchart_get(
v_dt varchar(20),
out chRet varchar(1000)) 
label_pro:BEGIN
	set chRet='';
	if not exists(select table_name from information_schema.tables where table_name='customer') then
		leave label_pro;
	end if;
	drop temporary table if exists tmpdt;
	create temporary table tmpdt(dt date);
	set @x=-10;
	while @x<0 DO
		insert into tmpdt	values(date_add(v_dt,interval @x day));
		set @x=@x+1;
	END while;
	set @a='';
	set @b='';
	select z.dt,ifnull(a.ct,0) as ct,@a:=concat(@a,ifnull(a.ct,0),','),@b:=concat(@b,date_format(z.dt,'%m-%d'),',') from tmpdt z left join (
	select date(activeDate) as dt,count(id) as ct from customer where activeDate>date_add(v_dt,INTERVAL -10 day)
	group by date(activeDate)) a on z.dt=a.dt;
	set @a=left(@a,length(@a)-1);
	set @b=left(@b,length(@b)-1);
	set chRet=concat(chRet,@b,'##','Daily Active,',@a);	-- 日期+日活

	set @a='';
	set @b='';
	select z.dt,ifnull(a.ct,0) as new,ifnull(b.ct,0) as old,@a:=concat(@a,ifnull(a.ct,0),','),@b:=concat(@b,ifnull(b.ct,0),',')
	from tmpdt z left join 
	(select date(activeDate) as dt,count(id) as ct from customer where activeDate>date_add(v_dt,INTERVAL -10 day)
	and date(activeDate)=date(subscribeDate)
		group by date(activeDate)) a on z.dt=a.dt left join
	(select date(activeDate) as dt,count(id) as ct from customer where activeDate>date_add(v_dt,INTERVAL -10 day)
	and date(activeDate)<>date(subscribeDate)
		group by date(activeDate)) b on z.dt=b.dt;
	set @a=left(@a,length(@a)-1);
	set @b=left(@b,length(@b)-1);
	set chRet=concat(chRet,'##','NewSign,',@a,';PastSign,',@b);	-- 日新老用户比

	set @a='';
	set @b='';
	select @a:=concat(@a,a.h,',') as tim,@b:=concat(@b,a.ct,',') as ct from (
	select hour(activeDate) as h,count(id) as ct from customer where activeDate between v_dt and date_add(v_dt,interval 1 day)
	group by hour(activeDate)) a;
	set @a=left(@a,length(@a)-1);
	set @b=left(@b,length(@b)-1);
	select concat(chRet,'##',@a,';','Time Active,',@b) from dual;
	set chRet=concat(chRet,'##',@a,';','Time Active,',@b);	-- 日时段活跃	
END//

drop table if exists ctbReportStore;
CREATE TABLE ctbReportStore (
  uid int(10) unsigned NOT NULL AUTO_INCREMENT,
  iType tinyint(4) NOT NULL COMMENT '1:reg;2:pv;3:uv;4:leftD;5:leftW;6:leftM',
  dt date NOT NULL,
  val decimal(7,2) DEFAULT '0',
  source varchar(50) DEFAULT NULL,
  PRIMARY KEY (uid)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
delimiter //
DROP PROCEDURE IF EXISTS cspReportStore_daily//
create procedure cspReportStore_daily(
v_dt date)
label_pro:BEGIN
if not exists(select table_name from information_schema.tables where table_name='ctbReportStore') then
	leave label_pro;
end if;
/*
update daysdata set channelid=7 where channelid in(8,9,10,14,15,16);
update daysdata set channelid=5 where channelid=11;
-- register
if not exists(select uid from ctbReportStore where iType=1 and dt=date_add(v_dt,interval -1 day)) then
insert into ctbReportStore(iType,dt,val,source)
select 1,a.dt,count(a.id),ifnull(b.channelId,'其它渠道') from
(select id, date(subscribeDate) as dt from wershare.customer 
where subscribeDate between date_add(v_dt,interval -1 day)	and v_dt
and enable=1 and mobile is not null) a left JOIN
(select distinct channelid,userid from usercenter.uc_device where userid in(select id from wershare.customer 
where subscribeDate between date_add(v_dt,interval -1 day)	and v_dt
and enable=1 and mobile is not null)
) b on a.id=b.userid 
group by b.channelId order by a.dt,b.channelId;
end if;
-- pv
if not exists(select uid from ctbReportStore where iType=2 and dt=date_add(v_dt,interval -1 day)) then
insert into ctbReportStore(iType,dt,val,source)
select 2,date_sub(v_dt,interval 1 day),ifnull(sum(number),0),type from flows 
where datetime between date_sub(v_dt,interval 1 day) and date_sub(v_dt,interval 1 day)
group by type;
end if;
-- uv
if not exists(select uid from ctbReportStore where iType=3 and dt=date_add(v_dt,interval -1 day)) then
insert into ctbReportStore(iType,dt,val,source)
select 3,date_sub(v_dt,interval 1 day),x.c1+y.c2,x.type from (
select a.type,ifnull(sum(a.c),0) as c1 from
(select count(number) as c,type from flows where mobile='0' 
and datetime=date_sub(v_dt,interval 1 day) group by type,name) a
group by a.type) x left join (
select z.type,ifnull(count(z.mobile),0) as c2
from (
select type,mobile from flows 
where datetime=date_sub(current_date(),interval 1 day)  and mobile<>'0'
group by mobile
) z group by z.type) y on x.type=y.type;
end if;
-- leftD
set @i=2;
while @i<9 DO
set @dt=date_sub(v_dt,interval @i day);
if not exists(select uid from ctbReportStore where iType=4 and dt=@dt and source=@i-1) then
set @ds1=0,@ds2=0;
set @ds1=(select count(x.id) from wershare.customer x
	where x.subscribeDate between @dt and date_add(@dt,interval 1 day) and enable=1 and mobile is not null
	and exists(select id from usercenter.uc_device where createDate between date_sub(v_dt,interval 1 day) and v_dt and userId=x.id));
set @ds2=(select count(x.id) from wershare.customer x
	where x.subscribeDate between @dt and date_add(@dt,interval 1 day) and enable=1 and mobile is not null);
insert into ctbReportStore(iType,dt,source,val)
select 4,@dt,@i-1,round(@ds1/@ds2*100,2);
end if;
set @i=@i+1;
end while;
-- point
if not exists(select uid from ctbReportStore where iType=7 and dt=date_add(v_dt,interval -1 day)) then
set @dt=date_add(v_dt,interval -1 day);
insert into ctbReportStore(iType,dt,val,source)
	select 7,@dt,count(id) as val,'27' as source from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 1 and 1000
	UNION
	select 7,@dt,count(id),'28' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 1001 and 3000
	UNION
	select 7,@dt,count(id),'29' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 3001 and 5000
	UNION
	select 7,@dt,count(id),'30' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 5001 and 10000
	UNION
	select 7,@dt,count(id),'31' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 10001 and 15000
	UNION
	select 7,@dt,count(id),'32' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore > 15000;
end if;

-- leftW
set @i=2;
while @i<9 DO
set @dt=date_sub(date_sub(v_dt,interval @i week),interval dayofweek(v_dt)-2 day);
if not exists(select uid from ctbReportStore where iType=5 and dt=@dt and source=@i-1) then
set @ds1=0,@ds2=0;
set @ds1=(select count(x.id) from wershare.customer x
	where yearweek(x.subscribeDate,1) =yearweek(@dt,1) and enable=1 and mobile is not null
	and exists(select id from usercenter.uc_device where yearweek(createDate,1)=yearweek(v_dt,1)-1 and userId=x.id));
set @ds2=(select count(x.id) from wershare.customer x
	where yearweek(x.subscribeDate,1) =yearweek(@dt,1) and enable=1 and mobile is not null);
insert into ctbReportStore(iType,dt,source,val)
select 5,@dt,@i-1,round(@ds1/@ds2*100,2);
end if;
set @i=@i+1;
end while;
-- leftM
set @i=2;
while @i<7 DO
set @dt=date_sub(date_sub(v_dt,interval @i month),interval day(v_dt)-1 day);
if not exists(select uid from ctbReportStore where iType=6 and dt=@dt and source=@i-1) then
set @ds1=0,@ds2=0;
set @ds1=(select count(x.id) from wershare.customer x
	where extract(year_month from x.subscribeDate)=extract(year_month from @dt) and enable=1 and mobile is not null
	and exists(select id from usercenter.uc_device where extract(year_month from createDate)=extract(year_month from v_dt)-1 and userId=x.id));
set @ds2=(select count(x.id) from wershare.customer x
	where extract(year_month from x.subscribeDate)=extract(year_month from @dt) and enable=1 and mobile is not null);
insert into ctbReportStore(iType,dt,source,val)
select 6,@dt,@i-1,round(@ds1/@ds2*100,2);
end if;
set @i=@i+1;
end while;
*/
END //
delimiter ;

delimiter //
DROP PROCEDURE IF EXISTS cspReportStore_weekly//
create procedure cspReportStore_weekly(
v_dt date)
label_pro:BEGIN
if not exists(select table_name from information_schema.tables where table_name='ctbReportStore') then
	leave label_pro;
end if;
-- leftW
set @i=2;
while @i<9 DO
set @dt=date_sub(date_sub(v_dt,interval @i week),interval dayofweek(v_dt)-2 day);
if not exists(select uid from ctbReportStore where iType=5 and dt=@dt and source=@i-1) then
set @ds1=0,@ds2=0;
set @ds1=(select count(x.id) from wershare.customer x
	where yearweek(x.subscribeDate,1) =yearweek(@dt,1) and enable=1 and mobile is not null
	and exists(select id from usercenter.uc_device where yearweek(createDate,1)=yearweek(v_dt,1)-1 and userId=x.id));
set @ds2=(select count(x.id) from wershare.customer x
	where yearweek(x.subscribeDate,1) =yearweek(@dt,1) and enable=1 and mobile is not null);
insert into ctbReportStore(iType,dt,source,val)
select 5,@dt,@i-1,round(@ds1/@ds2*100,2);
end if;
set @i=@i+1;
end while;
-- leftM
set @i=2;
while @i<7 DO
set @dt=date_sub(date_sub(v_dt,interval @i month),interval day(v_dt)-1 day);
if not exists(select uid from ctbReportStore where iType=6 and dt=@dt and source=@i-1) then
set @ds1=0,@ds2=0;
set @ds1=(select count(x.id) from wershare.customer x
	where extract(year_month from x.subscribeDate)=extract(year_month from @dt) and enable=1 and mobile is not null
	and exists(select id from usercenter.uc_device where extract(year_month from createDate)=extract(year_month from v_dt)-1 and userId=x.id));
set @ds2=(select count(x.id) from wershare.customer x
	where extract(year_month from x.subscribeDate)=extract(year_month from @dt) and enable=1 and mobile is not null);
insert into ctbReportStore(iType,dt,source,val)
select 6,@dt,@i-1,round(@ds1/@ds2*100,2);
end if;
set @i=@i+1;
end while;
END //
delimiter ;

SHOW VARIABLES LIKE 'event_scheduler';
SET GLOBAL event_scheduler = 1; 
drop event if exists evtest;
CREATE EVENT IF NOT EXISTS evtest
ON SCHEDULE EVERY 1 week
ON COMPLETION PRESERVE
DO CALL cspReportStore_weekly(curdate());

delimiter //
drop procedure if exists cspDashboard_get //
CREATE PROCEDURE cspDashboard_get(
out chRet varchar(5000))
label_pro:BEGIN
	set chRet='';
	set @ds1='',@ds2='',@ds3='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',')
	from (select date_format(date_add(curdate(),interval -2 day),'%m-%d') as dt,ifnull(sum(number),0) as ds
		from daysdata where status='down' and datetime=date_add(curdate(),interval -2 day) and channelid<>13
		UNION
		select date_format(date_add(curdate(),interval -1 day),'%m-%d') as dt,ifnull(sum(number),0) as ds
		from daysdata where status='down' and datetime=date_add(curdate(),interval -1 day) and channelid<>13
		) z;
	set @ds3=(select sum(number) from daysdata where status='down' and channelid<>13);
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'download','$$',@ds1,'$$',@ds2,'$$',@ds3,'##');	-- download

	set @ds1='',@ds2='',@ds3='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.id,',') 
	from (select date_format(date(a.dt),'%m-%d') as dt,count(a.id) as id from
	(select id, date(subscribeDate) as dt from wershare.customer where subscribedate between date_sub(curdate(),interval 7 day) and date_add(curdate(),interval 1 day)
	and enable=1 and mobile is not null ) a left join
	(select distinct channelid,userid from usercenter.uc_device where userid in(select id from wershare.customer 
	where subscribedate between date_sub(curdate(),interval 7 day) and date_add(curdate(),interval 1 day) and enable=1 and mobile is not null)) b on a.id=b.userid
	group by a.dt) z;
	set @ds3=(select count(id) from wershare.customer where enable=1 and mobile is not null);
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'register','$$',@ds1,'$$',@ds2,'$$',@ds3,'##');	-- register

	set @ds1='',@ds2='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',')
	from (select date_format(date_add(curdate(),interval -2 day),'%m-%d') as dt,ifnull(sum(number),0) as ds
		from daysdata where status='lively' and datetime=date_add(curdate(),interval -2 day) and channelid<>13
		UNION
		select date_format(date_add(curdate(),interval -1 day),'%m-%d') as dt,ifnull(sum(number),0) as ds
		from daysdata where status='lively' and datetime=date_add(curdate(),interval -1 day) and channelid<>13) z;
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'live','$$',@ds1,'$$',@ds2,'##');	-- live customer

	set @ds1='';
		set @ds1=ifnull((select val from ctbReportStore where iType=4 and dt=date_sub(current_date(),interval 2 day) limit 1),0);
	set chRet=concat(chRet,'leftDaily','$$',@ds1,'##');	

	set @ds1='';
		set @ds1=ifnull((select val from ctbReportStore where iType=5 and yearweek(dt)=yearweek(curdate())-2 and source=1 limit 1),0);
	set chRet=concat(chRet,'leftWeekly','$$',@ds1,'##');	

	set @ds1='';
		set @ds1=ifnull((select val from ctbReportStore where iType=6 and extract(year_month from dt)=extract(year_month from curdate())-2 and source=1 limit 1),0);
	set chRet=concat(chRet,'leftMonth','$$',@ds1,'##');

	set @ds1='',@ds2='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',') from
		(select date_format(date_sub(curdate(),interval 2 day),'%m-%d') as dt,ifnull(sum(number),0) as ds
		from flows where datetime between date_sub(curdate(),interval 2 day) and date_sub(current_date(),interval 2 day)
		UNION
		select date_format(date_sub(curdate(),interval 1 day),'%m-%d'),ifnull(sum(number),0)
		from flows where datetime between date_sub(curdate(),interval 1 day) and date_sub(curdate(),interval 0 day)) z;
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'pv','$$',@ds1,'$$',@ds2,'##');

	set @ds1='',@ds2='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',')
	from (select x.dt,x.c+y.c as ds from
		(select date(datetime) as dt,ifnull(count(distinct mobile),0) as c from flows 
		where datetime>=date_sub(current_date(),interval 2 day)  and mobile<>'0'
		group by date(datetime)) x inner join (
		select a.dt,ifnull(sum(a.c),0) as c from
		(select date(datetime) as dt,count(number) as c from flows where mobile='0' and datetime>=date_sub(current_date(),interval 2 day) group by date(datetime),name) a
		group by a.dt) y on x.dt=y.dt) z;
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	if @ds2='' THEN
		set @ds2='0,0';
	elseif @ds2='0' THEN
		set @ds2=concat(@ds2,',0');
	end if;
	set chRet=concat(chRet,'uv','$$',@ds1,'$$',@ds2,'##');
	
	set @ds1='';
	select @ds1:=concat(@ds1,'<li class="news-item">',y.city,' 日活：',y.ac,'</li>') from (
	select city,sum(active) as ac from location 
	where datetime between date_sub(current_date(),interval 1 day) 
	and date_sub(current_date(),interval 1 day) group by city) y;
	set chRet=concat(chRet,'loc','$$',@ds1,'##');
	select chRet;
	
	set @ds1='',@ds2='';
	select @ds1:=ifnull(sum(pv),0),@ds2:=ifnull(sum(uv),0)
	from tdevents 
	where datetime between date_sub(curdate(),interval 2 day) and date_sub(curdate(),interval 1 day)
	 and url like 'http://m.weshare12.com/newevents%' and file in('turntable.html','recruit_two.html');
	set chRet=concat(chRet,'td','$$',@ds1,'$$',@ds2,'##');
	select chRet;
	
END //
delimiter ;

delimiter //
DROP PROCEDURE IF EXISTS cspDashboard_detail//
create procedure cspDashboard_detail(
v_type varchar(20),	-- down;lively;reg;leftD;leftW;leftM;pv;uv
v_dt varchar(10))
label_pro:BEGIN
if not exists(select table_name from information_schema.tables where table_name='daysdata') then
	leave label_pro;
end if;
if v_type in('down','lively') then
	select date_format(z.dt,'%m-%d'),z.ds,substr(d.name,1,locate('_',concat(d.name,'_'))-1) as name
	from (select date(datetime) as dt,sum(number) as ds,channelid
	from daysdata
	where status=v_type and datetime between date_add(v_dt,interval -6 day) and v_dt and channelid<>13
	group by date(datetime),channelid) z inner join channel d on z.channelid=d.id
	order by d.name,z.dt;
elseif v_type ='reg' then
	select date_format(a.dt,'%m-%d'),count(a.id) as val,ifnull(b.channelId,'其它渠道') as source from
	(select id, date(subscribeDate) as dt from wershare.customer 
	where subscribeDate between date_sub(v_dt,interval 6 day) and date_add(v_dt,interval 1 day)
	and enable=1 and mobile is not null
	) a left JOIN
	(select distinct substr(channelid,1,locate('_',concat(channelid,'_'))-1) as channelid,userid from usercenter.uc_device where userid in(select id from wershare.customer 
	where subscribeDate between date_sub(v_dt,interval 6 day) and date_add(v_dt,interval 1 day)
	and enable=1 and mobile is not null)
	) b on a.id=b.userid
	group by a.dt,b.channelId order by source,a.dt;
elseif v_type ='pv' then
	select date_format(dt,'%m-%d'),val,case source when 'dujia' then '度假' when 'gushi' then '故事' when 'shop' then '商城'  when 'taowu' then '淘屋' else '' end as source from ctbReportStore 
	where iType=2 and dt between date_add(v_dt,interval -6 day) and v_dt
	order by source,dt;
elseif v_type ='uv' then
	select date_format(dt,'%m-%d'),val,case source when 'dujia' then '度假' when 'gushi' then '故事' when 'shop' then '商城'  when 'taowu' then '淘屋' else '' end as source from ctbReportStore 
	where iType=3 and dt between date_add(v_dt,interval -6 day) and v_dt
	order by source,dt;
elseif v_type ='leftD' then
	select source,concat(val,'%'),date_format(dt,'%m-%d') as dtv from ctbReportStore
	where iType=4 and dt between date_sub(v_dt,interval 7 day) and v_dt order by dt,source;
elseif v_type ='leftW' then
	select source,concat(val,'%'),concat(date_format(dt,'%m-%d'),'~',date_format(date_add(dt,interval 6 day),'%m-%d')) as dt	 
	from ctbReportStore where iType=5 and dt between date_sub(v_dt,interval 56 day) and v_dt
	order by dt,source;
elseif v_type ='leftM' then
	select source,concat(val,'%'),dt from ctbReportStore 
	where iType=6 and dt between date_sub(v_dt,interval 240 day) and v_dt 
	order by dt,source;
elseif v_type ='loc' then
	select date_format(datetime,'%m-%d'),sum(active) as active,city from location 
	where datetime between date_add(v_dt,interval -6 day) and v_dt group by city,datetime;
elseif v_type ='launch' then
	select date_format(a.dt,'%m-%d'),a.launch,a.type from (
	select sum(launch) as launch,date(datetime) as dt,type from location 
	where datetime between date_sub(v_dt,interval 6 day) and v_dt group by date(datetime),type) a
	order by a.type,a.dt;
elseif v_type ='point' then
	select date_format(date(a.dt),'%m-%d'),a.val,b.name from ctbReportStore a inner join channel b on a.source =b.id where a.iType=7 and a.dt between date_add(v_dt,interval -6 day) and v_dt
	order by a.source,a.dt;
elseif v_type ='time' then
	select date_format(date(createDate),'%m-%d'),count(id),concat(hour(createDate),'-',hour(createDate)+1)
	from usercenter.uc_device where createDate between date_add(v_dt,interval -6 day) and date_add(v_dt,interval 1 day)
	and userid not in(select id from wershare.customer where enable=0)
	group by hour(createDate),date(createDate);
elseif v_type ='interval' then
	select date_format(a.dt,'%m-%d'),a.ds,b.name,b.avgtime from
	(select channelid,date(datetime) as dt,sum(number) as ds from daysdata where status in('ios-ontime','an-ontime') and datetime between date_sub(v_dt,interval 6 day) and v_dt
	group by datetime,channelid) a inner join channel b on a.channelid=b.id
	order by a.channelid,a.dt;
elseif v_type ='wheel' then
	select date_format(operate_time,'%m-%d'),count(distinct (customer_id)) from weshare_activity.draw_result
	where operate_time between date_add(curdate(),interval -10 day) and date_add(curdate(),interval 1 day)
	group by date(operate_time);
elseif v_type ='sp-pv' then
	select date_format(datetime,'%m-%d') as dt,pv,case file when 'recruit_two.html' then 'recruit' when 'turntable.html' then '大转盘' else '其它' end as source
	from tdevents 
	where datetime between date_sub(v_dt,interval 6 day) and v_dt
	 and url like 'http://m.weshare12.com/newevents%'
	order by source,dt;
elseif v_type ='sp-uv' then
	select date_format(datetime,'%m-%d') as dt,uv,case file when 'recruit_two.html' then 'recruit' when 'turntable.html' then '大转盘' end as source
	from tdevents 
	where datetime between date_sub(v_dt,interval 6 day) and v_dt
	 and url like 'http://m.weshare12.com/newevents%'
	order by source,dt;
elseif v_type ='area' then
	select count(id) as val from wershare.customer 
	where province is not null and province<>'' and city<>'' and enable=1;
end if;
END//
delimiter ;

delimiter //
DROP PROCEDURE IF EXISTS cspDashboard_funnel//
create procedure cspDashboard_funnel(
v_sid tinyint,
v_dt varchar(10))
label_pro:BEGIN
if not exists(select table_name from information_schema.tables where table_name='funnel') then
	leave label_pro;
end if;
set @a=v_sid;
set @b=@a+9;
select step,name,sum(count) as count from funnel 
where fid in(@a,@b) and yearweek(datetime)=yearweek(curdate())
group by step order by step;
END//
delimiter ;
===============================================================================================
delimiter //
DROP PROCEDURE IF EXISTS cspDashboard_dataV//
create procedure cspDashboard_dataV(
v_type varchar(20)	-- regT;regA;down;voc;life;tw;pv;map;
)
label_pro:BEGIN
if not exists(select table_name from information_schema.tables where table_name='daysdata') then
	leave label_pro;
end if;
set @ret='';
if v_type='twAmount' THEN
	set @a='',@b='';
	select sum(pay_money) into @a from weshare_order.orders
	where status='alreadySuccess' and order_type<>'gift';
	set @ret:=concat('[{"value":',@a,'}]');
elseif v_type='regT' THEN
	set @a='',@b='';
	select count(id) into @a from wershare.customer
	where enable=1 and mobile is not null and subscribeDate>=curdate();
	set @ret:=concat('[{"regToday":',@a,'}]');
elseif v_type='regA' THEN
	set @a='',@b='';
	set @a='400000';
	select count(id) into @b from wershare.customer where enable=1 and mobile is not null;
	set @ret:=concat('[{"aims":',@a,',"actual":',@b,'}]');
elseif v_type='down' THEN
	set @a='',@b='';
	select @a:=concat(@a,'{"x":"',substr(b.name,1,locate('_',concat(b.name,'_'))-1),'","y":',a.num,'},') from
	(select datetime,channelid,sum(number) as num from daysdata 
	where status='down' and datetime>=date_sub(curdate(),interval 1 day) and number>0
	group by channelid) a inner join channel b on a.channelid=b.id;
	set @a=left(@a,char_length(@a)-1);
	set @ret:=concat('[',@a,']');
elseif v_type='downA' THEN
	set @a='';
	select sum(number) into @a from daysdata where status='down';
	set @ret:=concat('[{"value":',@a,'}]');
elseif v_type='liveness' THEN
	set @a='',@b='';
	set @a='6000';
	select sum(number) into @b from daysdata where status='lively' and datetime>=date_sub(curdate(),interval 1 day);
	set @ret:=concat('[{"aims":',@a,',"actual":',@b,'}]');
elseif v_type='voc' THEN
	set @a='',@b='';
	select @a:=concat(@a,'{"热门度假项目":"',a.name,'","数量":',a.num,'},') from (
	select name,sum(number) as num from flows where datetime>=date_sub(curdate(),interval 1 day) and type='dujia'
	group by name order by num DESC limit 20) a;
	set @a=left(@a,char_length(@a)-1);
	set @ret:=concat('[',@a,']');
elseif v_type='tw' THEN
	set @a='',@b='';
	select @a:=concat(@a,'{"热门淘屋":"',a.name,'","数量":',a.num,'},') from (
	select name,sum(number) as num from flows where datetime>=date_sub(curdate(),interval 1 day) and type='taowu'
	group by name order by num DESC limit 20) a;
	set @a=left(@a,char_length(@a)-1);
	set @ret:=concat('[',@a,']');
elseif v_type='pv' THEN
	set @a='',@b='';
	select sum(val) into @a from ctbreportstore where itype=2 and dt>=date_sub(curdate(),interval 1 day);
	set @ret:=concat('[{"value":',@a,'}]');
elseif v_type='uv' THEN
	set @a='',@b='';
	select sum(val) into @a from ctbreportstore where itype=3 and dt>=date_sub(curdate(),interval 1 day);
	set @ret:=concat('[{"value":',@a,'}]');
elseif v_type='map' THEN
	set @a='',@b='';
	select @a:=concat(@a,'{"adcode":"',b.code,'","value":"',a.ac,'"},')
	from (select city,sum(active)/100 as ac from location where datetime>=date_sub(curdate(),interval 1 day) 
	group by city) a inner join ctbAdcode b on a.city=b.name;
	set @a=left(@a,length(@a)-1);
	set @ret:=concat('[',@a,']');
elseif v_type='map2' THEN
	set @a='',@b='';
	select @a:=concat(@a,'{"lng":"',substr(a.geo,1,locate(',',a.geo)-1),'","lat":"',substr(a.geo,locate(',',a.geo)+1),'","value":',0.1,'},') from
	(select distinct geographicalPosition as geo from usercenter.uc_device 
	where userid in(select id from wershare.customer where subscribeDate>=date_sub(now(),interval 10 minute)) ) a
	where a.geo<>'' and a.geo<>'4.9E-324,4.9E-324';
	set @a=left(@a,length(@a)-1);
	set @ret:=concat('[',@a,']');
end if;
	select @ret;
END//
delimiter ;
http://elkonline.weshare12.net/dataVGet.php?typ=regT
=============================================================================================== login
drop table if exists ctbUser;
create table ctbUser(
iUid int UNSIGNED PRIMARY KEY AUTO_INCREMENT,
iStatus tinyint default 1,
dtInsert timestamp DEFAULT CURRENT_TIMESTAMP,
chInsertby char(6),
dtUpdate timestamp ON UPDATE CURRENT_TIMESTAMP,
chUpdateby char(6),
chUserNo varchar(20) not null,
chUserCN varchar(50) not null,
iDept tinyint comment '1:admin;2:supervisor;11:ICT',
chUdf1 varchar(100),
chUdf2 varchar(100),
chUdf3 varchar(100),
chUdf4 varchar(100),
chUdf5 varchar(100),
constraint uk_ctbUser unique(chUserNo)
)ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8;

drop table if exists ctbPWD;
create table ctbPWD(
iUid int UNSIGNED PRIMARY KEY AUTO_INCREMENT,
dtInsert timestamp DEFAULT CURRENT_TIMESTAMP,
chInsertby char(6),
dtUpdate timestamp ON UPDATE CURRENT_TIMESTAMP,
chUpdateby char(6),
iUser int,
chPwd char(32),
encrypt char(4),
constraint uk_ctbPWD unique(iUser)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

drop table if exists ctbLogs;
create table ctbLogs(
iUid int UNSIGNED PRIMARY KEY AUTO_INCREMENT,
dtInsert timestamp DEFAULT CURRENT_TIMESTAMP,
chOperator varchar(100) not null,
iSuccess tinyint not null,
chTag varchar(20),
chStep varchar(20),
chInfo varchar(100),
chUdf1 varchar(100),
chUdf2 varchar(100),
chUdf3 varchar(100),
chUdf4 varchar(100),
chUdf5 varchar(100)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

delimiter //
drop procedure IF EXISTS cspUser_mod//
create procedure cspUser_mod(
v_iUid varchar(10),
v_Unit varchar(10),
v_chUnit varchar(50),
v_iDept int,
v_pwd varchar(50),
v_user int,
out iRet int) -- 0:false/1:success/2:exist userNo/3:no found
label_pro:BEGIN
	set iRet=0;
	if (select count(table_name) from information_schema.tables where table_name='ctbUser' or table_name='ctbPWD')<2 then
		leave label_pro;
	end if;
	if exists(select iUid from ctbUser where iUid<>v_iUid and chUserNo=v_Unit) then
		set iRet=2;
		leave label_pro;
	end if;
	if (not exists(select iUid from ctbUser where iUid=v_iUid and iStatus=1) and v_iUid<>'') then
		set iRet=3;
		leave label_pro;
	end if;
	if v_iUid='' then
		set @i=0;
		set @v='';
		while @i<4 do
			set @v=concat(@v,(select char( rand()*25+97 )));
			set @i:=@i+1;
		end while;
		insert into ctbUser(iStatus,chInsertby,chUpdateby,chUserNo,chUserCN,iDept,
							chUdf1,chUdf2,chUdf3,chUdf4,chUdf5)
		values(1,v_user,v_user,v_Unit,v_chUnit,v_iDept,null,null,null,null,null);
		if (v_pwd<>'') then
			insert into ctbPWD(iUser,chInsertby,chPwd,encrypt)
			values(LAST_INSERT_ID(),v_user,md5(concat(md5(v_pwd),@v)),@v);
		end if;
	else
		if v_Unit<>'' then
			update ctbUser set chUpdateby=v_user,chUserNo=v_Unit,chUserCN=v_chUnit,iDept=v_iDept
			where iUid=v_iUid;
		end if;
		if v_pwd<>'' then
			update ctbPWD set chUpdateby=v_user,chPwd=md5(concat(md5(v_pwd),encrypt)) where iUser=v_iUid;
		end if;
	end if;
	set iRet=1;
END//
delimiter ;

delimiter //
DROP PROCEDURE IF EXISTS cspCheckLogin//
create procedure cspCheckLogin(
v_user varchar(10),
v_chpwd varchar(20),
out iRet varchar(50)) -- 0:failure/UserID,UserName,DeptID
label_pro:BEGIN
	set iRet='0';
	if not exists(select table_name from information_schema.tables where table_name='ctbPWD') then
		leave label_pro;
	end if;
	if v_user='' or v_chpwd='' then
		leave label_pro;
	end if;
	if exists (select iUid from ctbPWD where iUser in(select iUid from ctbUser where iStatus=1 and chUserNo=v_user) and chPwd=md5(concat(md5(v_chpwd),encrypt))) then
		set iRet=(select concat(iUid,'#',chUserNo,'#',chUserCN) from ctbUser where chUserNo=v_user);
	end if;
END//
delimiter ;
delimiter //
DROP PROCEDURE IF EXISTS cspLogs_ins//
create procedure cspLogs_ins(
v_sc tinyint,
v_step varchar(20),
v_info varchar(100),
v_user varchar(50),
out iRet tinyint) -- 0:failure/1:true
label_pro:BEGIN
	set iRet=0;
	if not exists(select table_name from information_schema.tables where table_name='ctbLogs') then
		leave label_pro;
	end if;
	insert into ctbLogs(chOperator,iSuccess,chStep,chInfo)
	values(v_user,v_sc,v_step,v_info);
	set iRet=1;
END//
delimiter ;

call cspUser_mod('','10002','用户2','0','weeshare',0,@x);
call cspCheckLogin('10001','weeshare',@x);
select @x;


========================================================================================================
drop table if exists ctbReportUMAPI;
create table ctbReportUMAPI(
uid int UNSIGNED PRIMARY KEY AUTO_INCREMENT,
dtInsert timestamp DEFAULT CURRENT_TIMESTAMP,
iType tinyint not null comment '1:new;2:act;3:launch;4:duration;5:pv;6:uv;7:regist;8:leftD;9:leftW;10:leftM;11:point;12:funnel',
dtDate date not null,
chVal int not null,
chsys tinyint comment '1:ios;2:android',
chUdf1 varchar(100),
chUdf2 varchar(100),
chUdf3 varchar(100),
chUdf4 varchar(100),
chUdf5 varchar(100),
chUdf6 varchar(100),
chUdf7 varchar(100),
chUdf8 varchar(100)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

drop table if exists ctbReportRef;
create table ctbReportRef(
uid int UNSIGNED PRIMARY KEY AUTO_INCREMENT,
cname varchar(50),
chdesc varchar(50),
umengid varchar(32),
udf1 varchar(100),
udf2 varchar(100),
udf3 varchar(100)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
insert into ctbReportRef(cname,chdesc,umengid)
values('App Store','channel','571449cae0f55a9404000277'),
('ZongHe_01','channel','576ddb97e0f55a0578000442'),
('Yingyongbao_android','channel','571845c9e0f55a0ca300328c'),
('official','channel','5714c2b467e58e62340006cc'),
('dingbin_03','channel','57577375e0f55a0e33007076'),
('Xiaomi_yingyong_android','channel','5714c2b467e58e62340006cb'),
('dingbin_02','channel','57577375e0f55a0e33007075'),
('dingbin_07','channel','575b6824e0f55a872e0004b6'),
('dingbin_04','channel','57577375e0f55a0e33007077'),
('Baidu_mobile_android','channel','5714c2b467e58e62340006ce'),
('m360_mobile_android','channel','5714c2b467e58e62340006cd'),
('dingbin_06','channel','575a17d067e58e5af302d102'),
('dingbin_05','channel','575a17d067e58e5af302d101'),
('Wandoujia_android','channel','57160368e0f55ae7d30028a7'),
('ZongHe_02','channel','576ddb97e0f55a0578000443'),
('dingbin_01','channel','5747a536e0f55a326d000911'),
('maikerui','channel','5789e43467e58e6e570027c7'),
('Huazhu_weixin_android','channel','');
insert into ctbReportRef(cname,chdesc,udf1)
values('1-3秒','duration','2'),('3-10秒','duration','7'),('10-30秒','duration','20.5'),('30-60秒','duration','45.5'),('1-3分钟','duration','120'),('3-10分钟','duration','390'),('10-30分钟','duration','1200'),('30分钟以上','duration','1800');
insert into ctbReportRef(uid,cname,chdesc)
select id,name,'point' from channel where id between 27 and 32 order by id;


set global log_bin_trust_function_creators=1;
set global binlog_format=MIXED
delimiter //
DROP function IF EXISTS cfnRefConv//
create function cfnRefConv(val varchar(50))
returns TINYINT
BEGIN
	set @x:=(select uid from ctbReportRef where cname=val limit 1);
	return @x;
end//
delimiter ;

delimiter //
DROP PROCEDURE IF EXISTS cspReportStore_daily_c//
create procedure cspReportStore_daily_c(
out iRet int) 
label_pro:BEGIN
set iRet=0;
set @dt=date_sub(curdate(),interval 1 day);
if not exists(select table_name from information_schema.tables where table_name='ctbReportStore') then
	leave label_pro;
end if;
update ctbReportUMAPI set chUdf1='576ddb97e0f55a0578000442' where chUdf1='576ddb97e0f55a0578000443';
update ctbReportUMAPI set chUdf1='57577375e0f55a0e33007076' where chUdf1 in (
'57577375e0f55a0e33007075','575b6824e0f55a872e0004b6','57577375e0f55a0e33007077',
'575a17d067e58e5af302d102','575a17d067e58e5af302d101','5747a536e0f55a326d000911');
--	Down
if not exists(select uid from ctbReportStore where iType=1 and dt=@dt) then
insert into ctbReportStore(iType,dt,val,source)
select a.iType,a.dtDate,sum(a.chVal) as chVal,substr(b.cname,1,locate('_',concat(b.cname,'_'))-1)
from ctbReportUMAPI a inner join ctbReportRef b on a.chUdf1=b.umengid
where a.iType=1 and a.dtDate>=@dt group by a.iType,b.cname,a.dtDate;
end if;
--	active
if not exists(select uid from ctbReportStore where iType=2 and dt=@dt) then
insert into ctbReportStore(iType,dt,val,source)
select a.iType,a.dtDate,sum(a.chVal) as chVal,substr(b.cname,1,locate('_',concat(b.cname,'_'))-1)
from ctbReportUMAPI a inner join ctbReportRef b on a.chUdf1=b.umengid
where a.iType=2 and a.dtDate>=@dt group by a.iType,b.cname,a.dtDate;
end if;
-- launch
if not exists(select uid from ctbReportStore where iType=3 and dt=@dt) then
insert into ctbReportStore(iType,dt,val,source)
select a.iType,a.dtDate,sum(a.chVal) as chVal,substr(b.cname,1,locate('_',concat(b.cname,'_'))-1)
from ctbReportUMAPI a inner join ctbReportRef b on a.chUdf1=b.umengid
where a.iType=3 and a.dtDate>=date_sub(@dt,interval 1 day) group by a.iType,b.cname,a.dtDate;
end if;
-- duration
if not exists(select uid from ctbReportStore where iType=4 and dt=@dt) then
insert into ctbReportStore(iType,dt,val,source,extend)
select a.iType,a.dtdate,a.chval,a.chudf1,b.cname
from ctbReportUMAPI a inner join ctbReportRef b on a.chudf4=b.umengid
where a.iType=4 and a.dtDate>=date_sub(@dt,interval 1 day);
end if;
-- pv
if not exists(select uid from ctbReportStore where iType=5 and dt=@dt) then
insert into ctbReportStore(iType,dt,val,source)
select iType,dtDate,sum(chVal) as chVal,substr(chUdf1,1,4)
from ctbReportUMAPI
where iType=5 and dtDate>=@dt
group by substr(chUdf1,1,4);
end if;
-- uv
if not exists(select uid from ctbReportStore where iType=6 and dt=@dt) then
insert into ctbReportStore(iType,dt,val,source)
select 6,@dt,x.c+y.c,x.type from
(select count(distinct(chudf2)) as c,substr(chUdf1,1,4) as type
from ctbReportUMAPI
where iType=5 and chUdf2<>''
group by substr(chUdf1,1,4)
) x inner join (select ifnull(sum(a.c),0) as c,a.type from
(select count(uid) as c,substr(chUdf1,1,4) as type
from ctbReportUMAPI where iType=5 and chUdf2='' group by substr(chUdf1,1,4),chUdf3
) a group by a.type) y on x.type=y.type;
end if;
-- register
if not exists(select uid from ctbReportStore where iType=7 and dt=@dt) then
insert into ctbReportStore(iType,dt,val,source)
select 7,a.dt,count(a.id),ifnull(b.channelId,'其它渠道') from
(select id, date(subscribeDate) as dt from wershare.customer 
where subscribeDate between @dt	and date_add(@dt,interval 1 day)
and enable=1 and mobile is not null) a left JOIN
(select distinct channelid,userid from usercenter.uc_device where userid in(select id from wershare.customer 
where subscribeDate between @dt	and date_add(@dt,interval 1 day)
and enable=1 and mobile is not null)
) b on a.id=b.userid 
group by b.channelId order by a.dt,b.channelId;
end if;
-- leftD
set @i=1;
while @i<9 DO
set @d=date_sub(@dt,interval @i day);
if not exists(select uid from ctbReportStore where iType=8 and dt=@d and source=@i) then
set @ds1=0,@ds2=0;
set @ds1=(select count(x.id) from wershare.customer x
	where x.subscribeDate between @d and date_add(@d,interval 1 day) and enable=1 and mobile is not null
	and exists(select id from usercenter.uc_device where createDate between @dt and date_add(@dt,interval 1 day) and userId=x.id));
set @ds2=(select count(x.id) from wershare.customer x
	where x.subscribeDate between @d and date_add(@d,interval 1 day) and enable=1 and mobile is not null);
insert into ctbReportStore(iType,dt,source,val)
select 8,@d,@i,round(@ds1/@ds2*100,2);
end if;
set @i=@i+1;
end while;
-- leftW
set @i=2;
while @i<8 DO
set @d=date_sub(date_sub(@dt,interval @i week),interval dayofweek(@dt)-2 day);
if not exists(select uid from ctbReportStore where iType=9 and dt=@d and source=@i-1) then
set @ds1=0,@ds2=0;
set @ds1=(select count(x.id) from wershare.customer x
	where yearweek(x.subscribeDate,1) =yearweek(@d,1) and enable=1 and mobile is not null
	and exists(select id from usercenter.uc_device where yearweek(createDate,1)=yearweek(@dt,1) and userId=x.id));
set @ds2=(select count(x.id) from wershare.customer x
	where yearweek(x.subscribeDate,1) =yearweek(@d,1) and enable=1 and mobile is not null);
insert into ctbReportStore(iType,dt,source,val)
select 9,@d,@i-1,round(@ds1/@ds2*100,2);
end if;
set @i=@i+1;
end while;
-- leftM
set @i=2;
while @i<8 DO
set @d=date_sub(date_sub(@dt,interval @i month),interval day(@dt)-1 day);
if not exists(select uid from ctbReportStore where iType=10 and dt=@d and source=@i-1) then
set @ds1=0,@ds2=0;
set @ds1=(select count(x.id) from wershare.customer x
	where extract(year_month from x.subscribeDate)=extract(year_month from @d) and enable=1 and mobile is not null
	and exists(select id from usercenter.uc_device where extract(year_month from createDate)=extract(year_month from @dt)-1 and userId=x.id));
set @ds2=(select count(x.id) from wershare.customer x
	where extract(year_month from x.subscribeDate)=extract(year_month from @d) and enable=1 and mobile is not null);
insert into ctbReportStore(iType,dt,source,val)
select 10,@d,@i-1,round(@ds1/@ds2*100,2);
end if;
set @i=@i+1;
end while;
-- point
if not exists(select uid from ctbReportStore where iType=11 and dt=date_add(@dt,interval 1 day)) then
set @dt=date_add(@dt,interval 1 day);
insert into ctbReportStore(iType,dt,val,source)
	select 11,@dt,count(id) as val,'27' as source from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 1 and 1000
	UNION
	select 11,@dt,count(id),'28' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 1001 and 3000
	UNION
	select 11,@dt,count(id),'29' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 3001 and 5000
	UNION
	select 11,@dt,count(id),'30' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 5001 and 10000
	UNION
	select 11,@dt,count(id),'31' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore between 10001 and 15000
	UNION
	select 11,@dt,count(id),'32' from wershare.point
	where id not in(select id from wershare.customer where enable=0)
	and totalScore > 15000;
end if;
set iRet=1;
end//
delimiter ;

delimiter //
drop procedure if exists cspDashboard_get//
CREATE PROCEDURE cspDashboard_get(
out chRet varchar(5000))
label_pro:BEGIN
	set chRet='';
	set @ds1='',@ds2='',@ds3='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',')
	from (select date_format(date_add(curdate(),interval -2 day),'%m-%d') as dt,ifnull(round(sum(val)),0) as ds
		from ctbReportStore where iType=1 and dt=date_add(curdate(),interval -2 day)
		UNION
		select date_format(date_add(curdate(),interval -1 day),'%m-%d') as dt,ifnull(round(sum(val)),0) as ds
		from ctbReportStore where iType=1 and dt=date_add(curdate(),interval -1 day)
		) z;
	set @ds3=(select round(sum(val)) from ctbReportStore where iType=1);
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'download','$$',@ds1,'$$',@ds2,'$$',@ds3,'##');	-- download

	set @ds1='',@ds2='',@ds3='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.id,',') 
	from (select date_format(date(a.dt),'%m-%d') as dt,count(a.id) as id from
	(select id, date(subscribeDate) as dt from wershare.customer where subscribedate between date_sub(curdate(),interval 7 day) and date_add(curdate(),interval 1 day)
	and enable=1 and mobile is not null ) a left join
	(select distinct channelid,userid from usercenter.uc_device where userid in(select id from wershare.customer 
	where subscribedate between date_sub(curdate(),interval 7 day) and date_add(curdate(),interval 1 day) and enable=1 and mobile is not null)) b on a.id=b.userid
	group by a.dt) z;
	set @ds3=(select count(id) from wershare.customer where enable=1 and mobile is not null);
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'register','$$',@ds1,'$$',@ds2,'$$',@ds3,'##');	-- register

	set @ds1='',@ds2='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',')
	from (select date_format(date_add(curdate(),interval -2 day),'%m-%d') as dt,ifnull(round(sum(val)),0) as ds
		from ctbReportStore where iType=2 and dt=date_add(curdate(),interval -2 day)
		UNION
		select date_format(date_add(curdate(),interval -1 day),'%m-%d') as dt,ifnull(round(sum(val)),0) as ds
		from ctbReportStore where iType=2 and dt=date_add(curdate(),interval -1 day)) z;
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'live','$$',@ds1,'$$',@ds2,'##');	-- live customer

	set @ds1='';
		set @ds1=ifnull((select val from ctbReportStore where iType=8 and dt=date_sub(current_date(),interval 2 day) limit 1),0);
	set chRet=concat(chRet,'leftDaily','$$',@ds1,'##');	

	set @ds1='';
		set @ds1=ifnull((select val from ctbReportStore where iType=9 and yearweek(dt)=yearweek(curdate())-2 and source=1 limit 1),0);
	set chRet=concat(chRet,'leftWeekly','$$',@ds1,'##');	

	set @ds1='';
		set @ds1=ifnull((select val from ctbReportStore where iType=10 and extract(year_month from dt)=extract(year_month from curdate())-2 and source=1 limit 1),0);
	set chRet=concat(chRet,'leftMonth','$$',@ds1,'##');

	set @ds1='',@ds2='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',') from
		(select date_format(date_sub(curdate(),interval 2 day),'%m-%d') as dt,ifnull(round(sum(val)),0) as ds
		from ctbReportStore where iType=5 and dt between date_sub(curdate(),interval 2 day) and date_sub(curdate(),interval 2 day)
		UNION
		select date_format(date_sub(curdate(),interval 1 day),'%m-%d'),ifnull(round(sum(val)),0)
		from ctbReportStore where iType=5 and dt between date_sub(curdate(),interval 1 day) and date_sub(curdate(),interval 0 day)) z;
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'pv','$$',@ds1,'$$',@ds2,'##');

	set @ds1='',@ds2='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',') from
		(select date_format(date_sub(curdate(),interval 2 day),'%m-%d') as dt,ifnull(round(sum(val)),0) as ds
		from ctbReportStore where iType=6 and dt between date_sub(curdate(),interval 2 day) and date_sub(curdate(),interval 2 day)
		UNION
		select date_format(date_sub(curdate(),interval 1 day),'%m-%d'),ifnull(round(sum(val)),0)
		from ctbReportStore where iType=6 and dt between date_sub(curdate(),interval 1 day) and date_sub(curdate(),interval 0 day)) z;
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'uv','$$',@ds1,'$$',@ds2,'##');
	
	set @ds1='';
	select @ds1:=concat(@ds1,'<li class="news-item">',y.city,' 日活：',y.ac,'</li>') from (
	select city,sum(active) as ac from location 
	where datetime between date_sub(current_date(),interval 1 day) 
	and date_sub(current_date(),interval 1 day) group by city) y;
	set chRet=concat(chRet,'loc','$$',@ds1,'##');
	
	set @ds1='',@ds2='';
	select @ds1:=ifnull(sum(pv),0),@ds2:=ifnull(sum(uv),0)
	from tdevents 
	where datetime between date_sub(curdate(),interval 2 day) and date_sub(curdate(),interval 1 day)
	 and url like 'http://m.weshare12.com/newevents%' and file in('turntable.html','recruit_two.html');
	set chRet=concat(chRet,'td','$$',@ds1,'$$',@ds2,'##');
	
	set @ds1='',@ds2='';
	select @ds1:=concat(@ds1,z.dt,','),@ds2:=concat(@ds2,z.ds,',')
	from (select date_format(date_add(curdate(),interval -2 day),'%m-%d') as dt,ifnull(round(sum(val)),0) as ds
	from ctbReportStore where iType=3 and dt=date_add(curdate(),interval -2 day)
	UNION
	select date_format(date_add(curdate(),interval -1 day),'%m-%d') as dt,ifnull(round(sum(val)),0) as ds
	from ctbReportStore where iType=3 and dt=date_add(curdate(),interval -1 day)) z;
	set @ds1=left(@ds1,length(@ds1)-1);
	set @ds2=left(@ds2,length(@ds2)-1);
	set chRet=concat(chRet,'lau','$$',@ds1,'$$',@ds2,'##');	-- launch count

	set @ds1='',@ds2='';
	select @ds1:=count(a.user_name) from exchange.ebk_debit_card a where a.id in(46,48,39,42);
	set chRet=concat(chRet,'debit$$',@ds1,'##');	-- debit

	set @ds1='',@ds2='';
	select @ds1:=count(a.mobile) from wershare.customer a where a.enterpriseRightMenber=1 and a.name is null;
	set chRet=concat(chRet,'member$$',@ds1,'##');	-- member

select chRet;
END //
delimiter ;

delimiter //
DROP PROCEDURE IF EXISTS cspDashboard_detail//
create procedure cspDashboard_detail(
v_type varchar(20),
v_dt varchar(10))
label_pro:BEGIN
if not exists(select table_name from information_schema.tables where table_name='ctbReportStore') then
	leave label_pro;
end if;
if v_type ='down' then
	select date_format(dt,'%m-%d'),val,source
	from ctbReportStore
	where iType=1 and dt between date_add(v_dt,interval -6 day) and v_dt 
	order by source,dt;
elseif v_type ='lively' then
	select date_format(dt,'%m-%d'),val,source
	from ctbReportStore
	where iType=2 and dt between date_add(v_dt,interval -6 day) and v_dt 
	order by source,dt;
elseif v_type ='lau' then
	select date_format(dt,'%m-%d'),val,source
	from ctbReportStore
	where iType=3 and dt between date_add(v_dt,interval -6 day) and v_dt 
	order by source,dt;
elseif v_type ='reg' then
	select date_format(a.dt,'%m-%d'),count(a.id) as val,ifnull(b.channelId,'其它渠道') as source from
	(select id, date(subscribeDate) as dt from wershare.customer 
	where subscribeDate between date_sub(v_dt,interval 6 day) and date_add(v_dt,interval 1 day)
	and enable=1 and mobile is not null
	) a left JOIN
	(select distinct substr(channelid,1,locate('_',concat(channelid,'_'))-1) as channelid,userid from usercenter.uc_device where userid in(select id from wershare.customer 
	where subscribeDate between date_sub(v_dt,interval 6 day) and date_add(v_dt,interval 1 day)
	and enable=1 and mobile is not null)
	) b on a.id=b.userid
	group by a.dt,b.channelId order by source,a.dt;
elseif v_type ='pv' then
	select date_format(dt,'%m-%d'),val,case source when 'holi' then '度假' when 'stor' then '故事' when 'shop' then '商城'  when 'taow' then '淘屋' else '' end as source from ctbReportStore 
	where iType=5 and dt between date_add(v_dt,interval -6 day) and v_dt
	order by source,dt;
elseif v_type ='uv' then
	select date_format(dt,'%m-%d'),val,case source when 'holi' then '度假' when 'stor' then '故事' when 'shop' then '商城'  when 'taow' then '淘屋' else '' end as source from ctbReportStore 
	where iType=6 and dt between date_add(v_dt,interval -6 day) and v_dt
	order by source,dt;
elseif v_type ='leftD' then
	select source,concat(val,'%'),date_format(dt,'%m-%d') as dtv from ctbReportStore
	where iType=8 and dt between date_sub(v_dt,interval 7 day) and v_dt order by dt,source;
elseif v_type ='leftW' then
	select source,concat(val,'%'),concat(date_format(dt,'%m-%d'),'~',date_format(date_add(dt,interval 6 day),'%m-%d')) as dt	 
	from ctbReportStore where iType=9 and dt between date_sub(v_dt,interval 56 day) and v_dt
	order by dt,source;
elseif v_type ='leftM' then
	select source,concat(val,'%'),dt from ctbReportStore 
	where iType=10 and dt between date_sub(v_dt,interval 240 day) and v_dt 
	order by dt,source;
elseif v_type ='loc' then
	select date_format(datetime,'%m-%d'),sum(active) as active,city from location 
	where datetime between date_add(v_dt,interval -6 day) and v_dt group by city,datetime;
elseif v_type ='launch' then
	select date_format(a.dt,'%m-%d'),a.launch,a.type from (
	select sum(launch) as launch,date(datetime) as dt,type from location 
	where datetime between date_sub(v_dt,interval 6 day) and v_dt group by date(datetime),type) a
	order by a.type,a.dt;
elseif v_type ='point' then
	select date_format(date(a.dt),'%m-%d') as dt,a.val,b.cname 
	from ctbReportStore a inner join ctbReportRef b on a.source =b.uid 
	where a.iType=11 and a.dt between date_add(v_dt,interval -6 day) and v_dt
	order by a.source,a.dt;
elseif v_type ='time' then
	select date_format(date(createDate),'%m-%d'),count(id),concat(hour(createDate),'-',hour(createDate)+1)
	from usercenter.uc_device where createDate between date_add(v_dt,interval -6 day) and date_add(v_dt,interval 1 day)
	and userid not in(select id from wershare.customer where enable=0)
	group by hour(createDate),date(createDate);
elseif v_type ='interval' then
	select date_format(a.dt,'%m-%d') as dt,a.val,a.source,b.udf1
	from (select date(dt) as dt,sum(val) as val,source from ctbReportStore where iType=4 and dt>=curdate()-6
	group by dt,source) a inner join ctbReportRef b on a.source=b.cname
	order by b.uid,dt;
elseif v_type ='interval-c' then
	select a.source,a.val,a.extend
	from ctbReportStore a inner join ctbReportRef b on a.source=b.cname
	where a.iType=4 and a.dt=v_dt order by a.extend,b.uid;
elseif v_type ='wheel' then
	select date_format(operate_time,'%m-%d'),count(distinct (customer_id)) from weshare_activity.draw_result
	where operate_time between date_add(curdate(),interval -10 day) and date_add(curdate(),interval 1 day)
	group by date(operate_time);
elseif v_type ='sp-pv' then
	select date_format(datetime,'%m-%d') as dt,pv,case file when 'recruit_two.html' then 'recruit' when 'turntable.html' then '大转盘' else '其它' end as source
	from tdevents 
	where datetime between date_sub(v_dt,interval 6 day) and v_dt
	 and url like 'http://m.weshare12.com/newevents%'
	order by source,dt;
elseif v_type ='sp-uv' then
	select date_format(datetime,'%m-%d') as dt,uv,case file when 'recruit_two.html' then 'recruit' when 'turntable.html' then '大转盘' end as source
	from tdevents 
	where datetime between date_sub(v_dt,interval 6 day) and v_dt
	 and url like 'http://m.weshare12.com/newevents%'
	order by source,dt;
elseif v_type ='area' then
	select count(id) as val from wershare.customer 
	where province is not null and province<>'' and city<>'' and enable=1;
elseif v_type ='debit' then
	select a.effective_start_date,a.user_name,b.hotel_name,a.sale_price,a.additional_price,a.balance,
	case when ebk_user_id is not null then '微信激活用户' else 'weshare注册用户' end as eu
	from exchange.ebk_debit_card a inner join exchange.ebk_hotel_info b on a.hotel_code=b.hotel_code
	where a.id in(46,48,39,42) order by a.effective_start_date desc;
elseif v_type ='member' then
	select d.create_time,concat(left(d.business_phone,3),'*****',right(d.business_phone,3)) as phone,
		d.business_name,e.hotel_name,round(e.sale_day_num/7),c.amount-- a.mobile,b.start_time,b.currency_amount
	from wershare.customer a inner join users.exchange_currency_detail b on a.id=b.customer_id 
	inner join users.exchange_currency c on a.id=c.customer_id
	inner join exchange.ebk_business_code d on a.id=d.customer_id
	inner join exchange.ebk_hotel_info e on d.hotel_code=e.hotel_code
	where a.enterpriseRightMenber=1 and a.name is null and b.currency_resource='platformgift';
	
end if;
END//
delimiter ;


select 'new',a.dtDate,b.cname,a.chVal,a.chsys
from ctbReportUMAPI a inner join ctbReportRef b on a.chUdf1=b.umengid where a.iType=1 and a.dtDate=curdate()-1;
select 'active',a.dtDate,b.cname,a.chVal,a.chsys
from ctbReportUMAPI a inner join ctbReportRef b on a.chUdf1=b.umengid where a.iType=2 and a.dtDate=curdate()-1;
select 'launch',a.dtDate,b.cname,a.chVal,a.chsys
from ctbReportUMAPI a inner join ctbReportRef b on a.chUdf1=b.umengid where a.iType=3 and a.dtDate=curdate()-1;
select 'duration',a.dtDate,b.cname,a.chVal,a.chsys,a.chUdf1
from ctbReportUMAPI a inner join ctbReportRef b on a.chUdf4=b.umengid inner join ctbReportRef c on a.chUdf1=c.cname
where a.iType=4 and a.dtDate=curdate()-1
order by b.uid,c.uid;
select 'pv',dtDate,chUdf1,sum(chval) as val
from ctbReportUMAPI
where iType=5 and dtDate=curdate()-1
group by dtDate,chUdf1;
select 'uv',date(curdate()-1) as dt,x.chUdf1,a.val+b.val from
(select distinct(chUdf1) as chUdf1 from ctbReportUMAPI where iType=5 and dtDate=curdate()-1) x left join 
(select dtDate,chUdf1,count(chval) as val
from ctbReportUMAPI
where iType=5 and dtDate=curdate()-1 and chudf2<>''
group by dtDate,chUdf1) a on x.chUdf1=a.chUdf1 left join 
(select 'uv',dtDate,chUdf1,sum(chval) as val
from ctbReportUMAPI
where iType=5 and dtDate=curdate()-1 and chudf2=''
group by dtDate,chUdf1) b on x.chUdf1=b.chUdf1;

dashboard v1.0 beta
	上线日期：2016-08-09
dashboard v2.0
	上线日期：2016-09-06
	更新日志：1.数据块扁平化设计；2.色调调整；3.布局调整；4.数据优化
dashboard v3.0
	上线日期：2016-10-11
	更新日志：1.分页化处理，增加导航条，布局调整；2.增加数据版块；3.小屏幕浏览自适应
dashboard v4.0
	上线日期：2016-11-17
	更新日志：1.导航条调整；2.小屏幕浏览自适应优化；3.增加数据版块；4.后台数据刷新改为3次/日;5.uv数据计算方式修改
	