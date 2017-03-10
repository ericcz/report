<?php
include_once "dbcon.php";

$pgnum=10;
$pgs=0;
$cols=0;
$pid = $_REQUEST['pid'];
$obj = $_REQUEST['obj'];
$desc = "";

switch($obj){
case 'getDashBoard': $desc = fnDashBoard();
break;
case 'getDetail': $desc = fnDetail();
break;
case 'getChart': $desc = fnChart();
break;
case 'getFunnel': $desc = fnFunnel();
break;
default:echo "0";
}

function fnDashBoard(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$proc='cspDashboard_get';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_query($GLOBALS["conn"],"call $proc(@x)");
	$result=mysqli_query($GLOBALS["conn"],"select @x");
	//if (!$result) {printf("Error: %s\n", mysqli_error($GLOBALS["conn"]));exit();}
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$dc = $row[0];
		}
	}else
		$dc = "0";
	return $dc;
}
function fnDetail(){
	$dc="";$title="";
	$pgnum=$GLOBALS["pgnum"];
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dtBegin = $_REQUEST['dtBegin'];
	$dtEnd = $_REQUEST['dtEnd'];
	$pg = $_REQUEST['pg'];
	$rcf=$pg*$pgnum-$pgnum+1;
	
	switch($typ){
	case 'leftD': 
		$title = '第一天,第二天,第三天,第四天,第五天,第六天,第七天';
		$dc=fnDetailListC($title);
	break;
	case 'leftW': 
		$title = '第一周,第二周,第三周,第四周,第五周,第六周,第七周';
		$dc=fnDetailListC($title);
	break;
	case 'leftM': 
		$title = '第一月,第二月,第三月,第四月,第五月,第六月,第七月';
		$dc=fnDetailListC($title);
	break;
	case 'interval-c': 
		$title = '1-3秒,3-10秒,10-30秒,30-60秒,1-3分钟,3-10分钟,10-30分钟,30分钟以上';
		$dc=fnDetailListB($title);
	break;
	case 'debit': 
		$title = '生效日期,有效期,姓名,电话,酒店,售价,赠送金额,结余,绑定会员卡,激活联盟会员';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	case 'debit-m': 
		$title = '酒店,售卡数量,销售金额,赠送金额,绑定会员卡,激活联盟会员';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	case 'debit-all': 
		$title = '酒店,售卡数量,销售金额,赠送金额,结余,绑定会员卡,激活联盟会员';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	case 'debit0': 
		$title = '生效日期,有效期,姓名,酒店,售价,赠送金额,结余';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	case 'debit0-all': 
		$title = '酒店,退卡数量,退卡金额,退卡赠送金额,退卡结余';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	case 'member': 
		$title = '加入日期,电话,姓名,酒店,间夜数,剩余换游币,赠送换游币';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	case 'hotel': 
		$title = '日期,城市,酒店名称,间夜数,可用数,最低价';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	case 'hotel-sale-d': 
		$title = '日期,酒店,订单内容,订单类型,联系电话,联系人,入住人,入住日期,退房日期,现金,换游币,间夜数';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	case 'card-sale': 
		$title = '酒店名,储值卡类型,销售数';
		$json=fnDetailListA($title);
		$dc=fnPaging(fnTitle($title),$json,$rcf,$pgnum);
		$dc.="##".fnGetPages($GLOBALS["pgs"],$pgnum);
	break;
	default:
		$p=round((strtotime($dtEnd)-strtotime($dtBegin))/3600/24)+1;
		$i=($pg-1)*7;
		$dt=date('Y-m-d',strtotime("$i day",strtotime($dtBegin)));
		$l=(($i+7)>$p?$p-$i:7);
		for ($i=0;$i<7;$i++){
			if ($i<=$l){
				$d=date('m-d',strtotime("$i day",strtotime($dt)));
			}else
				$d="";
			$title .=$d.",";
		}
		$title=substr($title,0,strlen($title)-1);
		$dc=fnDetailListB($title,$dt)."##".fnGetPages($p,7);
	}
	return $dc;
}
function fnGetPages($p,$pgnum){
	$i=0;
	if($p%$pgnum>0){
		$i=floor($p/$pgnum)+1;
	}else
		$i=floor($p/$pgnum);
	return $i;
}
function fnTitle($t){
	$title="";
	$titleArr=explode(',',$t);
	for ($i=0;$i<count($titleArr);$i++){
		$title.="<td>".$titleArr[$i]."</td>";
	}
	$title="<table class='table table-striped table-hover table-bordered' style='text-align:center;width:98%'><tr><td>序号</td>".$title."</tr>";
	return $title;
}
function fnPaging($tabTitle,$jsonOri,$rowF,$pageRow){
	$rowE=0;
	$dic="";
	$cell="";
	$json=json_decode($jsonOri,true);
	$json_count=count($json);
	if (($json_count-$rowF)<$pageRow){
		$rowE=$json_count;
	}else
		$rowE=$rowF+$pageRow-1;
	for($i=$rowF;$i<=$rowE;$i++){
		for ($j=0;$j<$GLOBALS["cols"];$j++){
			$cell.="<td>".$json[$i-1]['col'.$j]."</td>";
		}
		$dic.="<tr><td>".$i."</td>".$cell."</tr>";
		$cell="";
	}
	$dic=$tabTitle.$dic."</table>";
	return $dic;
}
function fnDetailListA($title){
	$dc="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dtBegin = $_REQUEST['dtBegin'];
	$dtEnd = $_REQUEST['dtEnd'];

	$proc ='cspDashboard_detail';
	$result=mysqli_real_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dtBegin','$dtEnd')");
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	$title="";
	if( $result == false ){ 
		echo "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			for ($i=0;$i<($result->field_count);$i++){
				$title.='"col'.$i.'":"'.$row[$i].'",';
			}
			$dc.= "{".substr($title,0,strlen($title)-1)."},";
			$title="";
		}
		$dc="[".substr($dc,0,strlen($dc)-1)."]";
	}else
		$dc = "0";
	$GLOBALS["pgs"]=$result->num_rows;
	$GLOBALS["cols"]=$i;
	return $dc;
}

