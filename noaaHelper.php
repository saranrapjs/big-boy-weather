<?
/*
Copyright (c) 2011 JEFF SISSON

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class noaa {
	public $lat;
	public $lon;
	public $current;
	public $forecast;
	function __construct( $lat = 0, $lon = 0) {
		$this->lat = round($lat,4);
		$this->lon = round($lon,4);
	}
	public function fetch($url) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	private function makeDom($string) {
		$this_dom->xml = new DOMDocument();
		$this_dom->xml->loadXML($string);
		$this_dom->xpath = new DomXPath($this_dom->xml);
		return $this_dom;
	}
	public function observations() {
		//"http://forecast.weather.gov/MapClick.php?lat=".$lat."&lon=".$lon."&FcstType=dwml"
		$xmlObject = $this->makeDom($this->fetch("http://forecast.weather.gov/MapClick.php?lat=".$this->lat."&lon=".$this->lon."&FcstType=dwml"));
		$forecast = new noaaForecast($xmlObject);
		$this->forecast = $forecast->get();
		$this->current = $this->getCurrent( $xmlObject->xpath );
		return $this;
	}
	public function getCurrent( $xpath ) {
		$co = '//data[@type="current observations"]';
		$current = new stdClass();
		$current->temp = $xpath->query($co.'/parameters/temperature[@type="apparent"]/value')->item(0)->nodeValue;
		$current->dewpoint = $xpath->query($co.'/parameters/temperature[@type="dew point"]/value')->item(0)->nodeValue;
		$current->humidity = $xpath->query($co.'/parameters/humidity/value')->item(0)->nodeValue;
		$current->weathersummary = $xpath->query($co.'/parameters/weather/weather-conditions')->item(0)->getAttribute('weather-summary');
		$current->icon = $xpath->query($co.'/parameters/conditions-icon/icon-link')->item(0)->nodeValue;
		$current->winddirection = $xpath->query($co.'/parameters/direction[@type="wind"]/value')->item(0)->nodeValue;
		$current->windgust = $xpath->query($co.'/parameters/wind-speed[@type="gust"]/value')->item(0)->nodeValue;
		$current->windsustained = $xpath->query($co.'/parameters/wind-speed[@type="sustained"]/value')->item(0)->nodeValue;
		$current->barometer = $xpath->query($co.'/parameters/pressure[@type="barometer"]/value')->item(0)->nodeValue;
		$current->time = $xpath->query($co.'/time-layout/start-valid-time')->item(0)->nodeValue;
		return $current;
	}
}
class noaaForecast {
	public $xml;
	public $xpath;
	public $times;
	public $textTimes;
	public function __construct( $xmlObject ) {
		$this->xml = $xmlObject;
		$this->xpath = $xmlObject->xpath;
		$this->times = $this->processTimes($xmlObject->xml);

	}
	public function get() {
		$keys = array();
		$keys["minimum"] = $this->xpath('temperature[@type="minimum"]/value', function($n) { return $n->nodeValue; } );
		$keys["maximum"] = $this->xpath('temperature[@type="maximum"]/value',function($n) { return $n->nodeValue; } );
		$keys["pop"] = $this->xpath('probability-of-precipitation/value',function($n) { return ($n->nodeValue!="") ? $n->nodeValue : "0"; });
		$keys["summary"] = $this->xpath('weather/weather-conditions',function($n) { return $n->getAttribute("weather-summary"); });
		$keys["icon"] = $this->xpath('conditions-icon/icon-link',function($n) { return $n->nodeValue; });
		$keys["words"] = $this->xpath('wordedForecast/text',function($n) { return $n->nodeValue; });
		$forecast = $this->formatForecast($keys);
		return $forecast;
	}
	private function formatForecast( $data ) {
		$forecast = array();
		foreach($data as $key => $list) {
			foreach($list as $date => $value ) {
				if (!isset($forecast[$date])) {
					$forecast[$date] = new stdClass();
					$forecast[$date]->datetime = $date;
					$forecast[$date]->day = $this->textTimes[$date];
				}
				$forecast[$date]->$key = $value;
			}
		}
		ksort($forecast);
		return $forecast;
	}
	private function formatTime($node,$num) {
		return $this->times[$node->parentNode->getAttribute('time-layout')][$num]["date"];
	}
	private function xpath($suffix, $callback) {
		$list = $this->xpath->query('//data[@type="forecast"]/parameters/'.$suffix);
		$values = array();
		foreach($list as $key => $node) {
			$values[$this->formatTime($node,$key)] = $callback($node);
		}
		return $values;
	}
	private function processTimes($xml) {
		$time_layouts = $xml->getElementsByTagName( "time-layout" );
		$times = array();
		foreach($time_layouts as $layout) {
			$layout_key = $layout->getElementsByTagName( "layout-key" )->item(0)->nodeValue;
			foreach($layout->getElementsByTagName( "start-valid-time" ) as $layout_value) {
				if (strlen($layout_value->getAttribute('period-name'))) $times[$layout_key][] = array("name"=>$layout_value->getAttribute('period-name'),"date"=>$layout_value->nodeValue);
				$this->textTimes[$layout_value->nodeValue] = $layout_value->getAttribute('period-name');
				//if ($layout_value->getAttribute('period-name') != "current") $this->output["forecast"][$layout_value->getAttribute('period-name')] = array("time"=>$layout_value->nodeValue);
			}
		}
		return $times;
	}
}

class bigboyWeather extends noaa {
	function enshorten() {  // for sms!
		$forecast = $this->theForecast();
		$sentences = explode(".", trim($forecast->words));
		$characters = 0;
		$shorter = "";
		foreach($sentences as $sentence) {
			if ( ($characters + strlen($sentence)) < 160 && strlen($sentence)) {
				$shorter .= $sentence . ".";
				$characters += strlen($sentence);
			}
		}
		return $shorter;
	}
	function theForecast() {
		$forecasts = $this->observations()->forecast;
		foreach ($forecasts as $forecast) {
			break;
		}
		return $forecast;
	}
	function geocode( $zip = null ) {
		// zip code to lat / lng
		$geo_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$zip."&sensor=false";
		$geo_data = json_decode($this->fetch($geo_url));

		$this->lat = $geo_data->results[0]->geometry->location->lat;
		$this->lon = $geo_data->results[0]->geometry->location->lng;
	}
	function sanitizeZip($z) {
		$int =  preg_replace('/\D/','',$z);
		$int = substr($int,0,5);
		return $int;
	}
}
?>