<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "device".
 *
 * @property string $domain
 * @property string $node
 * @property double $lat
 * @property double $lng
 * @property string $address
 */
class Device extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'device';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['domain', 'node', 'lat', 'lng'], 'required'],
            [['lat', 'lng'], 'number'],
            [['domain'], 'string', 'max' => 60],
            [['node'], 'string', 'max' => 200],
            [['address'], 'string', 'max' => 250]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'domain' => 'Domain',
            'node' => 'Node',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'address' => 'Address',
        ];
    }
    
    static function createIfNew($domain, $node, $lat, $lng, $address=null) {
    	$dev = self::find()->where(['domain'=>$domain, 'node'=>$node])->one();
    	if (!$dev) {
    		$dev = new Device;
    		$dev->domain = $domain;
    		$dev->node = $node;
    		$dev->lat = $lat;
    		$dev->lng = $lng;
    		$dev->address = $address;
    		$dev->save();
    	}
    }
    
    static function findLocation($domainName, $deviceName) {
    	$dev = self::find()->where(
						['domain'=>$domainName,'node'=>$deviceName])->asArray()->one();
        if (!$dev) {
            $dom = Domain::find()->where(['name'=>$domainName])->asArray()->one();
            return $dom;
        }
        return $dev;
    }
}
