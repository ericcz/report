<?php
include_once "dbcon.php";

$proc='cspDashboard_detail';
$dt='2016-06-13';
$dc='';
$typ='loc';
$query="select curdate()";

	$result=mysqli_query($conn,"set names utf8");
	//$result=mysqli_real_query($conn,"call $proc('$typ',@x)");
	//$result=mysqli_real_query($conn,"select @x");

	$result=mysqli_query($conn,$query);
	
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


echo '[{"value":234}]';

mysqli_close($conn);
?>
