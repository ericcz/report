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


drop procedure if exists cspDashboard_get //
CREATE PROCEDURE cspDashboard_get(
out chRet varchar(1000))
label_pro:BEGIN
	set chRet='';
	if not exists(select table_name from information_schema.tables where table_name='customer') then
		leave label_pro;
	end if;
	set @a='',@b='',@c='';
	set @a=(select count(id) from customer where subscribeDate>=current_date());	-- today
	set @b=(select count(id) from customer where yearweek(subscribeDate)=yearweek(current_date())-1);	-- last week
	set @c=(select count(id) from customer where yearweek(subscribeDate)=yearweek(current_date())-2);	-- before last week
	set chRet=concat(@a,'##',@b,'##',@c);	
	set @a='';
	set @b='';

	select a.dt,ifnull(a.ct,0) as ct,@a:=concat(@a,ifnull(a.ct,0),','),@b:=concat(@b,date_format(a.dt,'%d'),',') from (
	select date(activeDate) as dt,count(id) as ct from customer where date_format(activeDate,'%Y-%m')=date_format(current_date(),'%Y-%m')
	group by date(activeDate)) a;
	set @a=left(@a,length(@a)-1);
	set @b=left(@b,length(@b)-1);
	select concat(chRet,'##',@b,'##','Daily Active,',@a,'##',date_format(CURRENT_DATE(),'%M')) from dual;
	set chRet=concat(chRet,'##',@b,'##','Daily Active,',@a,'##',date_format(CURRENT_DATE(),'%M'));	-- 日期+日活
END //
delimiter ;