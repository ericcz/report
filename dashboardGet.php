<?php
include_once "dbcon.php";

$pid = $_REQUEST['pid'];
$obj = $_REQUEST['obj'];
$desc = "";

switch($obj){
case 'getDetail': $desc = fnDetail();
break;
case 'getDetailPoint': $desc = fnDetailPoint();
break;
case 'getDashBoard': $desc = fnDashBoard();
break;
case 'getChart': $desc = fnChart();
break;
default:echo "0";
}

function fnDashBoard(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$proc='cspDashboard_get';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc(@x)");
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
function fnDetailPoint(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dt = $_REQUEST['dt'];
	$proc='cspDashboard_detail';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dt')");
	while($GLOBALS["conn"]->more_results()){
	$result=mysqli_store_result($GLOBALS["conn"]);
	$GLOBALS["conn"]->next_result();
	}
	$dc="<table class='table table-striped table-hover table-bordered' style='text-align:center;width:40%'><tr><td class=title></td><td>客户数量</td></tr>";
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
	$dc="";
	$chn="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dt = $_REQUEST['dt'];
	$chartDt="";
	$chartData="";
	
	$arrDt=[];
	$th="";
	$i=-6;
	while ($i<1){
		if (substr($typ,0,4)=='left'){
			$arrDt[$i+6]=$i+7;
		}else
			$arrDt[$i+6]=date('m-d',strtotime("$i day",strtotime($dt)));
		$th.="<td class='th' type=".($i+6).">".date('m-d',strtotime("$i day",strtotime($dt)))."</td>";
		$i+=1;
	}
	if ($typ=="leftW"){
		$arrDs=array("<td></td>","<td></td>","<td></td>","<td></td>","<td></td>","<td></td>","<td></td>");
		$th="<td>第1周</td><td>第2周</td><td>第3周</td><td>第4周</td><td>第5周</td><td>第6周</td><td>第7周</td>";
	}elseif($typ=="leftM"){
		$arrDs=array("<td></td>","<td></td>","<td></td>","<td></td>","<td></td>","<td></td>","<td></td>");
		$th="<td>第1月</td><td>第2月</td><td>第3月</td><td>第4月</td><td>第5月</td><td>第6月</td><td>第7月</td>";
	}elseif($typ=="leftD"){
		$arrDs=array("<td></td>","<td></td>","<td></td>","<td></td>","<td></td>","<td></td>","<td></td>");
		$th="<td>第1天</td><td>第2天</td><td>第3天</td><td>第4天</td><td>第5天</td><td>第6天</td><td>第7天</td>";
	}else
		$arrDs=array("<td class='col0'>0</td>","<td class='col1'>0</td>","<td class='col2'>0</td>","<td class='col3'>0</td>","<td class='col4'>0</td>","<td class='col5'>0</td>","<td class='col6'>0</td>");
	$arrDc=$arrDs;
	$j=0;
	$proc='cspDashboard_detail';
	$result=mysqli_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dt')");
	//$result=mysqli_real_query($GLOBALS["conn"],"select @x");
	while($GLOBALS["conn"]->more_results()){
	$result=mysqli_store_result($GLOBALS["conn"]);
	$GLOBALS["conn"]->next_result();
	}
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			if ($chn!=$row[2]){
				if ($chn!=""){
					for ($i=0;$i<7;$i++){ $dc.=$arrDc[$i]; }
				}
				if($typ=='interval'){
					$dc.="</tr><tr class=tr".$j." type=".$row[3]."><td>".$row[2]."</td>";
					$j+=1;
				}else
					$dc.="</tr><tr><td>".$row[2]."</td>";
				$chartData=substr($chartData,0,strlen($chartData)-1).";".$row[2].",";
				$chn=$row[2];
				$i=0;
				$arrDc=$arrDs;
			}
			while ($arrDt[$i]!=$row[0] and $i<7){
				$i+=1;
				$chartData.="0,";
			}
			if (substr($typ,0,4)=='left'){
				$arrDc[$i]="<td class='col".$i."'>".$row[1]."</td>";
			}else
				$arrDc[$i]="<td class='col".$i."'>".round($row[1])."</td>";
			$i+=1;
			$chartData.=$row[1].",";
		}
		for ($i=0;$i<7;$i++){
			$dc.=$arrDc[$i];
			$chartDt.=$arrDt[$i].",";}
	}else
			$dc = "0";
	
	$dc="<table class='table table-striped table-hover table-bordered' style='text-align:center'><tr><td  class='title'></td>".$th."<tbody class='tbody'>".$dc."</tr></tbody></table>";
	if ($typ=="pv" or $typ=="uv"){
		$dc.="##".substr($chartDt,0,strlen($chartDt)-1)."##".substr($chartData,1);
	}
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
