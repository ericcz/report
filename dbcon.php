﻿<?php
	$hostname = "rds50pn92wn3z3b9z1lk.mysql.rds.aliyuncs.com";
	$username = "report";
	$password = "report61136500";
	$dbName = "crius";

	define('CLIENT_MULTI_RESULTS',131072);

	$conn=mysqli_connect($hostname,$username,$password,$dbName) or die("Could not connect: ".mysqli_error());
?>
