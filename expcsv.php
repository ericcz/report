<?php
include_once "dbcon.php";

$cols=0;
$pid = $_REQUEST['pid'];
$obj = $_REQUEST['obj'];
fnExpMain();

function fnExpMain(){
	$dc="";
	$title="";

	$typ = $_REQUEST['typ'];
	$dtBegin = $_REQUEST['dtBegin'];
	$dtEnd = $_REQUEST['dtEnd'];
	
	switch($typ){
	case 'debit': 
		$title = '生效日期,有效期,姓名,电话,酒店,售价,赠送金额,结余,绑定会员卡,激活联盟会员';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	case 'debit-m': 
		$title = '酒店,售卡数量,销售金额,赠送金额,绑定会员卡,激活联盟会员';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	case 'debit-all': 
		$title = '酒店,售卡数量,销售金额,赠送金额,结余,绑定会员卡,激活联盟会员';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	case 'debit0': 
		$title = '生效日期,有效期,姓名,酒店,售价,赠送金额,结余';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	case 'debit0-all': 
		$title = '酒店,退卡数量,退卡金额,退卡赠送金额,退卡结余';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	case 'member': 
		$title = '加入日期,电话,姓名,酒店,间夜数,剩余换游币,赠送换游币';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	case 'hotel': 
		$title = '日期,城市,酒店名称,间夜数,可用数,最低价';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	case 'hotel-sale-d': 
		$title = '日期,酒店,订单内容,订单类型,联系电话,联系人,入住人,入住日期,退房日期,现金,换游币,间夜数';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	case 'card-sale': 
		$title = '酒店名,储值卡类型,销售数';
		$json=fnDetailListA($title);
		fnGet_exp($title,$json);
	break;
	default:
		echo "0";
	}
	return $dc;
}
function fnTitle($tabTitle){
	$title="";
	$titleArr=explode(',',$tabTitle);
	for ($i=0;$i<count($titleArr);$i++){
		$title.="<td>".$titleArr[$i]."</td>";
	}
	$title="<table class='table table-striped table-hover table-bordered' style='text-align:center;width:98%'><tr><td>序号</td>".$title."</tr>";
	return $title;
}

function fnDetailListA($title){
	$dc="";$i=0;
	$pid = $GLOBALS["pid"];
	$typ = $_REQUEST['typ'];
	$dtBegin = $_REQUEST['dtBegin'];
	$dtEnd = $_REQUEST['dtEnd'];

	$proc ='cspDashboard_detail';
	$result=mysqli_real_query($GLOBALS["conn"],"set names utf8");
	$result=mysqli_real_query($GLOBALS["conn"],"call $proc('$typ','$dtBegin','$dtEnd')");
	while($GLOBALS["conn"]->more_results()){
		$result=mysqli_store_result($GLOBALS["conn"]);
		$GLOBALS["conn"]->next_result();
	}
	$title="";
	if( $result == false ){ 
		echo "Error .\n";}
	if ($result){ 
		while($row=mysqli_fetch_row($result)){
			for ($i=0;$i<($result->field_count);$i++){
				$title.='"col'.$i.'":"'.$row[$i].'",';
			}
			$dc.= "{".substr($title,0,strlen($title)-1)."},";
			$title="";
		}
		$dc="[".substr($dc,0,strlen($dc)-1)."]";
	}else
		$dc = "0";
	$GLOBALS["pgs"]=$result->num_rows;
	$GLOBALS["cols"]=$i;
	return $dc;
}

function fnGet_exp($title,$jsonOri){
	$dc="";
	$file=date('Ymd').'.csv';
	$json=json_decode($jsonOri,true);
	$json_count=count($json);

	for($i=1;$i<=$json_count;$i++){
		for ($j=0;$j<$GLOBALS["cols"];$j++){
			$dc.=$json[$i-1]['col'.$j].",";
		}
		$dc.="\n";
	}
	$dc=$title."\n".$dc;
	export_csv($file,$dc);
}
function export_csv($filename,$data) { 
    header("Content-type:text/csv"); 
    header("Content-Disposition:attachment;filename=".$filename); 
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0'); 
    header('Expires:0'); 
    header('Pragma:public'); 
    echo $data;
}

mysqli_close($conn);
?>