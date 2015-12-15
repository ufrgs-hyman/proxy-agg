<?php

namespace app\controllers;

use yii\web\Controller;
use Yii;
use app\models\NSIProxy;
use app\models\Subscription;

class DiscoveryController extends Controller {
	
	public $enableCsrfValidation = false;
    
    public function actionIndex() {
    	$proxy = new NSIProxy;
    	$proxy->loadFile(Yii::$app->params['source.discovery']);
    	$proxy->updateLocalProvider();
    	$proxy->parseTopology();
    	Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
		return $proxy->xml->saveXML();
    }  
    					
    public function actionSubscriptions($id=null) {
    	if (Yii::$app->request->isPost) {
    		$xml = new \DOMDocument();
    		$xml->loadXML(Yii::$app->request->getRawBody());
    		$xpath = new \DOMXpath($xml);
    		
	    	foreach ($xml->getElementsByTagNameNS('http://schemas.ogf.org/nsi/2014/02/discovery/types',
					'subscriptionRequest') as $subXml) {
				$nsaNode = $xpath->query(".//requesterId", $subXml);
				$nsaId = $nsaNode->item(0)->nodeValue;
				
				if(!($sub = Subscription::findOneByNSA($nsaId))) {
					$sub = new Subscription;
					$sub->nsa = $nsaId;
					$url = $xpath->query(".//callback", $subXml);
					$sub->discovery_url = $url->item(0)->nodeValue;
					if(!$sub->save()) return false;
				}
				
				$xml = new \DOMDocument();
				$xml->loadXML('<?xml version="1.0" encoding="UTF-8"?><ns0:subscription xmlns:ns0="http://schemas.ogf.org/nsi/2014/02/discovery/types" id="ea948ede-7f7b-4e36-b2ef-e235a235c88e" href="" version="2015-07-08T15:36:33.075-03:00"><requesterId>urn:ogf:network:es.net:2013:nsa:nsi-aggr-west</requesterId><callback>https://nsi-aggr-west.es.net/discovery/notifications</callback><filter><include><event>All</event></include></filter></ns0:subscription>');
				$xpath = new \DOMXpath($xml);
				
				foreach ($xml->getElementsByTagNameNS('http://schemas.ogf.org/nsi/2014/02/discovery/types',
						'subscription') as $subXml) {
				
					$subXml->setAttribute('id', $sub->id);
					$nsaId = $xpath->query(".//requesterId", $subXml);
					$nsaId->item(0)->nodeValue = $sub->nsa;
					$url = $xpath->query(".//callback", $subXml);
					$url->item(0)->nodeValue = $sub->discovery_url;
					return $xml->saveXML();
				}
	    	}
    	}
    	
    	if($id) {
    		$sub = Subscription::findOne($id);
    		if(!$sub) return '';
    		if (Yii::$app->request->isDelete) {
    			$sub->delete();
    			return '';
    		}
    		$xml = new \DOMDocument();
    		$xml->loadXML('<?xml version="1.0" encoding="UTF-8"?><ns0:subscription xmlns:ns0="http://schemas.ogf.org/nsi/2014/02/discovery/types" id="ea948ede-7f7b-4e36-b2ef-e235a235c88e" href="" version="2015-07-08T15:36:33.075-03:00"><requesterId>urn:ogf:network:es.net:2013:nsa:nsi-aggr-west</requesterId><callback>https://nsi-aggr-west.es.net/discovery/notifications</callback><filter><include><event>All</event></include></filter></ns0:subscription>');
    		$xpath = new \DOMXpath($xml);
    		
    		foreach ($xml->getElementsByTagNameNS('http://schemas.ogf.org/nsi/2014/02/discovery/types',
    				'subscription') as $subXml) {
    		
    			$subXml->setAttribute('id', $sub->id);
    			$nsaId = $xpath->query(".//requesterId", $subXml);
    			$nsaId->item(0)->nodeValue = $sub->nsa;
    			$url = $xpath->query(".//callback", $subXml);
    			$url->item(0)->nodeValue = $sub->discovery_url;
    			return $xml->saveXML();
    		}
    		 
    	} else {
    		$subsXml = '';
    		 
    		foreach (Subscription::find()->all() as $sub) {
    			$xml = new \DOMDocument();
    			$xml->loadXML('<?xml version="1.0" encoding="UTF-8"?><ns0:subscription xmlns:ns0="http://schemas.ogf.org/nsi/2014/02/discovery/types" id="ea948ede-7f7b-4e36-b2ef-e235a235c88e" href="" version="2015-07-08T15:36:33.075-03:00"><requesterId>urn:ogf:network:es.net:2013:nsa:nsi-aggr-west</requesterId><callback>https://nsi-aggr-west.es.net/discovery/notifications</callback><filter><include><event>All</event></include></filter></ns0:subscription>');
    			$xpath = new \DOMXpath($xml);
    		
    			foreach ($xml->getElementsByTagNameNS('http://schemas.ogf.org/nsi/2014/02/discovery/types',
    					'subscription') as $subXml) {
    		
    				$subXml->setAttribute('id', $sub->id);
    				$nsaId = $xpath->query(".//requesterId", $subXml);
    				$nsaId->item(0)->nodeValue = $sub->nsa;
    				$url = $xpath->query(".//callback", $subXml);
    				$url->item(0)->nodeValue = $sub->discovery_url;
    				$subsXml = $subsXml.$xml->saveHTML();
    			}
    		}
    		 
    		return '<?xml version="1.0" encoding="UTF-8"?><ns0:subscriptions xmlns:ns0="http://schemas.ogf.org/nsi/2014/02/discovery/types">'.
    				$subsXml.
    				'</ns0:subscriptions>';
    	}
    }

