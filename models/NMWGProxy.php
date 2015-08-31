<?php

namespace app\models;

class NMWGProxy {

	private $xpath;
	public $xml;
	private $url;

	function __construct($discoveryUrl){
		$this->url = $discoveryUrl;
	}

	function loadFile() {
		$ch = curl_init();

		$options = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,

				CURLOPT_USERAGENT => 'Meican',
				CURLOPT_URL => $this->url,
		);

		curl_setopt_array($ch , $options);

		$output = curl_exec($ch);
		curl_close($ch);
		
		$this->loadXml($output);
	}

	function loadXml($input) {
		$this->xml = new \SimpleXMLElement($input);
		$namespaces = $this->xml->getNameSpaces(true);
		$this->xml = new \DOMDocument();
		$this->xml->loadXML($input);
		$this->xpath = new \DOMXpath($this->xml);
			
		foreach ($namespaces as $ns) {
			$this->xpath->registerNamespace('x', $ns);

			$this->parseDomains();
		}
	}

	function loadFromPerfsonar() {
		$soapClient = new PerfsonarSoapClient($this->url);

		$this->loadXml($soapClient->getTopology());
	}

	function parseLocation($domainName, $deviceName) {
		//PARSER EXCLUSIVO CIPO LOCATIONS
		if ($domainName == "cipo.rnp.br") {
			foreach ($this->xpath->query('//comment()') as $comment) {
				$location = $comment->textContent;
				$location = explode(' ', $location);
				$i=0;
				if ($location[1] == 'ROUTER') {
					$state = $location[3];
					$state = substr($state, 1, -1);
					if (strlen($location[2]) < strlen($state)) {
						$device = $state;
						$state = $location[2];
					} else {
						$device = $location[2];
					}

					if ($device == $deviceName) {
						return $this->queryGeocode($state.'+Brazil');
					}
				}
			}
		}
	}

	function queryGeocode($string){

		$string = str_replace (" ", "+", urlencode($string));
		$details_url = "https://maps.googleapis.com/maps/api/geocode/json?address=".
				$string."&key=AIzaSyD1WDhjvDx14Z_mlG5l3TeMz9thwLU-n8Q";
		 
		$ch = curl_init();

		$options = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,

				CURLOPT_USERAGENT => 'Meican',
				CURLOPT_URL => $details_url,
		);

		curl_setopt_array($ch , $options);

		$output = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($output, true);
		// If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
		if ($response['status'] != 'OK') {
			return array(
					'lng' => null,
					'lat' => null,
					'address'=> null
			);
		}
		 
		$geometry = $response['results'][0]['geometry'];
		 
		$array = array(
				'lng' => $geometry['location']['lng'],
				'lat' => $geometry['location']['lat'],
				'address'=> $response['results'][0]['formatted_address']
		);
		 
		return $array;
	}

	function parseDomains() {
		$domainNodes = $this->xpath->query("//x:domain");
		if ($domainNodes) {
			foreach ($domainNodes as $domainNode) {
				$idString = $domainNode->getAttribute('id');
				$id = explode("=", $idString);
				$domainName = $id[count($id)-1];
					
				$this->parseDevices($domainNode, $domainName);
			}
		}
	}

	function parseDevices($domainNode, $domainName) {
		$i=0;
		$deviceNodes = $this->xpath->query(".//x:node", $domainNode);
		if($deviceNodes) {
			foreach ($deviceNodes as $deviceNode) {
				$idString = $deviceNode->getAttribute('id');
				$id = explode("=", $idString);
				$deviceName = $id[count($id)-1];

				$geo = Device::find()->where(
						['domain'=>$domainName,'node'=>$deviceName])->asArray()->one();
				if (!$geo) {
					$geo = $this->parseLocation($domainName, $deviceName);
					Device::createIfNew($domainName, $deviceName,
						$geo['lat'], $geo['lng'], $geo['address']);
					$i++;
				}

				$location = $this->xml->createElement('CtrlPlane:location');
				$lat = $this->xml->createElement('CtrlPlane:latitude', $geo['lat']);
				$lng = $this->xml->createElement('CtrlPlane:longitude', $geo['lng']);
				$address = $this->xml->createElement('CtrlPlane:address', $geo['address']);
				$location->appendChild($lat);
				$location->appendChild($lng);
				$location->appendChild($address);
				$deviceNode->appendChild($location);

			}
			if ($i>3) {
				$i=0;
				sleep(1);
			}
		}
	}
}

?>