<?php
	$hostname = "rds50pn92wn3z3b9z1lk.mysql.rds.aliyuncs.com";
	$username = "all_read";
	$password = "weshare12";
	$dbName = "wershare";
	
	define('CLIENT_MULTI_RESULTS',131072);
	
	$conn=mysqli_connect($hostname,$username,$password,$dbName) or die("Could not connect: ".mysql_error());
	
	// or die("Could not connect: ".$conn->getMessage()."\n");
	//mysql_select_db($dbName,$conn) or die("Could not select database");
?>
