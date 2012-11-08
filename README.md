big boy weather
================================

PHP utilities for generating meaningful versions of NOAA/NWS forecast data, implemented as a lightly formatted proxy for existing NOAA/NWS xml.  
"no pro" and "never not finishing", licensed under the MIT license.

Example
-------------------------
	include("noaaHelper.php");
	$weather = new noaaHelper(40.6498,-73.9488);
	$weather->observations();

Background
-------------------------
The URL template for the xml we're parsing looks like this: http://forecast.weather.gov/MapClick.php?lat=40.6498&lon=-73.9488&FcstType=dwml

NWS provides a real REST API, which is nearly identical, except that (to my knowledge?) their API XML doesn't include the "wordedForecast".  The worded forecast is one of the reasons I'm continually drawn to government weather over other services/API's: it is real to appreciate a bit of human-generated, descriptive text when you are reading the weather.

I've also included the code I used to run [Big Boy Weather](http://bigboy.us/), a weather-by-phone service.