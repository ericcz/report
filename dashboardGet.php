<?php
include_once "dbcon.php";

$pid = $_REQUEST['pid'];
$obj = $_REQUEST['obj'];
$desc = "";

switch($obj){
case 'getDetail': $desc = fnDetail();
break;
case 'getDetailWheel': $desc = fnDetailWheel();
break;
case 'getDashBoard': $desc = fnDashBoard();
break;
case 'getChart': $desc = fnChart();
break;
case 'getFunnel': $desc = fnFunnel();
break;
default:echo "0";
}

function fnFunnel(){
	$dc="";$step="";$op="";$val="";$tab="";$tmp="";$tmp2="";
	$sid=$_REQUEST["sid"];
	$dt=$_REQUEST["dt"];
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

function fnDashBoard(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$proc='cspDashboard_get';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc(@x)");
	$result=mysqli_real_query($GLOBALS["conn"],"select @x");
	if (!$result) {printf("Error: %s\n", mysqli_error($GLOBALS["conn"]));exit();}
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
function fnDetailWheel(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dt = $_REQUEST['dt'];
	$proc ='cspDashboard_detail';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dt')");
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	$dc="<table class='table table-striped table-hover table-bordered' style='text-align:center;width:40%'><tr><td class=title></td><td>人数</td></tr>";
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$dc.= "<tr><td>".$row[0]."</td><td>".$row[1]."</td></tr>";
		}
	}else
		$dc = "0";
	$dc.="</table>";
	return $dc;
}
function fnDetail(){
	$dc="";$chartDt="";$chartData="";$chartDataT="";$t="";$ro="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dt = $_REQUEST['dt'];
	if ($typ=="leftD"){
		$t="天";
	}elseif ($typ=="leftW"){
		$t="周";
	}elseif ($typ=="leftM"){
		$t="月";
	}
	$arrDt=[];	//日期数组
	$arrDs=[];	//表格行数组
	$th="";
	$i=-6;
	while ($i<1){
		if (substr($typ,0,4)=='left'){
			$arrDt[$i+6]=$i+7;
			$arrDs[]="<td></td>";
			$th.="<td>第".($i+7).$t."</td>";
		}else{
			$arrDt[$i+6]=date('m-d',strtotime("$i day",strtotime($dt)));
			$arrDs[]="<td class='col".($i+6)."'>0</td>";
			$th.="<td class='th' type=".($i+6).">".date('m-d',strtotime("$i day",strtotime($dt)))."</td>";
		}
		$i+=1;
	}
	if ($typ=="interval-c"){
		$th="<td>1-3秒</td><td>3-10秒</td><td>10-30秒</td><td>30-60秒</td><td>1-3分钟</td><td>3-10分钟</td><td>10-30分钟</td><td>30分钟以上</td>";
		$arrDt=["1-3秒","3-10秒","10-30秒","30-60秒","1-3分钟","3-10分钟","10-30分钟","30分钟以上"];
		$arrDs=["<td>0</td>","<td>0</td>","<td>0</td>","<td>0</td>","<td>0</td>","<td>0</td>","<td>0</td>","<td>0</td>"];
	}
	$arrDc=$arrDs;
	$j=0;
	$i=0;
	
	$proc='cspDashboard_detail';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dt')");
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

	$dc="<table class='table table-striped table-hover table-bordered' style='text-align:center'><tr><td  class='title'></td>".$th."<tbody class='tbody'>".$dc."</tr></tbody></table>";
	if ($typ=="pv" or $typ=="uv"){
		$dc.="##".substr($chartDt,0,strlen($chartDt)-1)."##".substr($chartData,1);}
	return $dc;
}

function fnChart(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$dt = $_REQUEST['dt'];
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

echo "1##".$obj."##".$desc;
mysqli_close($conn);
?>
