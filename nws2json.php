<?
/*
Copyright (c) 2011 JEFF SISSON

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class nws2json {
	function __construct($lat,$lon) {
		if (!$lat || !$lon) $json["error"][] = "missing map coordinates";
		$this->lat = round($lat,4);
		$this->lon = round($lon,4);
		$this->get_obs();
	}
	private function get_url($url) {
		if (!function_exists('curl_init')){
			$json["error"][] = "curl is not installed";
			return false;
		}
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		return $this->create_dom($data);
	}
	private function create_dom($string) {
		$this_dom->xml = new DOMDocument();
		$this_dom->xml->loadXML($string);
		$this_dom->xpath = new DomXPath($this_dom->xml);
		return $this_dom;
	}
	public function get_obs() {
		//"http://forecast.weather.gov/MapClick.php?lat=".$lat."&lon=".$lon."&FcstType=dwml"
		$this->obs = $this->get_url("http://forecast.weather.gov/MapClick.php?lat=".$this->lat."&lon=".$this->lon."&FcstType=dwml");
		$this->process_times($this->obs->xml);
		$this->get_forecast();
		$this->get_current();
		$this->finish();
	}
	public function finish() {
		$this->json = json_encode($this->json);
	}
	public function get_current() {
		$co = '//data[@type="current observations"]';
		$this->json["current"]["temp"] = $this->obs->xpath->query($co.'/parameters/temperature[@type="apparent"]/value')->item(0)->nodeValue;
		$this->json["current"]["dewpoint"] = $this->obs->xpath->query($co.'/parameters/temperature[@type="dew point"]/value')->item(0)->nodeValue;
		$this->json["current"]["humidity"] = $this->obs->xpath->query($co.'/parameters/humidity/value')->item(0)->nodeValue;
		$this->json["current"]["weathersummary"] = $this->obs->xpath->query($co.'/parameters/weather/weather-conditions')->item(0)->getAttribute('weather-summary');
		$this->json["current"]["icon"] = $this->obs->xpath->query($co.'/parameters/conditions-icon/icon-link')->item(0)->nodeValue;
		$this->json["current"]["winddirection"] = $this->obs->xpath->query($co.'/parameters/direction[@type="wind"]/value')->item(0)->nodeValue;
		$this->json["current"]["windgust"] = $this->obs->xpath->query($co.'/parameters/wind-speed[@type="gust"]/value')->item(0)->nodeValue;
		$this->json["current"]["windsustained"] = $this->obs->xpath->query($co.'/parameters/wind-speed[@type="sustained"]/value')->item(0)->nodeValue;
		$this->json["current"]["barometer"] = $this->obs->xpath->query($co.'/parameters/pressure[@type="barometer"]/value')->item(0)->nodeValue;
		$this->json["current"]["time"] = $this->obs->xpath->query($co.'/time-layout/start-valid-time')->item(0)->nodeValue;
	}
	public function get_forecast() {
		//nightly minimum
		$this->forecast_days('temperature[@type="minimum"]/value',"minimum",function($n) { return $n->nodeValue; });
		//daily maximum
		$this->forecast_days('temperature[@type="maximum"]/value',"maximum",function($n) { return $n->nodeValue; });
		//percent precip
		$this->forecast_days('probability-of-precipitation/value',"pop",function($n) { return ($n->nodeValue!="") ? $n->nodeValue : "0"; });
		//conditions: Weather Type, Coverage, Intensity
		$this->forecast_days('weather/weather-conditions',"weathersummary",function($n) { return $n->getAttribute("weather-summary"); });
		//icon
		$this->forecast_days('conditions-icon/icon-link',"icon",function($n) { return $n->nodeValue; });
		//worded forecast
		$this->forecast_days('wordedForecast/text',"wordedforecast",function($n) { return $n->nodeValue; });
	}
	private function forecast_days($xpath,$name,$callback) {
		$array = $this->obs->xpath->query('//data[@type="forecast"]/parameters/'.$xpath);
		foreach($array as $key => $node) {
			$this->json["forecast"][$this->time_key($node,$key)][$name] = $callback($node);
		}
	}
	private function time_key($node,$num) {
		//get appropriate time key based on time layout
		return $this->times[$node->parentNode->getAttribute('time-layout')][$num]["name"];
	}
	private function process_times($xml) {
		$time_layouts = $xml->getElementsByTagName( "time-layout" );
		$times = array();
		foreach($time_layouts as $layout) {
			$layout_key = $layout->getElementsByTagName( "layout-key" )->item(0)->nodeValue;
			foreach($layout->getElementsByTagName( "start-valid-time" ) as $layout_value) {
				$times[$layout_key][] = array("name"=>$layout_value->getAttribute('period-name'),"date"=>$layout_value->nodeValue);
				if ($layout_value->getAttribute('period-name') != "current") $this->json["forecast"][$layout_value->getAttribute('period-name')] = array("time"=>$layout_value->nodeValue);
			}
		}
		$this->times =  $times;
	}
}
?>