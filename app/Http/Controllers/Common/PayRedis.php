<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/24
 * Time: 下午5:05
 */

namespace App\Http\Controllers\Common;

use Illuminate\Support\Facades\Redis;

class PayRedis
{
     //支付方式
    const PAY_WAYS_KEY = 'PAY_WAYS';
    //收钱吧相关Key前缀
    const U_PAY_PREFIX = 'U_PAY';
    //POS机相关Key前缀
    const POS_PREFIX = 'POS';
    //BOSS订单前缀
    const BOSS_ORDER_PREFIX = 'ORDER_BOSS';
    //校区网点Key
    const NETWORK_PREFIX = 'NETWORK';
    //设备信息前缀
    const DEVICE_PREFIX = 'DEVICE';
    //获取设备号
    const GET_ALL_TERMINAL_ID = 'GET_ALL_TERMINAL_ID';
    //获取设备密钥
    const GET_ALL_TERMINAL_KEY = 'GET_ALL_TERMINAL_KEY';
    //获取设备有效日期
    const GET_ALL_TERMINAL_TIME = 'GET_ALL_TERMINAL_TIME';
    //获取EFS上报最小日期
    const GET_MIN_DATE = 'GET_MIN_DATE';

    /**
     * 根据校区ID获取终端号
     * @param String $networkId 校区ID
     * @param String $payWay 支付方式
     */
    public static function getTerminalIdByNetworkId($networkId, $payWay){
        return Redis::hGet(self::GET_ALL_TERMINAL_ID . '_' . $payWay, $networkId);
}

    /**
     * 存储校区激活信息
     * @param String $networkId 网点ID
     * @param String $terminalId 终端号
     * @param String $terminalKey 终端密钥
     * @param String $payWay 支付方式
     * @return bool 存储状态
     */
    public static function setTerminalInfoByNetworkId($networkId, $terminalId, $terminalKey, $payWay){
        Redis::hSet(self::GET_ALL_TERMINAL_ID . '_' . $payWay, $networkId, $terminalId);
        Redis::hSet(self::GET_ALL_TERMINAL_KEY . '_' . $payWay, $terminalId, $terminalKey);
        Redis::hSet(self::GET_ALL_TERMINAL_TIME . '_' . $payWay, $terminalId, date('Ymd'));
        return true;
    }

    /**
     * 获取校区激活信息
     * @param $payWay
     * @return array
     */
    public static function getTerminalInfoByNetworkId($payWay){
        $data = array(self::GET_ALL_TERMINAL_ID . '_' . $payWay);
        $data['terminalId'] = Redis::hGetAll(self::GET_ALL_TERMINAL_ID . '_' . $payWay);
        $data['terminalKey'] = Redis::hGetAll(self::GET_ALL_TERMINAL_KEY . '_' . $payWay);
        $data['terminalTime'] = Redis::hGetAll(self::GET_ALL_TERMINAL_TIME . '_' . $payWay);
        return $data;
    }

    /**
     * 根据终端号获取终端Key
     * @param String $terminalId 终端号
     * @param String $payWay 支付方式
     * @return mixed
     */
    public static function getTerminalKeyById($terminalId, $payWay){
        return Redis::hGet(self::GET_ALL_TERMINAL_KEY . '_' . $payWay, $terminalId);
    }

    /**
     * 根据终端号获取签到时间
     * @param String $terminalId 终端号
     * @param String $payWay 支付方式
     */
    public static function getTerminalTimeById($terminalId, $payWay){
        return Redis::hGet(self::GET_ALL_TERMINAL_TIME . '_' . $payWay, $terminalId);
    }

    /**
     * 根据设备号获取终端信息
     * @param String $deviceId 网点ID
     * @param String $payWay 支付方式
     * @return array
     */
    public static function getTerminalInfo($deviceId, $payWay){
        $terminalId = Redis::hGet($payWay . '_' . self::GET_ALL_TERMINAL_ID, $deviceId);
        $terminalKey = Redis::hGet($payWay . '_' . self::GET_ALL_TERMINAL_KEY, $terminalId);
        return compact($terminalId, $terminalKey);
    }

    /**
     * 设置终端信息
     * @param String $deviceId 设备ID
     * @param String $terminalId 终端号
     * @param String $terminalKey 终端密钥
     * @param String $payWay 支付方式
     * @return mixed
     */
    public static function setTerminalInfo($deviceId, $terminalId, $terminalKey,$payWay){

        return Redis::hMSet($payWay . '_' . self::DEVICE_PREFIX . '_' . $deviceId, 'terminalId', $terminalId, 'terminalKey', $terminalKey, 'checkInTime', date('Y-m-d'));
    }

    /**
     * 禁用终端
     * @param String $deviceId 设备ID
     * @return mixed
     */
    public static function delTerminalInfo($deviceId, $payWay){
        return Redis::del($payWay . '_' . self::DEVICE_PREFIX . '_' . $deviceId);
    }

