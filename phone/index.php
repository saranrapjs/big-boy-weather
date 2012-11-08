<?
// TWILIO-BASED INTERFACE FOR READING OUT THE WEATHER VIA SMS AND VOICE

require("../noaaHelper.php");
require("Mustache.php");

$weather = new bigboyWeather();

$format = (isset($_REQUEST['format']) && $_REQUEST['format'] == "voice") ? "voice" : "sms";
$input = ($format == "voice") ? "Digits" : "Body";
$zip = (isset($_REQUEST[$input])) ? $weather::sanitizeZip($_REQUEST[$input]) : "11231";
$template = file_get_contents( $format.".mustache");

$weather->geocode( $zip );
$weather->asked = ($format == "voice" && isset($_REQUEST["asked"])) ? true : false;

$mustache = new Mustache;
print $mustache->render($template,$weather);
exit;