<?php
include_once "dbcon.php";
//$p = $_REQUEST['p'];	//project
$dc ="";
$query ="select count(id) from customer where activeDate>= current_date();";
$query .="select count(id) from customer where enable=1;";
$result=mysqli_multi_query($conn,$query);
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){
		$result=$conn->store_result();
		while($row=$result->fetch_array()){
			$dc.=$row[0];
		}
		$result->free();
		$conn->next_result();
		$result=mysqli_store_result($conn);
		while($row=mysqli_fetch_array($result)){
			$dc.="##".$row[0];
		}
	}else
		$dc='0';
	
echo $dc;
mysqli_close($conn);
?>
