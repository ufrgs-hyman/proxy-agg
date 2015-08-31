<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use app\models\UserDomainRole;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property integer $id
 * @property string $login
 * @property string $password
 * @property string $authkey
 *
 */
class User extends \yii\db\ActiveRecord implements IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['login', 'password', 'authkey'], 'required'],
            [['login'], 'string', 'max' => 30],
            [['password'], 'string', 'max' => 200],
            [['authkey'], 'string', 'max' => 100],
            [['login'], 'unique'],
            [['authkey'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'login' => Yii::t('aaa', 'User'),
            'password' => Yii::t('aaa', 'Password'),
            'authkey' => 'Authkey',
            'name' => Yii::t('aaa', 'Name'),
        ];
    }

    public static function findByUsername($username) {
    	return static::findOne(['login' => $username]);
    }
    
    public static function findIdentity($id) {
    	return static::findOne($id);
    }
    
    public static function findIdentityByAccessToken($token, $type = null) {
    	return static::findOne(['authkey' => $token]);
    }
    
    public function getId() {
    	return $this->id;
    }
    
    public function getAuthKey() {
    	return $this->authkey;
    }
    
    public function validateAuthKey($authKey) {
    	return $this->authkey === $authKey;
    }
    
    public function isValidPassword($password) {
    	if(Yii::$app->getSecurity()->validatePassword($password, $this->password)) {
    		return true;
    	} else {
    		return false;
    	}
    }
}
