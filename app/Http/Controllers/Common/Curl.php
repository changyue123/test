<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 17-5-17
 * Time: 上午9:54
 */

namespace App\Http\Controllers\Common;

class Curl {
    //设置默认Options
    private static $defaultOptions = array(
        CURLOPT_HEADER => 0,
        CURLINFO_HEADER_OUT => 1,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => 1
    );
    //设置支持Type类型数组
    private static $types = array(
        'get' => array(),
        'post' => array(
            CURLOPT_POST => 1
        ),
        'put' => array(
            CURLOPT_CUSTOMREQUEST => 'PUT'
        ),
        'delete' => array(
            CURLOPT_CUSTOMREQUEST => 'DELETE'
        )
    );
    //设置需要传数据的类型
    private static $needSendData = array('post', 'put');
    //设置需要拼接Url的类型
    private static $needSpliceUrl = array('get', 'delete');

    /**
     * 获取CURL数据
     * @param $url
     * @param $data
     * @param string $type
     * @param array $headerData
     * @return mixed
     */
    public static function getData($url, $data, $type = 'get', $headerData = array()) {
        if (!array_key_exists($type, self::$types)) {
            //todo:返回错误code
        }
        $ch = curl_init();
        self::setHeadData($ch, $headerData);
        self::setOptions($ch, self::$types[$type]);
        self::setUrl($ch, $type, $url, $data);
        self::setData($ch, $type, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 设置Url
     * @param $ch
     * @param $type
     * @param $url
     * @param $data
     */
    public static function setUrl($ch, $type, $url, $data) {
        if (in_array($type, self::$needSpliceUrl)) {
            $url = env('JAVA_API') . $url . '?' . http_build_query($data);
        }
        self::setOptions($ch, array(
            CURLOPT_URL => $url
        ));
    }

    /**
     * 设置发送参数
     * @param $ch
     * @param $type
     * @param $data
     */
    public static function setData($ch, $type, $data) {
        if (in_array($type, self::$needSendData)) {
            if ($data && is_array($data)) {
                $data = json_encode($data);
            }
            self::setOptions($ch, array(
                CURLOPT_POSTFIELDS => $data
            ));
        }
    }

    /**
     * 设置Options
     * @param $ch
     * @param array $options
     */
    public static function setOptions($ch, array $options = array()) {
        if (!$options) {
            $options = self::$defaultOptions;
        };
        curl_setopt_array($ch, $options);
    }

    /**
     * 设置请求头
     * @param $ch
     * @param array $data
     */
    public static function setHeadData($ch, $data = array()) {
        $headerData = array();
        if ($data && is_array($data)) {
            foreach ($data as $value) {
                $queryString = $value;
                $headerData[] = $queryString;
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
    }
}