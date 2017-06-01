<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/18
 * Time: 9:42
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Common\Curl;
use App\Http\Controllers\Common\Log;
use App\Http\Controllers\Common\PayRedis;
use App\Http\Controllers\Common\Sign;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UPayController extends Controller implements UPay
{
    /**
     * 收钱吧接口地址
     */
    const U_PAY_URL = 'https://api.shouqianba.com';

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
     * 日志记录种类-info
     */
    const  LOG_INFO = 'info';

    /**
     * 日志记录种类-error
     */
    const  LOG_ERROR = 'error';

    /**
     * 日志记录种类-debug
     */
    const  LOG_DEBUG = 'debug';

    /**
     * 支付查询接口(支付中心的前端的支付回调)
     * @param Request $request
     * @return string
     */
    public function onlyInquire(Request $request){
        //编写支付信息验证规则（参考TestController@index）
        $rule = array(
            array('orderId', array('required' => true),  'required','orderId不可为空'),
        );
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
        $mResponse = $request -> all();
        //返回数据中有相应的流水号
        $orderId = $mResponse['biz_response']['data']['client_sn'];
        //返回状态至前端，这个地方支付中心进行判断，把状态写入redis中

        $mStatus = $mResponse['biz_response']['data']['order_status'];
        if ($mStatus == 'PAID') {
            //修改redis中的状态
            return PayRedis::updateOrderInfoById($orderId, self::ORDER_STATUS_SUCCESS);

        }  elseif ($mStatus == 'PAY_CANCELED'){
            //修改redis中的状态
            return PayRedis::updateOrderInfoById($orderId, self::ORDER_STATUS_FAILURE);

        }
        return false;
    }

    /**
     * 支付处理接口
     * @param Request $request
     * @return string
     */
    public function onlyDefray(Request $request){
        //编写支付信息验证规则（参考TestController@index）
        $rule = array(
            array('subject', array('required' => true), 'required', '简介必填'), //价格
            array('totalAmount', array('required' => true, 'number' => true), 'required', '金额必填'), //价格
            array('dynamicId', array('required' => true, 'number' => true), 'required', '二维码'), //价格
            array('payType', array('required' => true, 'number' => true), 'required', '支付方式'), //价格
            array('operator', array('required' => true, 'number' => true), 'required', '操作员'), //价格
            array('networkId', array('required' => true, 'number' => true), 'required', '校区网点ID'), //价格
            array('orderId', array('required' => true, 'number' => true), 'required', '订单ID'), //价格
            array('time', array('required' => true), 'required', '创建时间'), //价格
        );

        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        //记录Boss发送请求日志
        //获取订单号  支付中心流水号
        $orderId = $param['orderId'];
        //根据订单号查询之前存储的数据
        $orderInfo = PayRedis::getOrderInfoById($orderId);

        $mResponse = $this->pay($param['terminalSn'], $param['orderId'], $param['totalAmount'], $param['dynamicId'], $orderInfo['subject'], $orderInfo['operator'], $_SERVER["HTTP_HOST"].'/api/uPay/onlyNotify');

        //增加收钱吧返回信息日志记录info
        Log::setLog($mResponse, 'UPaydefray', self::LOG_INFO, 'response');//记录upay返回数据日志

        //返回状态至前端，这个地方支付中心进行判断，把状态写入redis中
        if ($mResponse['result_code'] == '200'){

            //修改订单状态为支付返回状态

            $mStatus = $mResponse['biz_response']['data']['order_status'];

            if ($mStatus == 'PAID') {

                //修改redis中的状态为 成功
                $orderInfo = PayRedis::updateOrderInfoById($orderId, self::ORDER_STATUS_SUCCESS);

                return $this->response('payIsApplied');
            } elseif ($mStatus == 'CREATED') {

                //修改redis中的状态为 支付中
                $orderInfo = PayRedis::updateOrderInfoById($orderId, self::ORDER_STATUS_UNPAID);
                return $this->response('payIsAction');

            } elseif ($mStatus == 'PAY_CANCELED'){
                //修改redis中的状态为 支付失败
                $status = PayRedis::updateOrderInfoById($orderId, self::ORDER_STATUS_FAILURE);
                return $this->response('tradeError', array('error_message'=>'success'));
            }
        } else {
            //此功能暂定
            //修改redis中的状态
            //$orderInfo = PayRedis::updateOrderInfoById($orderId, self::ORDER_STATUS_FAILURE);

            return $this->response('tradeError',array('error_message'=> '请联系工程师'));
        }
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

        $mData = array(
            'terminal_sn' => $terminal_sn,
            'client_sn' => $client_sn,
            'total_amount' => $total_amount,
            'dynamic_id' => $dynamic_id,
            'subject' => $subject,
            'operator' => $operator,
            'notify_url' => $notify_url
        );
        $mHeader  = Sign::getUPayHeader($mData);
        //增加收钱吧提交的信息 日志记录info
        Log::setLog($mData, 'UPaypay', self::LOG_INFO, 'request');//记录传递给upay的支付数据日志

        $mReponse = Curl::getData((self::U_PAY_URL).'/upay/v2/pay', $mData, 'post',$mHeader);
        return json_decode($mReponse, true);
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

    /*
     * 签到的判断和实现返回有效的头信息
     * */
    public function checkin(array $mData){

    }

}