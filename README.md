nws2json
================================

My little PHP class for generating JSON versions of NOAA/NWS forecast data, intended as a lightly formatted proxy for existing NOAA/NWS xml.  
"no pro" and "never not finishing", licensed under the MIT license.

Example
-------------------------
    include("nws2json.php");
    //setup with latitude and longitude
    $weather = new nws2json(40.6498,-73.9488);
    echo $weather->json;

Background
-------------------------
The URL template for the xml looks like this: http://forecast.weather.gov/MapClick.php?lat=40.6498&lon=-73.9488&FcstType=dwml

NWS provides a real REST API, which is nearly identical, except that (to my knowledge?) their API XML doesn't include the "wordedForecast".  This element is one of the reasons I'm continually drawn to government weather over other services/API's: it is real to appreciate a bit of human-generated, descriptive text when you are reading the weather.