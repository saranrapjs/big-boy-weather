<?
include("nws2json.php");
$weather = new nws2json(40.6498,-73.9488);
echo $weather->json;
?>