function fnDetailListB($title,$dtBegin){	//download;lively;register
	$dc="";$arrDs=[];$chartDt="";$chartData="";$chartDataT="";$t="";$ro="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$arrDt=explode(',',$title);
	$dc=fnTitle($title);
	for ($i=0;$i<7;$i++){
		if (substr($typ,0,4)=='left'){
			$arrDs[]="<td></td>";}
		else{$arrDs[]="<td class='col".($i)."'>0</td>";}
	}
	if ($typ=='interval-c'){
		$arrDs[]="<td class='col".($i)."'>0</td>";
	}
	$i=0;
	$j=0;
	$arrDc=$arrDs;
	
	$proc ='cspDashboard_detail';
	$result=mysqli_real_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dtBegin','')");
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			if ($ro!=$row[2] or $i==0){
				if ($i<>0){
					for ($i=0;$i<count($arrDs);$i++){ $dc.=$arrDc[$i];}
					$chartData.=substr($chartDataT,0,strlen($chartDataT)-1).";";
					$chartDataT="";
				}
				$arrDc=$arrDs;
				$ro=$row[2];
				$i=0;
				if($typ=='interval'){
					$dc.="</tr><tr class=tr".$j." type=".$row[3]."><td>".$row[2]."</td>";
					$j+=1;
				}else{
					$dc.="</tr><tr><td>".$row[2]."</td>";
					$chartData.=" ".$row[2].",";
				}
			}
			while ($i<count($arrDs) and $arrDt[$i]!=$row[0]){
				$i+=1;
				$chartDataT.="0,";
			}
			if (substr($typ,0,4)=='left'){
				$arrDc[$i]="<td class='col".$i."'>".$row[1]."</td>";
			}else
				$arrDc[$i]="<td class='col".$i."'>".round($row[1])."</td>";
			$chartDataT.=$row[1].",";
			$i+=1;
		}
		for ($i=0;$i<count($arrDs);$i++){ $dc.=$arrDc[$i];}
		$chartData.=substr($chartDataT,0,strlen($chartDataT)-1).";";
		for ($i=0;$i<count($arrDt);$i++){ $chartDt.=$arrDt[$i].",";}
	}else
		$dc = "0";
		
	$dc=$dc."</tr></tbody></table>";
	if ($typ=="pv" or $typ=="uv"){
		$dc.="##".substr($chartDt,0,strlen($chartDt)-1)."##".substr($chartData,1);}
	return $dc;
}

