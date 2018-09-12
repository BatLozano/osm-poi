<?php
namespace osm_poi;


function nettoie($str){
	if(is_array($str)) return $str;
	if($str == null) return "";
	$str 	= str_replace("\t", " ", $str);
	$str 	= str_replace("\n", " ", $str);
	$str 	= str_replace("\r\n", " ", $str);
	$str 	= str_replace("\r", " ", $str);
	$str 	= strip_tags($str);
	while(strstr($str , "  ")) $str 	= str_replace("  ", " ", $str);
	while(substr($str , 0 , 1) == " ") $str = substr($str , 1 , strlen($str)-1);
	while(substr($str , strlen($str)-1 , 1) == " ") $str = substr($str , 0 , strlen($str)-1);
	return $str;
}



