<?php
include("noaaHelper.php");
$weather = new noaaHelper(40.6498,-73.9488);
$weather->observations();
?>
