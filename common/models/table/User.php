<?php

namespace common\models\table;

use common\helpers\Helper;
use common\models\base\UserBase;
use yii\web\IdentityInterface;
use Yii;

class User extends UserBase implements IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return self::findOne(['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if (empty($token)) {
            return null;
        }
        return static::findOne(['token' => $token]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        //return $this->getAuthKey() == $authKey;
        return false;
    }

    /**
     * 验证密码
     *
     * @param $password
     * @return bool
     */
    public function validatePassword($password)
    {
        //return Yii::$app->getSecurity()->validatePassword($password, $this->password);
        //return $this->encryptPassword($password) == $this->password;
        return $password == $this->password;
    }

    /**
     * 加密密码
     *
     * @param $password
     * @return string
     * @throws \yii\base\Exception
     */
    public function encryptPassword($password)
    {
        //return Yii::$app->getSecurity()->generatePasswordHash($password);
        return md5($password);
    }

    /**
     * 设置密码
     *
     * @param $password
     * @throws \yii\base\Exception
     */
    public function setPassword($password)
    {
        $this->password = $this->encryptPassword($password);
    }

    /**
     * 验证码登陆，登陆后返回用户信息
     * @return array|false
     */
    public function login()
    {
        return [
            'id' => $this->id,
            'uid' => Helper::encryption($this->id),
            'name' => $this->name,
            'nick_name' => $this->nick_name,
            'mobile' => $this->mobile,
            'avatar' => $this->avatar,
            'introduce' => $this->introduce,
            'token' => $this->token,
            'is_expert' => $this->is_expert,
            'expert_label' => $this->expert_label,
            'full_avatar' => Helper::getImageUrl($this->avatar),
        ];
    }

    public function getIdByToken($token)
    {
        return User::find()->select(['id'])->where(['token' => $token, 'status' => 1])->scalar();
    }

    public function getExpertRank()
    {
        return $this->hasOne(ExpertRank::className(), ['user_id' => 'id'])->where(['type' => 9]);
    }
}
