<?php
include_once "dbcon.php";

$pid = $_REQUEST['pid'];
$obj = $_REQUEST['obj'];
$dtBegin = $_REQUEST['dtBegin'];
$dtEnd = $_REQUEST['dtEnd'];
$typ = $_REQUEST['typ'];
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
	$pid = $GLOBALS["pid"];
	$typ = $GLOBALS['typ'];
	$dt = $GLOBALS['dtBegin'];
	
	switch($typ){
	case 'leftD': 
		$title = '第一天,第二天,第三天,第四天,第五天,第六天,第七天,';
		$dc=fnDetailListC($title);
	break;
	case 'leftW': 
		$title = '第一周,第二周,第三周,第四周,第五周,第六周,第七周,';
		$dc=fnDetailListC($title);
	break;
	case 'leftM': 
		$title = '第一月,第二月,第三月,第四月,第五月,第六月,第七月,';
		$dc=fnDetailListC($title);
	break;
	case 'interval': 
		$title = '1-3秒,3-10秒,10-30秒,第四月,第五月,第六月,第七月,';
		$dc=fnDetailListB($title);
	break;
	case 'debit': 
		$title = '生效日期,有效期,姓名,电话,酒店,售价,赠送金额,结余,是否激活公众号,是否激活app,';
		$dc=fnDetailListA($title);
	break;
	case 'debit-m': 
		$title = '酒店,售卡数量,销售金额,赠送金额,已激活公众号,已激活app,';
		$dc=fnDetailListA($title);
	break;
	case 'debit-all': 
		$title = '酒店,售卡数量,销售金额,赠送金额,结余,已激活公众号,已激活app,';
		$dc=fnDetailListA($title);
	break;
	case 'debit0': 
		$title = '生效日期,有效期,姓名,酒店,售价,赠送金额,结余,';
		$dc=fnDetailListA($title);
	break;
	case 'debit0-all': 
		$title = '酒店,退卡数量,退卡金额,退卡赠送金额,退卡结余,';
		$dc=fnDetailListA($title);
	break;
	case 'member': 
		$title = '加入日期,电话,姓名,酒店,间夜数,剩余换游币,赠送换游币,';
		$dc=fnDetailListA($title);
	break;
	case 'hotel': 
		$title = '日期,城市,酒店名称,间夜数,可用数,最低价,';
		$dc=fnDetailListA($title);
	break;
	case 'hotel-sale-d': 
		$title = '日期,酒店,订单内容,订单类型,联系电话,联系人,入住人,入住日期,退房日期,现金,换游币,间夜数,';
		$dc=fnDetailListA($title);
	break;
	default:
		for ($i=-6;$i<1;$i++){
			$d=date('m-d',strtotime("$i day",strtotime($dt)));
			$title .=$d.",";
		}
		$dc=fnDetailListB($title);
	}
	return $dc;
}

function fnTitle($t){
	$title="";
	$titleArr=explode(',',$t);
	for ($i=0;$i<count($titleArr)-1;$i++){
		$title.="<td>".$titleArr[$i]."</td>";
	}
	$title="<div class='title font20' style='width:100%;text-align:center;margin-top:-20px;'></div><table class='table table-striped table-hover table-bordered' style='text-align:center;width:98%'><tr><td>序号</td>".$title."</tr>";
	return $title;
}

function fnDetailListA($title){
	$dc="";
	$serial=0;
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dtBegin = $GLOBALS['dtBegin'];
	$dtEnd = $GLOBALS['dtEnd'];
	$proc ='cspDashboard_detail';
	$result=mysqli_real_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dtBegin','$dtEnd')");
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	$dc=fnTitle($title);
	$title="";
	if( $result == false ){ 
		echo "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$serial+=1;
			for ($i=0;$i<($result->field_count);$i++){
				$title.="<td>".$row[$i]."</td>";
			}
			$dc.= "<tr><td>".$serial."</td>".$title."</tr>";
			$title="";
		}
	}else
		$dc = "0";
	$dc.="</table>";
	return $dc;
}

function fnDetailListB($title){	//download;lively;register
	$dc="";$arrDs=[];$chartDt="";$chartData="";$chartDataT="";$t="";$ro="";
	$pid = $GLOBALS["pid"];
	$typ = $GLOBALS['typ'];
	$dt = $GLOBALS['dtBegin'];
	
	$arrDt=explode(',',$title);
	$dc=fnTitle($title);
	for ($i=0;$i<7;$i++){
		if (substr($typ,0,4)=='left'){
			$arrDs[]="<td></td>";}
		else{$arrDs[]="<td class='col".($i)."'>0</td>";}
	}
	$i=0;
	$j=0;
	$arrDc=$arrDs;
	$proc='cspDashboard_detail';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dt','')");
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
		for ($i=0;$i<7;$i++){ $chartDt.=$arrDt[$i].",";}
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
	$typ = $GLOBALS['typ'];
	$dt = $GLOBALS['dtBegin'];
	
	$dc=fnTitle($title);
	for ($i=0;$i<7;$i++){
		$arrDt[]=$i+1;
		$arrDs[]="<td></td>";
	}
	$i=0;
	$arrDc=$arrDs;
	$proc='cspDashboard_detail';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dt','')");
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
	$dt = $GLOBALS['dtBegin'];
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
	$dt = $GLOBALS['dtBegin'];
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