function fnDetailListC($title){	//Left Day Week Month
	$dc="";$arrDs=[];$chartDt="";$chartData="";$chartDataT="";$t="";$ro="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dt = $_REQUEST['dtBegin'];

	$dc=fnTitle($title);
	for ($i=0;$i<7;$i++){
		$arrDt[]=$i+1;
		$arrDs[]="<td></td>";
	}
	$i=0;
	$arrDc=$arrDs;
	$pg = $_REQUEST['pg'];
	$proc ='cspDashboard_detail';
	$result=mysqli_real_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dtBegin','$dtEnd','$pg')");
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			if ($ro!=$row[2] or $i==0){
				if ($i<>0){
					for ($i=0;$i<count($arrDs);$i++){ $dc.=$arrDc[$i];}
					$chartData.=substr($chartDataT,0,strlen($chartDataT)-1).";";
					$chartDataT="";
				}
				$arrDc=$arrDs;
				$ro=$row[2];
				$i=0;
				$dc.="</tr><tr><td>".$row[2]."</td>";
				$chartData.=" ".$row[2].",";
				
			}
			while ($i<count($arrDs) and $arrDt[$i]!=$row[0]){
				$i+=1;
				$chartDataT.="0,";
			}

			$arrDc[$i]="<td class='col".$i."'>".$row[1]."</td>";
			$chartDataT.=$row[1].",";
			$i+=1;
		}
		for ($i=0;$i<count($arrDs);$i++){ $dc.=$arrDc[$i];}
		$chartData.=substr($chartDataT,0,strlen($chartDataT)-1).";";
		for ($i=0;$i<7;$i++){ $chartDt.=$arrDt[$i].",";}
	}else
		$dc = "0";
		
	$dc=$dc."</tr></tbody></table>";
	if ($typ=="pv" or $typ=="uv"){
		$dc.="##".substr($chartDt,0,strlen($chartDt)-1)."##".substr($chartData,1);}
	return $dc;
}

function fnChart(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$dt = $_REQUEST['dtBegin'];
	$proc='csphighchart_get';
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$dt',@x)");
	$result=mysqli_real_query($GLOBALS["conn"],"select @x");
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$dc = $row[0];
		}
	}else
		$dc = "0";
	return $dc;
}
function fnFunnel(){
	$dc="";$step="";$op="";$val="";$tab="";$tmp="";$tmp2="";
	$sid=$_REQUEST["sid"];
	$dt = $_REQUEST['dtBegin'];
	$proc='cspDashboard_funnel';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$sid','$dt')");
	$result=mysqli_real_query($GLOBALS["conn"],"select @x");
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$step.=$row[0].",";
			$op.=$row[1].",";
			$val.=$row[2].",";
			if ($tmp==""){
				$tmp="100%";
				$tmp2=$row[2];
			}else
				$tmp=(@($row[2]/$tmp)?round($row[2]/$tmp*100,2).'%':0);
			$tab.="<tr><td>".$row[0]."</td><td>".$row[1]."</td><td>".$row[2]."</td><td>".$tmp."</td><td>".(@(round($row[2]/$tmp2*100,2)))."%</td></tr>";
			$tmp=$row[2];
		}
		$dc=$op."##".$val."##"."<table class='table table-hover table-striped' style='line-height:10px'><tr><td>步骤序号</td><td>名称</td><td>次数</td><td>上一比</td><td>总比</td></tr>".$tab."</table>";
	}else
		$dc = "0";
	return $dc;
}

echo "1##".$obj."##".$desc;
mysqli_close($conn);
?>

