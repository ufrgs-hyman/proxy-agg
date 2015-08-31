<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'mqg-proxy-agg',
    'name'=>'MQG Proxy Aggregator',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['debug'],
    'defaultRoute' => 'discovery',
    'modules' => [
	    'debug' => [
	    	'class' => 'yii\debug\Module',
	    	//'allowedIPs' => ['143.54.12.245']
	    ],
		'gii' => 'yii\gii\Module',
	],
    'components' => [
    	'request' => [
    		'cookieValidationKey' => 'asdadsd',
    	],
    	'urlManager' => [
	    	'class' => 'yii\web\UrlManager',
	    	'enablePrettyUrl' => true,
	    	'showScriptName' => false,
    		'rules' => [
    			'discovery/subscriptions/<id>' => 'discovery/subscriptions',
    		],
    	],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    		'user' => [
    				'identityClass' => 'app\models\User',
    				'enableAutoLogin' => true,
    				'loginUrl' => ['init/login']
    		],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'trace'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        'authManager' => [
	        'class' => 'yii\rbac\DbManager',
	        'defaultRoles' => ['guest'],
        ],
    ],
    'params' => $params,
];

return $config;
