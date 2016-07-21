<?php
header('Cache-Control:no-cache,must-revalidate');    
header('Pragma:no-cache');  
include_once "dbcon.php";

$pid = $_REQUEST['pid'];
$obj = $_REQUEST['obj'];
$desc = "";

switch($obj){
case 'getDetail': $desc = fnDetail();
break;
case 'getDetailPv': $desc = fnDetailPv();
break;
case 'getDetailLeft': $desc = fnDetailLeft();
break;
case 'getDashBoard': $desc = fnDashBoard();
break;
case 'getChart': $desc = fnChart();
break;
default:echo "0";
}

function fnDetail(){
	$dc="";
	$chn="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dt = $_REQUEST['dt'];
	
	$arrChn=array("Yingyongbao_android","Xiaomi_yingyong_android","official","m360_mobile_android","ZongHe_01","Baidu_mobile_android","dingbin_04","dingbin_05","dingbin_02","dingbin_01","ZongHe_02","Wandoujia_android","Huazhu_weixin_android","dingbin_07","dingbin_03","dingbin_06","","IOS");
	
	$arrDt=[];
	$th="";
	$i=-6;
	while ($i<1){
		$arrDt[$i+6]=date('m-d',strtotime("$i day",strtotime($dt)));
		$th.="<td>".date('m-d',strtotime("$i day",strtotime($dt)))."</td>";
		$i+=1;
	}

	$proc='cspDashboard_detail';
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ',@x)");
	$result=mysqli_real_query($GLOBALS["conn"],"select @x");
	while($GLOBALS["conn"]->more_results()){
	$result=mysqli_store_result($GLOBALS["conn"]);
	$GLOBALS["conn"]->next_result();
	}
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			if ($chn!=$row[2]){
				$dc.="</tr><tr><td>";
				if ($typ=="reg"){
					$dc.=$row[2]."</td>";
				}else
					$dc.=$arrChn[$row[2]-1]."</td>";
				$chn=$row[2];
				$i=0;
			}
			
			while ($arrDt[$i]!=$row[0] and $i<7){
				$dc.="<td>0</td>";
				$i+=1;
			}

			$dc.="<td class='col".$i."'>".round($row[1])."</td>";
			$i+=1;

		}
	}else
		$dc = "0";
	return "<table class='table table-striped table-hover table-bordered'><tr><td style='width:10%'></td>".$th."</tr>".substr($dc,5)."</tr></table>";
}
function fnDetailPv(){
	$dc="";
	$chn="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dt = $_REQUEST['dt'];
	
	$arrDt=[];
	$th="";
	$i=-6;
	while ($i<1){
		$arrDt[$i+6]=date('m-d',strtotime("$i day",strtotime($dt)));
		$th.="<td>".date('m-d',strtotime("$i day",strtotime($dt)))."</td>";
		$i+=1;
	}

	$proc='cspDashboard_detail';
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ',@x)");
	$result=mysqli_real_query($GLOBALS["conn"],"select @x");
	while($GLOBALS["conn"]->more_results()){
	$result=mysqli_store_result($GLOBALS["conn"]);
	$GLOBALS["conn"]->next_result();
	}
	$i=0;
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
		
			while ($arrDt[$i]!=$row[0] and $i<7){
				$dc.="<td>0</td>";
				$i+=1;
			}
			$i+=1;
			$dc.="<td>".round($row[1])."</td>";
		}
	}else
		$dc = "0";
	return "<table class='table table-striped table-hover table-bordered'><tr>".$th."</tr><tr>".$dc."</tr></table>";
}
function fnDetailLeft(){
	$dc="";
	$chn="";
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dt = $_REQUEST['dt'];
	
	$arrDt=[];
	$th="";
	$i=-6;
	while ($i<1 and $typ=='leftD'){
		$arrDt[$i+6]=date('m-d',strtotime("$i day",strtotime($dt)));
		$th.="<td>".date('m-d',strtotime("$i day",strtotime($dt)))."</td>";
		$i+=1;
	}

	$proc='cspDashboard_detail';
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ',@x)");
	$result=mysqli_real_query($GLOBALS["conn"],"select @x");
	while($GLOBALS["conn"]->more_results()){
	$result=mysqli_store_result($GLOBALS["conn"]);
	$GLOBALS["conn"]->next_result();
	}
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$dc.="<td>".$row[1]."%</td>";
			if ($typ!='leftD'){
				$th.="<td>".$row[2]."</td>";
			}
		}
	}else
		$dc = "0";
	return "<table class='table table-striped table-hover table-bordered'><tr>".$th."</tr><tr>".$dc."</tr></table>";
}
function fnDashBoard(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$proc='cspDashboard_get';
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
