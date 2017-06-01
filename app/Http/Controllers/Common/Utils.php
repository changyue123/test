<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/22
 * Time: 13:57
 */

namespace App\Http\Controllers\Common;

class Utils
{
    /**
     * 获取散列值
     * @param $key
     * @param $mod
     * @return int
     */
    public static function getModHashValue($key, $mod){
        $crc = abs(hexdec(hash('crc32b', $key))); //无32、64 bit区别
        return fmod($crc, $mod) + 1;
    }


    /**
     * 获取随机字符串
     * @param int    $num
     * @param string $type
     * @return string
     */
    public static function genRandStr($num = 6, $type = NULL){
        $number = '0123456789';
        $letter = 'abcdefghijklmnopqrstuvwxyz';
        if ($type == 'number') {
            $chars = $number;
        } elseif ($type == 'letter') {
            $chars = $letter;
        } else {
            $chars = $number . $letter;
        }
        $chars_max = strlen($chars) - 1;
        $str = [];
        while ($num-- > 0) {
            $str[] = $chars[mt_rand(0, $chars_max)];
        }
        return implode('', $str);
    }

    /**
     *
     * @param $param
     * @return string
     */
    public static function dataFormat($param, $view = false){
        $data = array();
        foreach($param as $key=>$v){
            $data[] = array('key' => $key, 'value' => $v);
        }
        if($view){
            return json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        }
        return $data;
    }

    /**
     * 网点ID转换
     * @param $networkId
     * @return string
     */
    public static function networkFormat($networkId){
        return '1' . date('Y') . $networkId;
    }
}