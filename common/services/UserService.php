<?php

namespace common\services;

use common\helpers\Helper;
use common\models\table\User;
use Yii;
use yii\helpers\ArrayHelper;


class UserService
{
    /**
     * 更新头像
     * @param $avatar
     * @return array
     */
    public static function updateAvatar($avatar)
    {
        $user_info = User::findOne(Yii::$app->user->id);

        if (empty($user_info)) {
            return [
                'code' => 101,
                'msg' => '该用户不存在'
            ];
        }

        $user_info->avatar = $avatar;

        if (!$user_info->save()) {
            return [
                'code' => 102,
                'msg' => current($user_info->getFirstErrors())
            ];
        }

        return [
            'code' => 0,
            'msg' => '修改成功',
        ];
    }

    /**
     * 更新昵称
     * @param $nick_name
     * @return array
     */
    public static function updateNickName($nick_name)
    {
        $user_info = User::findOne(Yii::$app->user->id);

        if (empty($user_info)) {
            return [
                'code' => 201,
                'msg' => '该用户不存在'
            ];
        }

        $user_info->nick_name = htmlspecialchars($nick_name);

        if (!$user_info->save()) {
            return [
                'code' => 202,
                'msg' => current($user_info->getFirstErrors())
            ];
        }

        return [
            'code' => 0,
            'msg' => '修改成功',
        ];
    }

    /**
     * 更新个人简介
     * @param $intro
     * @return array
     */
    public static function updateIntro($intro)
    {
        $user_info = User::findOne(Yii::$app->user->id);

        if (empty($user_info)) {
            return [
                'code' => 301,
                'msg' => '该用户不存在'
            ];
        }

        $user_info->introduce = htmlspecialchars($intro);

        if (!$user_info->save()) {
            return [
                'code' => 302,
                'msg' => current($user_info->getFirstErrors())
            ];
        }

        return [
            'code' => 0,
            'msg' => '修改成功',
        ];
    }

    /**
     * 更新密码
     * @param $odd_pass
     * @param $new_pass
     * @param $re_pass
     * @return array
     */
    public static function updatePassword($odd_pass, $new_pass, $re_pass)
    {
        if (empty($odd_pass) || empty($new_pass) || empty($re_pass)) {
            return [
                'code' => 301,
                'msg' => '信息请填写完整'
            ];
        }

        if ($new_pass != $re_pass) {
            return [
                'code' => 302,
                'msg' => '新密码与重复密码不一致'
            ];
        }

        $user_info = User::findOne(Yii::$app->user->id);

        if ($user_info->password != $odd_pass) {
            return [
                'code' => 303,
                'msg' => '原始密码错误'
            ];
        }

        $user_info->password = $new_pass;
        $user_info->token = Helper::uuid();

        if (!$user_info->save()) {
            return [
                'code' => 304,
                'msg' => current($user_info->getFirstErrors())
            ];
        }

        return [
            'code' => 0,
            'msg' => '更新成功'
        ];
    }

    /**
     * 修改手机号 - 第一步
     * @param $code
     * @return array
     */
    public static function updateMobileOne($code)
    {
        $user_info = User::findOne(Yii::$app->user->id);

        if (empty($user_info)) {
            return [
                'code' => 401,
                'msg' => '该用户不存在'
            ];
        }
        $codeCheck = CodeMsgService::checkCode($user_info->mobile, $code);

        if (!$codeCheck) {
            return [
                'code' => 402,
                'msg' => '验证码不正确'
            ];
        }

        // 缓存一个key值，第二步用到
        $redis = Yii::$app->redis;
        $key = Helper::randString($len = 8);
        $redis->set($key, json_encode([
            'user_id' => Yii::$app->user->id,
            'key' => $key,
        ]));
        $redis->expire($key, 300);

        return [
            'code' => 0,
            'msg' => '',
            'data' => [
                'key' => $key
            ]
        ];
    }

    /**
     * 修改手机号 - 第二步
     * @param $mobile
     * @param $code
     * @param $key
     * @return array
     */
    public static function updateMobileTwo($mobile, $code, $key)
    {
        $user_id = Yii::$app->user->id;
        $user_info = User::findOne($user_id);

        if (empty($user_info)) {
            return [
                'code' => 501,
                'msg' => '该用户不存在'
            ];
        }

        // 检测手机号是否被占用
        $exists = User::find()->where(['mobile' => $mobile, 'status' => 1])->exists();
        if ($exists) {
            return [
                'code' => 502,
                'msg' => '新的手机号已被占用'
            ];
        }

        if ($mobile == $user_info->mobile) {
            return [
                'code' => 503,
                'msg' => '新的手机号与原手机号相同'
            ];
        }

        $redis = Yii::$app->redis;
        $str = $redis->get($key);
        $validate_info = json_decode($str, true);

        if (ArrayHelper::getValue($validate_info, 'user_id') != $user_id ||
            ArrayHelper::getValue($validate_info, 'key') != $key
        ) {
            return [
                'code' => 504,
                'msg' => '参数错误，重试第一步'
            ];
        }

        $codeCheck = CodeMsgService::checkCode($mobile, $code);

        if (!$codeCheck) {
            return [
                'code' => 505,
                'msg' => '验证码不正确'
            ];
        }

        $user_info->mobile = $mobile;
        if (!$user_info->save()) {
            return [
                'code' => 506,
                'msg' => current($user_info->getFirstErrors())
            ];
        }

        return [
            'code' => 0,
            'msg' => '修改成功',
        ];
    }

    /**
     * 注销账号
     * @param $code
     * @return array
     */
    public static function cancel($code)
    {
        $user_info = User::findOne(Yii::$app->user->id);

        if (empty($user_info)) {
            return [
                'code' => 601,
                'msg' => '该用户不存在'
            ];
        }

        $codeCheck = CodeMsgService::checkCode($user_info->mobile, $code);

        if (!$codeCheck) {
            return [
                'code' => 602,
                'msg' => '验证码不正确'
            ];
        }

        $user_info->status = 0;
        $user_info->token = '';

        if (!$user_info->save()) {
            return [
                'code' => 603,
                'msg' => '操作失败'
            ];
        }

        return [
            'code' => 0,
            'msg' => '操作成功'
        ];
    }
}