<?php
include_once "dbcon.php";

$proc='cspDashboard_dataV';
$dc='';
$typ=$_REQUEST['typ'];
$query="select curdate()";
	$result=mysqli_query($conn,"set names utf8");
	//$result=mysqli_query($conn,$query);
	$result=mysqli_real_query($conn,"call $proc('$typ')");
	//$result=mysqli_real_query($conn,"select @x");
	
	while($conn->more_results()){
	$result=mysqli_store_result($conn);
	$conn->next_result();
	}
	
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$dc.=$row[0];
		}
	}else
		$dc = "0";

echo $dc;
mysqli_close($conn);
?>