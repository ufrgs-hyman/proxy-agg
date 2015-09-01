<?php

namespace app\controllers;

use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\NSIProxy;
use app\models\NMWGProxy;
use Yii;

class TopologyController extends Controller {
	
	public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['?'],
                    ],
        		],
        	],
        ];
    }
    
    public function actionNsi() {
    	$proxy = new NSIProxy;
		$proxy->loadFile('http://localhost/rnptopo2.xml');
		$proxy->parseTopology();
		return $proxy->xml->saveXML();
    }
    
    public function actionNmwg() {
    	$topology = new NMWGProxy(Yii::$app->params['source.nmwg']);
    	$topology->loadFile();
    	return $topology->xml->saveXML();
    }
}
