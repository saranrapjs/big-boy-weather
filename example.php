<?
include("nws2whatever.php");
$weather = new nws2whatever(40.6498,-73.9488);
echo $weather->json;
?>