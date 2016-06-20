<?php
header('Cache-Control:no-cache,must-revalidate');    
header('Pragma:no-cache');  
include_once "dbcon.php";

$pid = $_REQUEST['pid'];
$obj = $_REQUEST['obj'];
$desc = "";

switch($obj){
case 'getCustomer': $desc = fnNumber();
break;
case 'getDashBoard': $desc = fnDashBoard();
break;
case 'getchart': $desc = fnChart();
break;
default:echo "0";
}

function fnNumber(){
	$dc="";
	$pid = $GLOBALS["pid"];
	$proc='cspcustomer_get';
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc(@x)");
	$result=mysqli_real_query($GLOBALS["conn"],"select @x");
	$result=mysqli_store_result($GLOBALS["conn"]);
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
