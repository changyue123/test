<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/17
 * Time: 上午10:12
 */

namespace App\Http\Controllers\Common;


class Validator
{
    /**
     * Multi error list
     * @var array
     */
    private static $error = array();

    /**
     * 验证Validate
     * @param array $data
     * @param array $filter 数据过滤规则
     * @return bool
     */
    public static function execute(array $data, array $filter = array()){
        foreach ($filter as $detail) {
            /**
             * 1: field
             * 2: rules
             * 3: error message
             * 4: error code
             */
            list($field, $rules, $err_msg, $err_message) = $detail + array('', array(), '', '');

            $value = &$data[$field];
            $required = &$rules['required'];

            if ($value === null) {
                //null、true等
                if ($required !== false) {
                    self::$error = array(
                        'msg' => $err_msg,
                        'message' => $err_message
                    );
                }
                break;
            }

            unset($rules['required']);

            foreach ($rules as $m => $params) {
                if (!self::validate($value, $m, $params)) {
                    self::$error = array(
                        'msg' => $err_msg,
                        'message' => $err_message
                    );
                    break;
                }
            }
        }

        return !self::$error;
    }


    /**
     * Validate Filter
     * @param $data
     * @param $rule_type
     * @param $params
     * @return bool|mixed
     */
    public static function validate($data, $rule_type, $params){
        $func = "{$rule_type}Matcher";
        return self::$func($data, $params);
    }


    /**
     * @param $data
     * @param $pattern
     * @return mixed
     */
    public static function regexpMatcher($data, $pattern){
        return preg_match($pattern, $data);
    }


    /**
     * Validate IP address
     * @param $data
     * @return mixed
     */
    public static function ipMatcher($data){
        return filter_var($data, FILTER_VALIDATE_IP);
    }

    /**
     * Validate Email
     * @param $data
     * @return mixed
     */
    public static function emailMatcher($data){
        return filter_var($data, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate Mobile
     * @param $data
     * @return int
     */
    public static function mobileMatcher($data){
        return preg_match('/^1\d{10}$/', trim($data));
    }


    /**
     * Validate URL
     * @param $data
     * @return mixed
     */
    public static function urlMatcher($data){
        return filter_var($data, FILTER_VALIDATE_URL);
    }


    /**
     * Validate Int Type
     * @param $data
     * @return bool
     */
    public static function intMatcher($data){
        return is_int($data);
    }


    /**
     * Validate Float Type
     * @param $data
     * @return bool
     */
    public static function floatMatcher($data){
        return is_float($data);
    }


    /**
     * Validate Array Type
     * @param $data
     * @return bool
     */
    public static function arrayMatcher($data){
        return is_array($data);
    }


    /**
     * Validate Number
     * @param $data
     * @return bool
     */
    public static function numberMatcher($data){
        return is_numeric($data);
    }

    /**
     * Validate less than
     * @param $data
     * @param $target
     * @return bool
     */
    public static function ltMatcher($data, $target){
        return $data < $target;
    }

    /**
     * Validate less than and equal
     * @param $data
     * @param $target
     * @return bool
     */
    public static function eltMatcher($data, $target){
        return $data <= $target;
    }

    /**
     * Validate greater than
     * @param $data
     * @param $target
     * @return bool
     */
    public static function gtMatcher($data, $target){
        return $data > $target;
    }

    /**
     * Validate greater than and equal
     * @param $data
     * @param $target
     * @return bool
     */
    public static function egtMatcher($data, $target){
        return $data >= $target;
    }

    /**
     * Validate String length
     * @param $data
     * @param $len
     * @return bool
     */
    public static function egtLenMatcher($data, $len){
        return self::egtMatcher(mb_strlen($data, 'UTF-8'), $len);
    }

    /**
     * Validate String length
     * @param $data
     * @param $len
     * @return bool
     */
    public static function eltLenMatcher($data, $len){
        return self::eltMatcher(mb_strlen($data, 'UTF-8'), $len);
    }

    /**
     * Validate equal
     * @param $data
     * @param $target
     * @return bool
     */
    public static function eqMatcher($data, $target){
        return $data == $target;
    }

    /**
     * Validate not equal
     * @param $data
     * @param $target
     * @return bool
     */
    public static function neqMatcher($data, $target){
        return $data != $target;
    }

    /**
     * Validate in section
     * @param $data
     * @param array $target
     * @return bool
     */
    public static function inMatcher($data, array $target){
        return in_array($data, $target);
    }

    /**
     * Not Allow Empty String
     * @param $data
     * @return bool
     */
    public static function noEmptyMatcher($data){
        return !empty(trim($data));
    }

    /**
     * 回调函数
     * @param $data
     * @param $function
     * @return bool|mixed
     */
    public static function callbackMatcher($data, $function){
        if (is_callable($function)) {
            return call_user_func($function, $data);
        }
        return false;
    }

    /**
     * 获取校验错误信息
     * @return bool|array
     */
    public static function getError(){
        return self::$error;
    }
}