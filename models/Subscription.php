<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "device".
 *
 * @property integer $id
 * @property string $discoveryUrl
 * @property string $nsa
 */
class Subscription extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'subscription';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['discovery_url', 'nsa'], 'required'],
            [['nsa'], 'string', 'max' => 200],
        	[['discovery_url'], 'string'],
        	[['nsa'], 'unique']
        ];
    }
    
    static function findOneByNSA($nsa) {
    	self::find()->where(['nsa'=>$nsa])->one();
    }
}
