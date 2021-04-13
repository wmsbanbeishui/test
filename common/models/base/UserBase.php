<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id id
 * @property string $name 用户名
 * @property string $real_name 真实名
 * @property string $nick_name 昵称
 * @property int $mobile 手机号
 * @property string $password 密码
 * @property string $token 用户token
 * @property string $avatar 头像
 * @property string $introduce 介绍
 * @property int $is_expert 是否是专家(0-否；1-是)
 * @property int $expert_type 专家类型(1-数字专家；2-篮球专家；3-足球专家)
 * @property string $expert_label 专家标签id串(1-竞彩足球；2-足彩；3-竞彩篮球；4-盘口分析；5-大乐透；6-双色球；7-排列三；8-排列五；9-其他)
 * @property int $is_hot 是否热门专家(0-否；1-是)
 * @property string $id_card_no 身份证号码
 * @property string $id_card_face 身份证正面照url
 * @property string $id_card_back 身份证背面照url
 * @property int $like_num 点赞数量
 * @property int $balance 账户余额(单位分)
 * @property int $status 状态(0-注销；1-正常)
 * @property string $cid 设备id
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class UserBase extends \common\extensions\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mobile', 'password'], 'required'],
            [['mobile', 'is_expert', 'expert_type', 'is_hot', 'like_num', 'balance', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name', 'password', 'cid'], 'string', 'max' => 64],
            [['real_name'], 'string', 'max' => 16],
            [['nick_name'], 'string', 'max' => 32],
            [['token'], 'string', 'max' => 50],
            [['avatar', 'introduce', 'expert_label', 'id_card_face', 'id_card_back'], 'string', 'max' => 255],
            [['id_card_no'], 'string', 'max' => 20],
            [['name'], 'unique'],
            [['nick_name'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'name' => '用户名',
            'real_name' => '真实名',
            'nick_name' => '昵称',
            'mobile' => '手机号',
            'password' => '密码',
            'token' => '用户token',
            'avatar' => '头像',
            'introduce' => '介绍',
            'is_expert' => '是否是专家(0-否；1-是)',
            'expert_type' => '专家类型(1-数字专家；2-篮球专家；3-足球专家)',
            'expert_label' => '专家标签id串(1-竞彩足球；2-足彩；3-竞彩篮球；4-盘口分析；5-大乐透；6-双色球；7-排列三；8-排列五；9-其他)',
            'is_hot' => '是否热门专家(0-否；1-是)',
            'id_card_no' => '身份证号码',
            'id_card_face' => '身份证正面照url',
            'id_card_back' => '身份证背面照url',
            'like_num' => '点赞数量',
            'balance' => '账户余额(单位分)',
            'status' => '状态(0-注销；1-正常)',
            'cid' => '设备id',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }
}
