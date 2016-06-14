

/*
function xmlText(xtext){
	var xmlDoc="";
	if (window.ActiveXObject){	//For IE browser
		xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
		xmlDoc.async=false;
		xmlDoc.loadXML(xtext);
	} else if (document.implementation && document.implementation.createDocument){	//For FireFox
		try{
			var parser = new DOMParser();
			var xmlDoc = parser.parseFromString(xtext,"text/xml");
		}catch(e){
			try{	//Chrome safari Opera
				var xmlhttp = new window.XMLHttpRequest();
				xmlhttp.open("GET",xtext,false);
				xmlhttp.send(null);
				xmlDoc = xmlhttp.responseXML;
			}catch(e){
				alert(e.message);
			}
		}
	}else {return null;}
	return xmlDoc;
}
var xmlDoc=xmlText("<root><dbtype>mssq</dbtype></root>");
*/
function xmlFile(xfile){
	var xmlDoc="";
	if (window.ActiveXObject){	//For IE browser
		xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
		xmlDoc.async=false;
		xmlDoc.load(xfile);
	} else if (document.implementation && document.implementation.createDocument){	//For FireFox
		try{
			xmlDoc = document.implementation.createDocument('', '', null);
			xmlDoc.async = false;
			xmlDoc.load(xfile)
		}catch(e){
			try{	//Chrome safari Opera
				var xmlhttp = new window.XMLHttpRequest();
				xmlhttp.open("GET",xfile,false);
				xmlhttp.send(null);
				xmlDoc = xmlhttp.responseXML;
			}catch(e){
				alert(e.message);
			}
		}
	}else {return null;}
	return xmlDoc;
}



function createXMLHttpRequest(url,dv){
	xmlhttp=""
	if(window.ActiveXObject){
		try{
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		}catch(e){
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}}
		else if(window.XMLHttpRequest){
			xmlhttp = new XMLHttpRequest();
				if (xmlhttp.overrideMimeType){ xmlhttp.overrideMimeType("text/xml");}
		}
		if(!xmlhttp){ window.alert("Do not support XMLHttpRequest!");}
		xmlhttp.onreadystatechange=XHRChanged;
		xmlhttp.open("POST", url,false);
		xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlhttp.send(dv);
}
function XHRChanged(){	//handle return value xmlhttp.responseText
	if (xmlhttp.readyState==4){
		getBack(xmlhttp.responseText);}
}
function cookieExpiryDate(){
	date = new Date();
	return (new Date(date.setDate(date.getYear()+10))).toGMTString();
}
/*
if (window.ActiveXObject){	//For IE browser
	xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
	xmlDoc.async=false;
	xmlDoc.load("config.xml");
	var fpath=xmlDoc.getElementsByTagName("dbtype")[0].childNodes[0].nodeValue
	}
else if (document.implementation && document.implementation.createDocument){ //For Firefox
	var xmlDom = document.implementation.createDocument("","",null);
	xmlDom.load("config.xml");
	xmlDom.onload = function(){
	var fpath=xmlDom.getElementsByTagName("dbtype")[0].childNodes[0].nodeValue;
	}}
else{
	alert('Your browser cannot handle this script');
}
*/


function getBack(vchReturn){
	try{
		//$(".alert").html(vchReturn);
		$(".alert").css('display','block')
		vchReturn=vchReturn.substr(0,vchReturn.length);
		var vf=vchReturn.split("#")[0];
		var desc=vchReturn.split("#")[1];
		if (vf=="1"){
			document.cookie=document.getElementById("inp-usr").value;
			$(".alert").html("Welcome");
			timeid=setTimeout(function(){
			window.location="query.html";},2000);
		}else{
			$(".alert").html(desc);
			$(".inp-usr").val("");
			$(".inp-pwd").val("");
			}
		}catch(e){ alert(e);}
}
$(document).ready(function(){
	var xmlDoc=xmlFile("config.xml");
	node=xmlDoc.getElementsByTagName("user");
	var emp=node[0].childNodes[0].nodeValue;
	node=xmlDoc.getElementsByTagName("pwd");
	var pwd=node[0].childNodes[0].nodeValue;
	$(".hid-u").val(emp);
	$(".hid-p").val(pwd);
	$(".dvb").show();
	$(".btn").click(function(){
		var udf1=document.getElementById("inp-usr").value
		var udf2=document.getElementById("inp-pwd").value
		if (isNaN(udf1)==1 || (udf1.length!=4)||udf2==""){
			$(".alert").html("incorrect user").css("display","block");
			return;
		}
		if (udf1!=$(".hid-u").val() || udf2!=$(".hid-p").val()){
			$(".alert").html("Wrong User or Password").css("display","block");
			return;
		}else{
			$(".alert").html("Welcome").css("display","block");
			document.cookie=udf1;
			timeid=setTimeout(function(){
			window.location="query.html";},2000);
		}

		//url=php+"?Pid="+escape(udf1)+"&Pwd="+escape(udf2)+"&fresh="+Math.random();
		//createXMLHttpRequest(url,"fresh="+Math.random());
	});
})