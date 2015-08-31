<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "domain".
 *
 * @property string $name
 * @property double $lat
 * @property double $lng
 * @property string $address
 */
class Domain extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'domain';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'lat', 'lng'], 'required'],
            [['lat', 'lng'], 'number'],
            [['name'], 'string', 'max' => 60],
            [['address'], 'string', 'max' => 250]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Name',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'address' => 'Address',
        ];
    }
    
    static function createIfNew($name, $lat, $lng, $address=null) {
    	$dom = self::find()->where(['name'=>$name])->one();
    	if (!$dom) {
    		$dom = new Domain;
    		$dom->name = $name;
    		$dom->lat = $lat;
    		$dom->lng = $lng;
    		$dom->address = $address;
    		$dom->save();
    	}
    }
}
