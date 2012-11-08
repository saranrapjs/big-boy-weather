<?
// TWILIO-BASED INTERFACE FOR READING OUT THE WEATHER VIA SMS AND VOICE

require("../noaaHelper.php");
require("Mustache.php");

$weather = new bigboyWeather();

$zip = (isset($_REQUEST['body'])) ? $weather::sanitizeZip($_REQUEST['body']) : "11231";
$format = (isset($_REQUEST['format']) && $_REQUEST['format'] == "voice") ? "voice" : "sms";
$template = file_get_contents( $format.".mustache");

$weather->geocode( $zip );
$weather->ask = ($format == "voice" && isset($_REQUEST["asked"])) ? true : false;

$mustache = new Mustache;
print $mustache->render($template,$weather);
exit;