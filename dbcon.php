<?php
	$hostname = "rm-bp1c2y1656jgbn3lh.mysql.rds.aliyuncs.com";
	$username = "report";
	$password = "report_61136500";
	$dbName = "crius";

	define('CLIENT_MULTI_RESULTS',131072);

	$conn=mysqli_connect($hostname,$username,$password,$dbName) or die("Could not connect: ".mysqli_error());
?>
