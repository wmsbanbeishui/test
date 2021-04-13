<?php

namespace common\helpers;

use common\models\table\AuthMenu;
use Yii;
use yii\log\FileTarget;
use yii\helpers\ArrayHelper;

class Helper
{
    public static $curl = null;
    public static $sys_menu_id = null;

    public static $wxcfg = [];

    /**
     * 获取参数
     *
     * @param $key
     * @param $default
     * @return array|string
     */
    public static function getParam($key, $default = null)
    {
        return ArrayHelper::getValue(Yii::$app->params, $key, $default);
    }

    /**
     * 设置参数
     *
     * @param $key
     * @param $value
     */
    public static function setParam($key, $value)
    {
        Yii::$app->params[$key] = $value;
    }

    /**
     * 获取取当前管理后台菜单ID
     * @return integer
     */
    public static function get_menu_id()
    {
        if (isset(self::$sys_menu_id)) {
            return self::$sys_menu_id;
        }
        $route = '/' . Yii::$app->controller->route;
        $menu = AuthMenu::findOne(['auth_name' => $route]);
        $menu_id = $menu ? $menu->menu_id : 0;
        self::$sys_menu_id = $menu_id;
        return $menu_id;
    }

    /**
     * redis读取数据
     * @param $key
     * @param $default
     * @return mixed|null
     */
    public static function redis_get($key, $default = null)
    {
        /** @var \yii\redis\Connection $redis */
        $redis = Yii::$app->get('redis');
        $prefix = self::getParam('redis_key_prefix');
        if ($prefix) {
            $key = $prefix . $key;
        }
        $value = $redis->get($key);
        if ($value === null) {
            return $default;
        }
        if (is_string($value) && !is_numeric($value)) {
            $unserialize_value = @unserialize($value);
            if ($unserialize_value !== false) {
                return $unserialize_value;
            }
        }
        return $value;
    }

