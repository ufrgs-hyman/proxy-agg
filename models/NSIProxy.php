<?php

namespace app\models;
use Yii;
use yii\helpers\Url;

class NSIProxy {

	public $xml;
	private $xpath;
	private $url;
	private $error;

	function loadXml($input) {
		$this->xml = new \DOMDocument();
		$this->xml->loadXML($input);
		$this->xpath = new \DOMXpath($this->xml);
		$this->decodeGz64();
	}
	
	function parseTopology() {
		$this->parseDomains();
		$this->parseProviders();
	}
	
	function decodeGz64() {
		$contentNodes = $this->xpath->query("//content");
	
		foreach ($contentNodes as $contentNode) {
			if($contentNode->getAttribute("contentType") == "application/x-gzip" &&
				$contentNode->getAttribute('contentTransferEncoding') == "base64") {
				$contentDOM = new \DOMDocument();
				$contentDOM->loadXML(gzdecode(base64_decode($contentNode->nodeValue)));
				$xmlns = "http://schemas.ogf.org/nml/2013/05/base#";
				$tagName = "Topology";
				foreach ($contentDOM->getElementsByTagNameNS($xmlns, $tagName)
						as $netNode) {
					$node = $this->xml->importNode($netNode, true);
					$contentNode->nodeValue = "";
					$contentNode->removeAttribute("contentType");
					$contentNode->removeAttribute('contentTransferEncoding');
					$contentNode->appendChild($node);
				}
				$xmlns = "http://schemas.ogf.org/nsi/2014/02/discovery/nsa";
				$tagName = "nsa";
				foreach ($contentDOM->getElementsByTagNameNS($xmlns, $tagName)
						as $nsaNode) {
					$node = $this->xml->importNode($nsaNode, true);
					$contentNode->nodeValue = "";
					$contentNode->removeAttribute("contentType");
					$contentNode->removeAttribute('contentTransferEncoding');
					$contentNode->appendChild($node);
				}
	 		}
		}
	}

	function loadFile($url) {
		$this->url = $url;
		Yii::trace("loading file: ".$url);
		$ch = curl_init();

		$options = array(
				CURLOPT_RETURNTRANSFER => true,
				//CURLOPT_HEADER         => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,

				CURLOPT_USERAGENT => 'Meican',
				//CURLOPT_VERBOSE        => true,
				CURLOPT_URL => $this->url,
		);

		curl_setopt_array($ch , $options);

		$output = curl_exec($ch);
		curl_close($ch);
		//	echo $output;
		$this->loadXml($output);
	}
	