    public function actionNotify() {
    	$proxy = new NSIProxy;
    	$proxy->loadFile('https://agg.cipo.rnp.br/dds/documents?id=urn:ogf:network:cipo.rnp.br:2013:');
    	$proxy->parseTopology();
    	
    	$message = '<?xml version="1.0" encoding="UTF-8"?>'.
    		'<tns:notifications xmlns:tns="http://schemas.ogf.org/nsi/2014/02/discovery/types" '.
    		'providerId="urn:ogf:network:ufrgs.br:2015:nsa:proxy" '.
    		'id="6" href="">'.
    		'<tns:notification>'.
    		'<discovered>none</discovered>'.
    		'<event>Update</event>'.
    		$proxy->xml->saveHTML().
    		'</tns:notification>'.
    		'</tns:notifications>';
    	
    	$xml = new \DOMDocument();
    	$xml->loadXML($message);
    	
    	$ch = curl_init();
    	
    	foreach (Subscription::find()->asArray()->all() as $sub) {
    		foreach ($xml->getElementsByTagNameNS('http://schemas.ogf.org/nsi/2014/02/discovery/types',
    				'notifications') as $subXml) {
    			$subXml->setAttribute('id', $sub['id']);
    		}
    		
    		Yii::trace($xml->saveXML());
    		
    		$options = array(
    			CURLOPT_RETURNTRANSFER => true,
    			CURLOPT_SSL_VERIFYHOST => false,
    			CURLOPT_SSL_VERIFYPEER => false,
    			CURLOPT_POST 			=> 1,
    			CURLOPT_POSTFIELDS 	=> $xml->saveXML(),
    			CURLOPT_HTTPHEADER => array(
    				'Accept-encoding: application/xml;charset=utf-8',
    				'Content-Type: application/xml;charset=utf-8'),
    			CURLOPT_USERAGENT => 'Meican',
    			CURLOPT_URL => $sub['discovery_url'],
    		);
    		
    		curl_setopt_array($ch , $options);
    		
    		$output = curl_exec($ch);
    	}
    	
    	curl_close($ch);
    	
    	return "";
    }
    
    public function actionNotification() {
        http_response_code(202);
        
        return "";
    }
    
    public function actionRegister() {
        $r = new \HttpRequest('https://agg.cipo.rnp.br/dds/subscriptions', \HttpRequest::METH_POST);
        $r->addHeaders(array(
                'Accept-encoding' => 'application/xml;charset=utf-8',
                'Content-Type' => 'application/xml;charset=utf-8'));
        $r->setBody('<?xml version="1.0" encoding="UTF-8"?><tns:subscriptionRequest xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                    xmlns:tns="http://schemas.ogf.org/nsi/2014/02/discovery/types">
                <requesterId>urn:ogf:network:cipo.ufrgs.br:2014:nsa:meican</requesterId>
                <callback>http://meican-cipo.inf.ufrgs.br/meican2/web/topology/service/discovery/notification</callback>
                <filter>
                    <include>
                        <event>All</event>
                    </include>
                </filter>
            </tns:subscriptionRequest>');
        
        $r->send();
        
        Yii::trace($r->getResponseCode()." ".$r->getResponseBody());
        return "";
    }
}

?>