    /**
     * redis写入数据
     *
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    public static function redis_set($key, $value, $expire = null)
    {
        /** @var \yii\redis\Connection $redis */
        $redis = Yii::$app->get('redis');
        if (is_null($expire)) {
            $expire = self::getParam('redis_key_timeout');
        }
        if (is_null($expire) || !is_int($expire)) {
            $expire = 3600;
        }
        $prefix = self::getParam('redis_key_prefix');
        if ($prefix) {
            $key = $prefix . $key;
        }
        if (is_bool($value)) {
            $value = intval($value);
        }
        if (!is_scalar($value)) {
            $value = serialize($value);
        }
        return $redis->executeCommand('SET', [$key, $value, 'EX', $expire]);
    }

    /**
     * redis删除数据
     *
     * @param $key
     * @return bool
     */
    public static function redis_del($key)
    {
        /** @var \yii\redis\Connection $redis */
        $redis = Yii::$app->get('redis');
        $prefix = self::getParam('redis_key_prefix');
        if ($prefix) {
            $key = $prefix . $key;
        }
        return $redis->executeCommand('DEL', [$key]);
    }

    /**
     * 写日志文件
     * @param $content
     * @param string $logName
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\log\LogRuntimeException
     */
    public static function fLogs($content, $logName = 'log.log')
    {
        $time = microtime(true);
        $log = new FileTarget();
        $log->logFile = Yii::$app->getRuntimePath() . '/logs/' . $logName;
        $log->messages[] = [$content, 1, 'application', $time];
        $log->export();
    }

    /**
     * @param $value
     * @return string|array
     */
    public static function unifyLimiter($value)
    {
        return str_replace([' ', '　', '，', '、', "\n"], ',', $value);
    }

    /**
     * 设置输出内容格式为json
     */
    public static function json_output($data = null, $exit = true)
    {
        if (!$data) {
            Yii::$app->response->format = 'json';
            return;
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!$exit) {
            return $data;
        }
        echo $data;
        exit(0);
    }

    /**
     * CURL发送http请求
     * @return array|string|object
     */
    public static function curl($url, $data = [], $method = 'get', $params = [], $debug = false)
    {
        if (!self::$curl) {
            self::$curl = curl_init();
        }
        $ch = self::$curl;

        if ($method == 'get' && !empty($data)) {
            $params = $data;
        }
        if ($params) {
            $p = parse_url($url);
            if (!empty($p['query'])) {
                parse_str($p['query'], $query_data);
                if (is_array($query_data)) {
                    $params = $params + $query_data;
                }
            }
            $url = '';
            if (isset($p['scheme'])) {
                $url .= $p['scheme'] . '://';
            }
            if (isset($p['host'])) {
                $url .= $p['host'];
            }
            if (isset($p['path'])) {
                $url .= $p['path'];
            }
            $url .= '?' . http_build_query($params);
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
        ];
        if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == 'NMC_BETA') {
            $options[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
        }
        if ($method == 'post' && $data) {
            $options[CURLOPT_POSTFIELDS] = $data;
            if (!is_array($data)) {
                $options[CURLOPT_HTTPHEADER] = ['Content-Type: text/plain'];
            }
        }
        if ($debug) {
            echo '$options<pre>';
            print_r($options);
            echo '</pre><hr>';
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if ($response === false) {
            $result = [
                'code' => curl_errno($ch),
                'msg' => curl_error($ch),
            ];
            return $result;
        }

        $result = json_decode($response, true);
        if (is_null($result)) {
            return [
                'code' => 500,
                'msg' => 'parse to json error',
                'response' => $response,
            ];
        }

        return $result;
    }

    public static function curlPostJson($url, $data)
    {
        if (!self::$curl) {
            self::$curl = curl_init();
        }
        $ch = self::$curl;

        $method = 'post';
        $debug = true;

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
        ];
        if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == 'NMC_BETA') {
            $options[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
        }
        if ($method == 'post' && $data) {
            $options[CURLOPT_POSTFIELDS] = $data;
            if (!is_array($data)) {
                $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json;charset=utf-8'];
            }
        }
        if ($debug) {
            echo '$options<pre>';
            print_r($options);
            echo '</pre><hr>';
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if ($response === false) {
            $result = [
                'errno' => curl_errno($ch),
                'errmsg' => curl_error($ch),
            ];
            return $result;
        }

        $result = json_decode($response, true);
        if (is_null($result)) {
            return [
                'errno' => 500,
                'errmsg' => 'parse to json error',
                'response' => $response,
            ];
        }

        return $result;
    }

    /**
     * 设置Header CORS
     */
    public static function set_cors()
    {
        $headers = Yii::$app->response->headers;
        $headers->set('Access-Control-Allow-Origin', 'http://capi.foxcode.cn');
        $headers->set('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Connection, User-Agent, Cookie, Authorization');
        $headers->set('Access-Control-Allow-Credentials', 'true');

        $referer = null;
        $http_referer = null;
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $http_referer = $_SERVER['HTTP_REFERER'];
        }
        if (!$http_referer && !empty($_SERVER['HTTP_ORIGIN'])) {
            $http_referer = $_SERVER['HTTP_ORIGIN'];
        }
        if ($http_referer) {
            $referer = parse_url($http_referer);
            $referer = $referer + ['host' => null, 'scheme' => null];
            $referer_host = $referer['host'];
            if (!empty($referer['port'])) {
                $referer_host = $referer['host'] . ':' . $referer['port'];
            }
        }
        $cors_white_hosts = self::getParam('cors_white_hosts');

        if ($referer && is_array($cors_white_hosts)) {
            foreach ($cors_white_hosts as $host) {
                if (substr($referer['host'], -strlen($host)) == $host) {
                    $headers->set('Access-Control-Allow-Origin', $referer['scheme'] . '://' . $referer_host);
                    break;
                }
            }
        }


        if (YII_ENV != 'prod') {
            $headers->set('Access-Control-Allow-Origin', '*');
            if (!empty($referer_host)) {
                $headers->set('Access-Control-Allow-Origin', $referer['scheme'] . '://' . $referer_host);
            }
        }
    }

    /**
     * MD5签名
     * @return string
     */
    public function sign($data, $secret, $ignore_keys = ['sign'])
    {
        foreach ($ignore_keys as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }
        ksort($data);
        foreach ($data as $k => $v) {
            $data[$k] = urlencode($v);
        }
        $sign = md5(implode('&', $data) . $secret);
        return $sign;
    }

    /**
     * 检查是否为微信客户端
     * @return bool
     */
    public static function is_wx_client()
    {
        // Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1 wechatdevtools/0.7.0 MicroMessenger/6.3.9 Language/zh_CN webview/0
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger/')) {
            return true;
        }
        return false;
    }

    /**
     * 检查是否为微信小程序
     * @return bool
     */
    public static function is_wx_mini_program()
    {
        $request = Yii::$app->getRequest();

        if (YII_ENV_DEV) {
            return true;
        }

        $userAgent = $request->getUserAgent();
        Helper::fLogs($userAgent, 'wx_login.log');
        if ($userAgent) {
            if (
                substr_count($userAgent, 'MicroMessenger') == 2
                || strpos($userAgent, 'miniProgram') !== false
            ) {
                return true;
            }
        }
        $referrer = $request->getReferrer();
        Helper::fLogs($referrer, 'wx_login.log');
        if ($referrer) {
            return strpos($referrer, 'servicewechat.com') !== false;
        }
        return false;
    }

    /**
     * 获取随机名称
     */
    public static function getRandName($last_name = [], $suffix = '团友')
    {
        if (!$last_name) {
            $last_name = '
				赵 钱 孙 李 周 吴 郑 王 冯 陈 褚 卫
				蒋 沈 韩 杨 朱 秦 尤 许 何 吕 施 张
				孔 曹 严 华 金 魏 陶 姜 戚 谢 邹 喻
				柏 水 窦 章 云 苏 潘 葛 奚 范 彭 郎
				鲁 韦 昌 马 苗 凤 花 方 俞 任 袁 柳
				酆 鲍 史 唐 费 廉 岑 薛 雷 贺 倪 汤
				滕 殷 罗 毕 郝 邬 安 常 乐 于 时 傅
				皮 卞 齐 康 伍 余 元 卜 顾 孟 平 黄
				和 穆 萧 尹 姚 邵 湛 汪 祁 毛 禹 狄
				米 贝 明 臧 计 伏 成 戴 谈 宋 茅 庞';
            $last_name = trim($last_name);
            $last_name = str_replace(["\n", "\t"], [' ', null], $last_name);
            $last_name = explode(' ', $last_name);
        }
        return $last_name[array_rand($last_name)] . $suffix;
    }

    /**
     * 获取随机时间
     */
    public static function getRandTime($last_minute = null, $start_date = null, $end_date = null, $start_hour = 8, $end_hour = 22)
    {
        $hour = rand($start_hour, $end_hour);
        if (!$start_date) {
            $start_date = strtotime(date('Y-m-d') . ' -3day');
        }
        if (!$end_date) {
            $end_date = strtotime('last day of this month');
        }
        $minute = rand(0, 59);
        if ($last_minute) {
            $last_minute = rand(1, $last_minute);
            $start_date = $end_date = strtotime('today');
            $hour = date('H');
            $minute = date('i') - $last_minute;
            if ($minute < 0) {
                $minute += 60;
                $hour--;
            }
        }
        $day = rand($start_date, $end_date);
        $day = date('Y-m-d ', $day);
        return $day . $hour . ':' . $minute . ':' . rand(0, 59);
    }

    /**
     * 获取隐藏四位电话号码
     */
    public static function getMaskMobile($mobile = null)
    {
        if ($mobile) {
            return substr($mobile, 0, 3) . '****' . substr($mobile, -2);
        }
        $prefix_list = [132, 134, 135, 136, 137, 150, 159, 180, 189];
        return $prefix_list[array_rand($prefix_list)] . '******' . rand(10, 99);
    }

    /**
     * 获取随机字符
     * @param int $len
     * @param int $level
     * @return string
     */
    public static function randString($len = 8)
    {
        $str = 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';

        $length = strlen($str) - 1;
        $rand_str = '';

        for ($i = 0; $i < $len; $i++) {
            $rand_str .= $str[mt_rand(0, $length)];
        }
        return $rand_str;
    }

    public static function uuid($prefix = '')
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-';
        $uuid .= substr($chars, 8, 4) . '-';
        $uuid .= substr($chars, 12, 4) . '-';
        $uuid .= substr($chars, 16, 4) . '-';
        $uuid .= substr($chars, 20, 12);
        return $prefix . $uuid;
    }

    public static function uuid2($prefix = '')
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-';
        $uuid .= substr($chars, 8, 4) . '-';
        $uuid .= substr($chars, 12, 4) . '-';
        $uuid .= substr($chars, 16, 4);
        return $prefix . $uuid;
    }

    /**
     * 获取微信的相关配置信息
     * @param null $key
     * @param null $target
     * @return string|array
     */
    public static function get_wx_cfg($key = null)
    {
        if (!Helper::$wxcfg) {
            Helper::$wxcfg = [
                'app_id' => Yii::$app->params['wx_app_id'],
                'app_secret' => Yii::$app->params['wx_app_secret'],
            ];
        }

        if ($key) {
            return isset(Helper::$wxcfg[$key]) ? Helper::$wxcfg[$key] : null;
        }

        return Helper::$wxcfg;
    }

    /**
     * 获取两个指定日期之间的所有日期
     * @param $start
     * @param $end
     * @return array
     */
    public static function getDates($start, $end)
    {
        $dt_start = strtotime($start);
        $dt_end = strtotime($end);

        $date_list = [];
        while ($dt_start <= $dt_end) {
            $date_list[] = date('Y-m-d', $dt_start);
            $dt_start = strtotime('+1 day', $dt_start);
        }

        return $date_list;
    }

    /**
     * 获取两个日期之间的天数差
     * @param $start
     * @param $end
     * @return float|int
     */
    public static function getDiffDays($start, $end)
    {
        $dt_start = strtotime($start);
        $dt_end = strtotime($end);

        return ($dt_end - $dt_start) / (3600 * 24);
    }

    /**
     * 生成指定长度随机字符串
     * @param int $length
     * @param bool $numberOnly
     * @return string
     */
    public static function generateRandomString($length = 32, $numberOnly = false)
    {
        $char = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($numberOnly) {
            $char = '0123456789';
        }
        $charLength = strlen($char) - 1;
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $char[rand(0, $charLength)];
        }
        return $str;
    }

    /**
     * 响应微信支付通知
     * @param bool $success
     * @return string|void
     */
    public static function wxpay_notify_return($success = true, $exit = true)
    {
        $return_code = $success ? 'SUCCESS' : 'FAIL';
        $result = "<xml><return_code><![CDATA[$return_code]]></return_code></xml>";
        if ($exit) {
            echo $result;
            exit(0);
        }

        return $result;
    }

    /**
     * 生成微信订单号
     * @param null $mch_id
     * @param null $prefix
     * @param null $suffix
     * @return string
     */
    public static function gen_order_no($mch_id = null, $prefix = null, $suffix = null)
    {
        if (!$mch_id) {
            require_once Yii::getAlias('@common/WxPay/lib/WxPay.Api.php');
            $mch_id = \WxPayConfig::MCHID;
        }

        return $prefix . $mch_id . date('YmdHis') . rand(100, 999) . $suffix;
    }

    /**
     * 生成支付宝订单号
     * @param null $mch_id
     * @param null $prefix
     * @param null $suffix
     * @return string
     */
    public static function ali_order_no($prefix = null, $suffix = null)
    {
        if (!$prefix) {
            $config = Helper::getParam('alipay');
            $prefix = substr($config['app_id'], 0, 6);
        }

        return $prefix . date('YmdHis') . rand(100, 999) . $suffix;
    }

    /**
     * 获取当前http请求域名
     * @return string
     */
    public static function get_request_host($host = null, $request_scheme = null)
    {
        if (!$host) {
            $host = $_SERVER['HTTP_HOST'];
        }
        if (!$request_scheme) {
            $request_scheme = $_SERVER['REQUEST_SCHEME'];
        }
        return $request_scheme . '://' . $host;
    }

    /**
     * 关闭调试模式
     */
    public static function DebugToolbarOff()
    {
        if (class_exists('\yii\debug\Module')) {
            Yii::$app->view->off(\yii\web\View::EVENT_END_BODY, [\yii\debug\Module::getInstance(), 'renderToolbar']);
        }
    }

    /**
     * 获取图片完整url
     * @param $img
     * @return string
     */
    public static function getImageUrl($img)
    {
        if (empty($img)) {
            return '';
        }

        $img_host = Helper::getParam('image_host');

        if (substr($img, 0, 7) == 'http://' ||
            substr($img, 0, 8) == 'https://' ||
            substr($img, 0, 2) == '//'
        ) {
            return $img;
        }

        // 接口调试临时加的
        if (in_array($_SERVER['HTTP_HOST'], ['192.168.2.170:8085'])) {
            $img_host = '192.168.2.170:8084';
        }

        $request_scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';

        return $request_scheme . '://' . $img_host . $img;
    }

    /**
     * 根据第几周获取当周的开始日期与最后日期
     * @param $year
     * @param $week_num
     * @return array
     */
    public static function getWeekDate($year, $week_num)
    {
        $first_day_of_year = mktime(0, 0, 0, 1, 1, $year);
        $first_week_day = date('N', $first_day_of_year);
        $first_week_num = date('W', $first_day_of_year);
        if ($first_week_num == 1) {
            $day = (1 - ($first_week_day - 1)) + 7 * ($week_num - 1);
            $start_date = date('Y-m-d', mktime(0, 0, 0, 1, $day, $year));
            $end_date = date('Y-m-d', mktime(0, 0, 0, 1, $day + 6, $year));
        } else {
            $day = (9 - $first_week_day) + 7 * ($week_num - 1);
            $start_date = date('Y-m-d', mktime(0, 0, 0, 1, $day, $year));
            $end_date = date('Y-m-d', mktime(0, 0, 0, 1, $day + 6, $year));
        }

        return [
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }

    /**
     * 根据日期获取星期
     * @param $date
     * @return string
     */
    public static function getWeekByDate($date)
    {
        $week_array = ["日", "一", "二", "三", "四", "五", "六"];

        return "星期" . $week_array[date("w", strtotime($date))];
    }

    /**
     * 字符串加解密
     * @param $string
     * @param string $operation
     * @return bool|false|string|string[]
     */
    public static function encryption($string, $operation = 'ENCODE')
    {
        $key = '^&%$*Hy#t^*&H&^SK))';
        $key_length = strlen($key);
        if ($key_length == 0) {
            return false;
        }

        $string = $operation == 'DECODE' ? base64_decode(str_replace('-', '/', $string)) :
            substr(md5($string . $key), 0, 8) . $string;
        $string_length = strlen($string);

        $rndkey = $box = array();
        $result = '';

        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        //以上的过程就是加、解密的过程。只要key不变，($box[($box[$a] + $box[$j]) % 256])都是唯一的值，
        //加密时当1^0时变成了1，解密时1^0自然变成了1,或者这样说，加密时0^1变成1,解密时1^1就变成了0.

        if ($operation == 'DECODE') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return str_replace(array('=', '/'), array('', '-'), base64_encode($result));
        }
    }

    /**
     * 获取当前毫秒时间戳
     * @return mixed|string
     */
    public static function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}
