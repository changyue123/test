<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/23
 * Time: 10:36
 */

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
interface UPay
{
    /**
     * 支付查询接口
     * @param Request $request
     * @return mixed
     */
    function onlyInquire(Request $request);

    /**
     * 支付回调函数（UPay的回调函数）
     * @param Request $request
     * @return mixed
     */
    function onlyNotify(Request $request);

    /**
     * 支付处理接口
     * @param Request $request
     * @return mixed
     */
    function onlyDefray(Request $request);

    /**
     * 订单支付，金额最小单位为分
     * @param String $terminal_sn 终端号
     * @param String $client_sn 订单号
     * @param String $total_amount 总金额
     * @param String $dynamic_id 条码内容
     * @param String $subject 交易描述
     * @param String $operator 操作员
     * @param String $notify_url 回调地址
     * @return mixed
     */
    function pay($terminal_sn, $client_sn, $total_amount, $dynamic_id, $subject, $operator,$notify_url);

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
    function refund($terminal_sn, $client_sn, $client_tsn, $refund_request_no, $refund_amount, $operator);

    /**
     * 查询
     * @param String $terminal_sn 终端号
     * @param String $client_sn 订单号
     * @return mixed
     */
    function query($terminal_sn, $client_sn);

}