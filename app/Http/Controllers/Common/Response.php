<?php

/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/17
 * Time: 15:57
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\Common\Log;

class Response {
    private static $handler;

    const LOG_SOURCE = 'http';

    const LOG_LEVEL = 'error';

    //SUCCESS CODE
    private static $success_code = array(
        'success' => array('code' => '200', 'message' => 'ok'),
        'payIsApplied' => array('code' => '100101', 'message' => '支付成功'),
        'payIsFinish' => array('code' => '100102', 'message' => '钱已到账'),
        'payIsAction' => array('code' => '100103', 'message' => '支付中')

    );
    /**
     * ERROR CODE
     * 10020X支付参数错误
     * 10030X订单错误
     * 10040X交易错误
     * 10050X操作类错误
     * 10060X服务类错误
     * 10070X设备类错误
     * @var array
     */
    private static $error_code = array(
        'testErrorId' => array('code' => '100201', 'message' => '验证ID失败'),
        'testErrorMobile' => array('code' => '100202', 'message' => '验证手机号失败'),
        'typeError' => array('code' => '100203', 'message' => '支付方式错误'),
        'priceError' => array('code' => '100204', 'message' => '支付金额有误'),
        'required' => array('code' => '100209', 'message' => '必填项不可为空'),
        'orderIdError' => array('code' => '100301', 'message' => '订单编号有误'),
        'orderNotFound' => array('code' => '100302', 'message' => '订单不存在'),
        'orderStatusError' => array('code' => '100303', 'message' => '订单状态错误'),
        'tradeError' => array('code' => '100401', 'message' => '交易失败'),
        'tradeUpdateFailed' => array('code' => '100402', 'message' => '交易状态更新失败'),
        'operationIsTooFast' => array('code' => '100501', 'message' => '操作过快'),
        'waitOnLine' => array('code' => '100502', 'message' => '排队中，请稍后'),
        'serviceIsNotWork' => array('code' => '100601', 'message' => '服务不可用'),
        'deviceSignFailure' => array('code' => '100701', 'message' => '设备注册失败'),
        'deviceActivateFailure' => array('code' => '100702', 'message' => '设备未激活'),
        'error' => array('code' => '100801', 'message' => '暂无上报数据'),
        'reportError' => array('code' => '100802', 'message' => '上报数据失败')
    );
    private static $response_data = array();

    /**
     * 回调函数
     * @param $callback
     */
    public static function setResponseHandler($callback) {
        self::$handler = $callback;
    }

    /**
     * Code验证
     * @param array $msg 提示Key
     * @return array
     */
    public static function validate($msg) {
        //自定义返回message
        if ($msg && is_array($msg)) {
            $message = $msg['msg'];
        } else {
            $message = $msg;
        }
        //获取返回信息
        if (array_key_exists($message, self::$response_data)) {
            var_dump(self::$response_data);
            //return self::$response_data[$message];
        }
        $data = array('isSuccess' => false);
        if (array_key_exists($message, self::$success_code)) {
            $data = self::$success_code[$message];
            $data['isSuccess'] = true;
        } else if (array_key_exists($message, self::$error_code)) {
            $data += self::$error_code[$message];
            Log::setLog($data, self::LOG_SOURCE, self::LOG_LEVEL, 'response');//记录请求发送日志
        } else {
            $data += array('code' => '000000', 'message' => '该CODE不存在，请补充');
        }
        //自定义返回message
        if (is_array($msg) && $msg['message']) {
            $data['message'] = $msg['message'];
        }
        //封装返回类型
        self::$response_data[$message] = $data;
        return $data;
    }

    /**
     * 处理返回数据
     * @param array $msg 提示信息
     * @param array $data 返回数据
     * @return string
     */
    public static function handle($msg, $data = array()) {
        //返回信息校验
        $rData = self::validate($msg);
        $rData['data'] = $data;
        //回调函数
        if (self::$handler) {
            call_user_func(self::$handler, $rData);
            exit(1);
        } else {
            return json_encode($rData, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * 格式化输出
     * @param $msg
     * @param array $data
     */
    public static function dump($msg, $data = array()) {
        if (is_array($msg)) {
            var_dump($msg);
        } else {
            echo '--' . $msg . '--';
        }
        if ($data) {
            var_dump($data);
        }
    }
}