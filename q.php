<?php
include_once "dbcon.php";
//1:git pull ->step2:git tag ->3:show tags ->4:git checkout ->5:ant ->6:cp ->7:ant war ->8:save war ->9:delopy

$p = $_REQUEST['p'];	//project
$t = $_REQUEST['t'];	//tags
$d = $_REQUEST['d'];	//dir
$u = $_REQUEST['u'];	//operator
$dir = "/home/weshare/".$d;	//full dir

$desc = "";
$var = "";
$ob = "GetTags";

$output=shell_exec('cd '.$dir.' && git fetch origin master && git reset --hard origin/master && git checkout master 2>&1');
$output=shell_exec('cd '.$dir.' && git pull 2>&1');

if (trim($output)!="Already up-to-date."){
	$desc = "0##".$ob."##git pull error:".$output;
	$var = fncheckinLogs(0,"git pull",$desc);
	echo $desc;
	return;
}
$output = shell_exec('cd '.$dir.' && git tag');
if( $output == "" ){ 
	$desc = "0##".$ob."##git tag error : no tags";
	$var = fncheckinLogs(0,"git tag",$desc);
	echo $desc;
	return;
}
$tags=explode("\n",$output);
$i=0;
$tt="";
while($i<count($tags)){
	$tt.= $tags[$i]."##";
	$i++;
}
$desc="1##".$ob."##".fnGetTags($tt,$d);
$var = fncheckinLogs(1,"show tags","");

function fnGetTags($tt,$d){
	$proc='cspTag_get';
	$result=mysqli_query($GLOBALS["conn"],"call $proc('$tt','$d',@x)");
	$result=mysqli_query($GLOBALS["conn"],"select @x");
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$dc = $row[0];
		}
	}else
		$dc = "-1";
	return $dc;
}
function fncheckinLogs($sc,$step,$info){
	$proc='cspLogs_ins';
	$p = $GLOBALS["p"];
	$t = $GLOBALS["t"];
	$u = $GLOBALS["u"];
	$result=mysqli_query($GLOBALS["conn"],"call $proc('$sc','$p','$t','$step','$info','$u',@x)");
	$result=mysqli_query($GLOBALS["conn"],"select @x");
	if( $result == false ){ 
		$dc = "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			$dc = $row[0];
		}
	}else
		$dc = "-1";
	return $dc;
}

echo $desc;
mysqli_close($conn);
?>