    /**
     * 按校区注册设备
     * @param String $networkId 网点ID
     * @param String $deviceId 设备ID
     * @param String $deviceName 设备名称
     * @param String $payWay 支付方式
     * @return mixed
     */
    public static function setDeviceInfo($networkId, $deviceId, $deviceName,$payWay){
        return Redis::hSet($payWay . '_' . self::NETWORK_PREFIX . '_' . $networkId, $deviceId, $deviceName);
    }

    /**
     * 校区设备注销
     * @param String $networkId 网点ID
     * @param String $deviceId 设备ID
     * @param String $payWay 支付方式
     */
    public static function delDeviceInfo($networkId, $deviceId,$payWay){
        return Redis::hDel($payWay . '_' . self::NETWORK_PREFIX . '_' . $networkId, $deviceId);
    }

    /**
     * 按校区和支付方式获取设备信息
     * @param $networkId
     * @param $payWay
     * @return mixed
     */
    public static function getDeviceInfo($networkId,$payWay){
        return Redis::hGetAll($payWay . '_' .  self::NETWORK_PREFIX . '_' . $networkId);
    }

    /**
     * 获取支付途径
     * @return mixed
     */
    public static function getPayWays(){
        Redis::del(self::PAY_WAYS_KEY);
        Redis::hMSet(self::PAY_WAYS_KEY, '100', '收钱吧');
        return Redis::hGetAll(self::PAY_WAYS_KEY);
    }

    /**
     * 存储订单信息
     * @param String $orderId 订单ID
     * @param String $serialNumber 支付流水号
     * @param String $totalAmount 总金额
     * @param String $dynamicId 二维码
     * @param String $payType 支付方式
     * @param String $status 支付状态
     * @param String $subject 订单介绍
     * @param String $operator 操作员ID
     * @param String $networkId 网点ID
     * @param String $createTime 提交时间
     * @param String $updateTime 更新时间
     * @return mixed
     */
    public static function setOrderInfo($orderId, $serialNumber, $totalAmount, $dynamicId, $payType, $status, $subject, $operator, $networkId, $createTime,$updateTime){
        return Redis::hMSet(self::BOSS_ORDER_PREFIX . '_' .$serialNumber,
            'orderId', $orderId,
            'serialNumber', $serialNumber,
            'totalAmount', $totalAmount,
            'dynamicId', $dynamicId,
            'payType', $payType,
            'status', $status,
            'subject', $subject,
            'operator', $operator,
            'networkId', $networkId,
            'createTime', $createTime,
            'updateTime', $updateTime
        );
    }

    /**
     * 根据订单ID获取订单信息
     * @param $orderId
     * @return array
     */
    public static function getOrderInfoById($orderId){
        return Redis::hGetAll(self::BOSS_ORDER_PREFIX . '_' . $orderId);
    }

    /**
     * 根据订单ID获取订单信息
     * @param $orderId
     * @return array
     */
    public static function updateOrderInfoById($orderId, $status){
        //$data = array('orderId' => $orderId);
        Redis::hSet(self::BOSS_ORDER_PREFIX . '_' . $orderId, 'status', $status);
        //更新数据更新时间
        Redis::hSet(self::BOSS_ORDER_PREFIX . '_' . $orderId, 'updateTime', $status);
    }

    /**
     * 获取支付终端签到日期
     * @param String $deviceId 设备ID
     * @param String $payWay 支付方式
     * @return mixed
     */
    public static function getTermCheckInDate($deviceId,$payWay){

        return Redis::get($payWay . '_' . self::DEVICE_PREFIX . '_' . $deviceId, 'checkInTime');
    }
    public static function setMinDate($networkId){
        return Redis::rPush(self::GET_MIN_DATE . '_' . $networkId, date('Y-m-d'));
    }

    public static function getMinDate($networkId){
        return Redis::lRange(self::GET_MIN_DATE . '_' . $networkId, date('Y-m-d'));
    }

    /**
     * 获取某网点下的所有时间戳
     * @param String $networkId 网点ID
     * @return Integer
     */
    public static function getReportMinDate($networkId){
        //计算该网点上报的最小日期
        $len = Redis::lLen(self::GET_MIN_DATE . $networkId);
        if($len == 0){
            Redis::lPush(self::GET_MIN_DATE . $networkId, '2017-05-24', '2017-05-25', '2017-05-26', '2017-05-27', '2017-05-28');
        }
        //return Redis::lLen(self::GET_MIN_DATE . $networkId);
        return Redis::lIndex(self::GET_MIN_DATE . $networkId, $len - 1);
    }

    /**
     * 网点收入状态更新
     * @param String $networkId 网点ID
     * @param Integer $date 更新日期
     * @return mixed
     */
    public static function updateReportStatus($networkId, $date){
        //修改该网点该日期的收入上报状态
        $len = Redis::lLen(self::GET_MIN_DATE . $networkId);
        if(Redis::lIndex(self::GET_MIN_DATE . $networkId, $len - 1) == $date){
            return Redis::rPop(self::GET_MIN_DATE . $networkId);
        }
    }
}