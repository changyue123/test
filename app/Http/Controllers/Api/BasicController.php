<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/18
 * Time: 9:42
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Common\PayRedis;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BasicController extends Controller implements UPay
{
    /**
     * 订单支付状态-待支付
     */
    const ORDER_STATUS_UNPAID = 0;

    /**
     * 订单支付状态-支付成功
     */
    const ORDER_STATUS_SUCCESS = 1;

    /**
     * 订单支付状态-支付失败
     */
    const ORDER_STATUS_FAILURE = -1;

    /**
     * 支付查询接口(支付中心的前端的支付回调)
     * @param Request $request
     * @return string
     */
    public function onlyInquire(Request $request){
        //编写支付信息验证规则（参考TestController@index）
        $rule = array(''=>'...');
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        //根据流水号获取订单信息
        $orderInfo = PayRedis::getOrderInfoById($param['orderId']);
        //返回订单支付状态
        return $this->response('success',array('status'=> $orderInfo['status']));
    }

    /**
     * 支付回调函数（UPay的回调函数）
     * @param Request $request
     * @return string
     */
    public function onlyNotify(Request $request){
        //编写支付信息验证规则（参考TestController@index）
        $rule = array(''=>'...');
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        //修改订单状态（此处的订单状态为支付流水号）
        PayRedis::updateOrderInfoById($param['orderId']);
        return $this->response('success',array());
    }

    /**
     * 支付处理接口
     * @param Request $request
     * @return string
     */
    public function onlyDefray(Request $request){
        //编写支付信息验证规则（参考TestController@index）
        $rule = array(''=>'...');
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        //获取二维码信息
        $dynamicId = $param['dynamicId'];
        //获取订单号
        $orderId = $param['orderId'];
        //根据订单号查询之前存储的数据
        $orderInfo = PayRedis::getOrderInfoById($orderId);
        //todo:封装数据给UPay

        //$data = $this->pay($terminal_sn, $client_sn, $total_amount, $dynamic_id, $subject, $operator);
        //修改订单状态为支付返回状态
        //返回状态至前端
        return $this->response('success',array('status'=>'success'));
    }

    /**
     * 订单支付，金额最小单位为分
     * @param String $terminal_sn 终端号
     * @param String $client_sn 订单号
     * @param String $total_amount 总金额
     * @param String $dynamic_id 条码内容
     * @param String $subject 交易描述
     * @param String $operator 操作员
     * @param String $notify_url 回调接口地址Url
     * @return mixed
     */
    public function pay($terminal_sn, $client_sn, $total_amount, $dynamic_id, $subject, $operator, $notify_url){

        $this->dump('pay');
    }

    /**
     * 退款
     * @param String $terminal_sn 终端号
     * @param String $client_sn 订单号
     * @param String $client_tsn 退款订单号
     * @param String $refund_request_no 退款序列号
     * @param String $refund_amount 退款金额
     * @param String $operator 操作员
     * @return mixed
     */
    public function refund($terminal_sn, $client_sn, $client_tsn, $refund_request_no, $refund_amount, $operator){

        $this->dump('refund');
    }

    /**
     * 查询
     * @param String $terminal_sn 终端号
     * @param String $client_sn 订单号
     * @return mixed
     */
    public function query($terminal_sn, $client_sn){

        $this->dump('query');
    }
}