	function getLocation($domainName) {
		$ipAddress = gethostbyname($domainName);
		$response = null;

		if ($ipAddress != $domainName) {
			$details_url = "http://ipinfo.io/".gethostbyname($domainName);

			$response = json_decode(file_get_contents($details_url), true);
			//echo "ipinfo ";
			//var_dump($response);
		} else {
			//echo "domain does not exist";
		}

		if (!$response) {
			return array(
					'lng' => null,
					'lat' => null,
					'address'=> null
			);
		}

		$loc = explode(",",$response['loc']);
		$address = "";
		if ($response['org']) {
			$org = explode(" ", $response['org']);
			$org[0] = '';
			$org = implode(" ", $org);
			trim($org);
			$address = $org.", ";
				
			if ($org) {
				$geo = $this->queryGeocode($org);
				if ($geo) return $geo;
			}
		}

		$address .= isset($response['city']) ? $response['city'].", " : "";
		$address .= isset($response['region']) ? $response['region'].", " : "";
		$address .= isset($response['country']) ? $response['country'] : "";

		return ['lat'=>$loc[0], 'lng'=> $loc[1], 'address'=> $address];
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
		//echo "geocode ";
		//var_dump($response);
		// If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
		if ($response['status'] != 'OK') {
			return null;
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
		$this->xpath->registerNamespace('x', "http://schemas.ogf.org/nml/2013/05/base#");
		$netNodes = $this->xpath->query("//x:Topology");
		$i = 0;
		if ($netNodes) {
			foreach ($netNodes as $netNode) {
				$netId = $netNode->getAttribute('id');
				

				$id = explode(":", $netId);
				//         0   1     2         3        4    5
				//	      urn:ogf:network:cipo.rnp.br:2014::POA

				$domainName = $id[3];
				$geo = Domain::find()->where(['name'=>$domainName])->asArray()->one();
				if (!$geo) {
					$geo = $this->getLocation($domainName);
					Domain::createIfNew($domainName, $geo['lat'], $geo['lng'], $geo['address']);
					$i++;
				}
				
				$location = $netNode->appendChild($this->xml->createElement('location'));
				$lat = $location->appendChild($this->xml->createElement('latitude'));
				$lat->appendChild($this->xml->createTextNode($geo['lat']));
				$lng = $location->appendChild($this->xml->createElement('longitude'));
				$lng->appendChild($this->xml->createTextNode($geo['lng']));
				$address = $location->appendChild($this->xml->createElement('address'));
				$address->appendChild($this->xml->createTextNode(urlencode($geo['address'])));
				
				$this->parsePorts($netNode, $netId);
			}
			if ($i>3) {
				$i=0;
				sleep(1);
			}
		}
	}
	
	function parsePorts($netNode, $netId) {
		$biPortNodes = $this->xpath->query(".//x:BidirectionalPort", $netNode);
		if($biPortNodes) {
			foreach ($biPortNodes as $biPortNode) {
				$biPortId = $biPortNode->getAttribute('id');
				$id = explode(":", $netId);
				//         0   1     2         3        4    5
				//	      urn:ogf:network:cipo.rnp.br:2014::POA
				
				$domainName = $id[3];
				$devicePort = str_replace($netId.":", "", $biPortId);
				
				if (strpos($devicePort,'urn') !== false) {
				    $this->errors["Unknown URN"][$devicePort] = null;
				    return;
				}
				
				$devicePortArray = explode(":", $devicePort);
				if (count($devicePortArray) > 1) {
					$deviceName = $devicePortArray[0];
					$deviceId = $netId.":".$deviceName;
				} else {
					$deviceName = $domainName;
					$deviceId = $netId.":".$deviceName;
				}
				$deviceId = $netId.":".$deviceName;
				
				$portNodes = $this->xpath->query(".//x:PortGroup", $biPortNode);
				if($portNodes) {
					foreach ($portNodes as $portNode) {
						$this->addPortToDevice($portNode, $this->parseUniPortType($netNode,
								$portNode->getAttribute("id")), 
								$deviceId, 
								$deviceName, 
								$netNode, $domainName);
					}
				}
			}
		}
	}
	
	function addPortToDevice($portNode, $uniPortType, $deviceId, $deviceName, $netNode, 
			$domainName) {
		$deviceNodes = $this->xpath->query(".//x:Node", $netNode);
		if($deviceNodes) {
			foreach ($deviceNodes as $deviceNode) {
				if($deviceId == $deviceNode->getAttribute('id')) {
					$relationNodes = $this->xpath->query(".//x:Relation", $deviceNode);
					if($relationNodes) {
						foreach ($relationNodes as $relationNode) {
							if($relationNode->getAttribute("type") ==
									$uniPortType) {
								$relationNode->appendChild($portNode->cloneNode());
								return;
							}
						}
					}
					return;
				}
			}
		} 
		
		$deviceNode = $netNode->appendChild($this->xml->createElementNS('http://schemas.ogf.org/nml/2013/05/base#', 'Node'));
		$deviceNode->setAttribute("id", $deviceId);
			
		$name = $deviceNode->appendChild($this->xml->createElementNS('http://schemas.ogf.org/nml/2013/05/base#', 'name'));
		$name->appendChild($this->xml->createTextNode($deviceName));
		$relation = $deviceNode->appendChild($this->xml->createElementNS('http://schemas.ogf.org/nml/2013/05/base#', 'Relation'));
		$relation->setAttribute("type", "http://schemas.ogf.org/nml/2013/05/base#hasInboundPort");
		$relation = $deviceNode->appendChild($this->xml->createElementNS('http://schemas.ogf.org/nml/2013/05/base#', 'Relation'));
		$relation->setAttribute("type", "http://schemas.ogf.org/nml/2013/05/base#hasOutboundPort");
		$dev = Device::findOneByDomainAndNode($domainName, $deviceName);
		if($dev) {
			$location = $deviceNode->appendChild($this->xml->createElement('location'));
			$lat = $location->appendChild($this->xml->createElement('latitude'));
			$lat->appendChild($this->xml->createTextNode($dev['lat']));
			$lng = $location->appendChild($this->xml->createElement('longitude'));
			$lng->appendChild($this->xml->createTextNode($dev['lng']));
			$address = $location->appendChild($this->xml->createElement('address'));
			$address->appendChild($this->xml->createTextNode(urlencode($dev['address'])));
		}
		
		$relationNodes = $this->xpath->query(".//x:Relation", $deviceNode);
		if($relationNodes) {
			foreach ($relationNodes as $relationNode) {
				if($relationNode->getAttribute("type") ==
						$uniPortType) {
							$relationNode->appendChild($portNode->cloneNode());
							return;
						}
			}
		}
	}
	
	function parseUniPortType($netNode, $portId) {
		$relationNodes = $this->xpath->query(".//x:Relation", $netNode);
		foreach ($relationNodes as $relationNode) {
			$portNodes = $this->xpath->query(".//x:PortGroup", $relationNode);
			if($portNodes) {
				foreach ($portNodes as $portNode) {
					$id = $portNode->getAttribute('id');
	
					$temp = explode(":", $id);
					if ($temp[0] !== "urn") {
						$this->errors["Unknown URN"][$id] = null;
						continue;
					}
	
					if ($id === $portId) {
						if ($relationNode->getAttribute("type") == 
								"http://schemas.ogf.org/nml/2013/05/base#hasInboundPort") {
							return "http://schemas.ogf.org/nml/2013/05/base#hasInboundPort";
						} elseif ($relationNode->getAttribute("type") == 
								"http://schemas.ogf.org/nml/2013/05/base#hasOutboundPort") {
							return "http://schemas.ogf.org/nml/2013/05/base#hasOutboundPort";
						}
					}
				}
			}
		}
		return null;
	}

	function parseProviders() {
		$this->xpath->registerNamespace('x', 'http://schemas.ogf.org/nsi/2014/02/discovery/nsa');
		$nsaNodes = $this->xpath->query("//x:nsa");
		foreach ($nsaNodes as $nsaNode) {
			$idString = $nsaNode->getAttribute('id');
			$id = explode(":", $idString);
			$domainName = $id[3];
			$longitudeNode = $this->xpath->query(".//longitude", $nsaNode);
			$latitudeNode = $this->xpath->query(".//latitude", $nsaNode);
			$lat = null;
			$lng = null;
				
			if($longitudeNode->item(0)) {
				$lat = $latitudeNode->item(0)->nodeValue;
				$lng = $longitudeNode->item(0)->nodeValue;
				Domain::createIfNew($domainName, $lat, $lng);
			}
		}
	}
	
	function updateLocalProvider() {
		foreach ($this->xml->getElementsByTagNameNS('http://schemas.ogf.org/nsi/2014/02/discovery/types',
				'local') as $localNode) {
		    $docNode = $localNode->firstChild;
		    $docNode->setAttribute('id', 'urn:ogf:network:ufrgs.br:2015:nsa:proxy');
		    $docNode->setAttribute('href', 'http://143.54.12.80/proxyagg/web/topology/nsi');
		    $nsaId = $this->xpath->query(".//nsa", $docNode);
		    $nsaId->item(0)->nodeValue = 'urn:ogf:network:ufrgs.br:2015:nsa:proxy';
		    break;
		}
		
		$this->xpath->registerNamespace('x', 'http://schemas.ogf.org/nsi/2014/02/discovery/nsa');
		$nsaNodes = $this->xpath->query(".//x:nsa", $docNode);
		foreach ($nsaNodes as $nsaNode) {
			$nsaNode->setAttribute('id', 'urn:ogf:network:ufrgs.br:2015:nsa:proxy');
			$nameNode = $this->xpath->query(".//name", $nsaNode);
			if ($nameNode->item(0)) $nameNode->item(0)->nodeValue = "Proxy Aggregator";
			$versionNode = $this->xpath->query(".//softwareVersion", $nsaNode);
			if ($versionNode->item(0)) $versionNode->item(0)->nodeValue = "1.1";
			$adminNode = $this->xpath->query(".//adminContact", $nsaNode);
			if ($adminNode->item(0)) $adminNode->item(0)->removeChild($adminNode->item(0)->firstChild);
			
			$longitudeNode = $this->xpath->query(".//longitude", $nsaNode);
			if ($longitudeNode->item(0)) $longitudeNode->item(0)->nodeValue = "-51.1200936";

			$latitudeNode = $this->xpath->query(".//latitude", $nsaNode);
			if ($latitudeNode->item(0)) $latitudeNode->item(0)->nodeValue = "-30.0684507";
			
			$interfaceNodes = $this->xpath->query(".//interface", $nsaNode);
			foreach ($interfaceNodes as $interfaceNode) {
				$nsaNode->removeChild($interfaceNode);
			}
			
			$interface = $nsaNode->appendChild($this->xml->createElement('interface'));
			$type = $interface->appendChild($this->xml->createElement('type'));
			$type->appendChild($this->xml->createTextNode('application/vnd.ogf.nsi.dds.v1+xml'));
			$url = $interface->appendChild($this->xml->createElement('href'));
			$url->appendChild($this->xml->createTextNode(Url::toRoute(["/discovery"], true)));
			$interface = $nsaNode->appendChild($this->xml->createElement('interface'));
			$type = $interface->appendChild($this->xml->createElement('type'));
			$type->appendChild($this->xml->createTextNode('application/nmwg.topology+xml'));
			$url = $interface->appendChild($this->xml->createElement('href'));
			$url->appendChild($this->xml->createTextNode(Url::toRoute(["topology/nmwg"], true)));
			
			$peerNodes = $this->xpath->query(".//peersWith", $nsaNode);
			foreach ($peerNodes as $peerNode) {
				$nsaNode->removeChild($peerNode);
			}
			break;
		}
	}
}

?>