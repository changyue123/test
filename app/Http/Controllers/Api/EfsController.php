<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/24
 * Time: 下午9:43
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Common\Curl;
use App\Http\Controllers\Common\PayRedis;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EfsController extends Controller implements Efs {
    /**
     * 获取收入上报最小日期
     * @param Request $request
     * @return mixed
     */
    public function OnlyReport(Request $request) {
        //编写网点信息验证规则（参考TestController@index）
        $rule = array(
            array('Income_network', array('required' => true, 'number' => true), 'required', '网点id不可为空'), //网点id
            array('date', array('required' => true), 'required', '日期不可为空'), //日期
        );
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if (!$param)
            return $this->response($this->getError());
        //获取收入上报数据
        $data = $this->report($param['Income_network'], $param['date']);
        //java服务不可用
        if ($data == 100601) {
            //返回信息
            return $this->response('serviceIsNotWork', []);
        } else {
            //是否有上报数据
            if (empty($data)) {
                //返回信息
                return $this->response('error', $data);
            } else {
                //返回信息
                return $this->response('success', $data);
            }
        }
    }

    /**
     * 获取收入日报数据
     * @param Request $request
     * @return mixed
     */
    public function getReportMinDate(Request $request) {
        //编写网点信息验证规则（参考TestController@index）
        $rule = array(
            array('Income_network', array('required' => true, 'number' => true), 'required', '网点id不可为空')    //网点id
        );
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if (!$param)
            return $this->response($this->getError());
        //获取最小上报日期
        $date = $this->getDate($param['Income_network']);
        //返回信息
        return $this->response('success', array('SignUpTime', $date));
    }

    /**
     * 上报确认
     * @param Request $request
     * @return mixed
     */
    public function OnlyReportNotify(Request $request) {
        //编写网点信息验证规则（参考TestController@index）
        $rule = array(
            array('Income_network', array('required' => true, 'number' => true), 'required', '网点id不可为空'), //网点id
            array('date', array('required' => true), 'required', '日期不可为空'), //日期
        );
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if (!$param)
            return $this->response($this->getError());
        $res = $this->notify($param['Income_network'], $param['date']);
        if($res){
            return $this->response('success');
        }
        return $this->response('reportError');
    }

    /**
     * 根据网点获取最小日期
     * @param String $incomeNetWork 网点ID
     * @return mixed
     */
    public function getDate($incomeNetWork) {
        return PayRedis::getReportMinDate($incomeNetWork);
    }

    /**
     * 获取收入上报数据
     * @param String $incomeNetWork 网点ID
     * @param String $date 上报日期
     * @return mixed
     */
    public function report($incomeNetWork, $date) {
        $data = array();
        //获取并处理收入上报数据
        //默认返回结构及数据
        $mDefaultData1 = [
            "Invoice_ID" => "",                    //单据号
            "Old_Invoice_ID" => "",            //原报单据号   收费时同单据号
            "Type_Code" => "",           //单据类型
            "Type_Name" => "",                                   //单据类型名称
            "SignUp_Time" => "",                                //业务日期
            "BMIncome_Network" => $incomeNetWork,                             //财务收款网点id  同入参Income_network相同
            "JXIncome_Network" => $incomeNetWork,                             //财务教学网点id   （BOS暂时没有跨校区报名，所以数据同上）
            "Student_ID" => "",                                           //学员id
            "Student_Name" => "",                                    //学员姓名
            "Item_ID" => 0,                                             //项目id
            "Item_Name" => "",                                             //项目名称
            "Course_ID" => 0,                                              //课程id
            "Course_Name" => "",                                      //课程名称
            "Class_ID" => 0,                                                //班级id
            "Class_Name" => "",                                          //班级名称
            "Charge_Nature_ID" => 1,                              //收款性质id
            "Charge_Nature_Name" => "现收",              //收款性质名称
            "Charge_Contents_ID" => 0,                        //班级业务的收费内容id
            "Charge_Contents_Name" => "",                   //收费内容名称
            "Income_Flag" => 1,                        //是否算收入
            "Payable_Money" => 0,                                       //应缴金额（不含税）
            "Paid_Money" => 0,                                              //实收金额（不含税）
            "Deduction_Money" => 0,                                          //使用的预收金额（不含税）
            "Deduction_Money_Remain" => 0,                          //使用的延期金额（不含税）
            "Deduction_Money_Card" => 0,                               //使用的充值卡金额（不含税）
            "PersonNum" => 1,                                                     //报名班级课目或课时类型的人次
            "Information_ID" => "",                                             //学员此时在这个校区的当前信息渠道ID
            "Payable_Money_Total" => 0,                          //应缴金额（含税）（合同金额）
            "Paid_Money_Total" => 0,                                 //实收金额（含税）（当次实际收的金额）
            "Deduction_Money_Total" => 0,                             //使用的预收金额（含税）
            "Remain_Money_Total" => 0,                                 //使用的延期金额（含税）
            "Card_Money_Total" => 0,                                      //使用的充值卡金额（含税）
            "Tax_Rate" => 0,                                                       //增值税率（根据“收入网点" +“收费内容”-增值税率）
            "Card_Money_Tax" => 0,                                        //使用的充值卡金额的税额
            "Deduction_Money_Tax" => 0,                              //使用的预收金额的税额
            "Tax_Money" => 0,                                                  //税额——除使用充值卡和使用预收金额以外其他金额的税额
            "Card_IsBusinessTax" => 1,                                 //使用的充值卡金额的税种
            "Deduction_IsBusinessTax" => 1,                         //使用的预收金额的税种
            "IsBusinessTax" => 1,
        ];
        $mDefaultData2 = [
            "Invoice_ID" => "",              //单据号
            "Charge_Time" => "",    //收款日期
            "Charge_Manner_ID" => 8000,            //收款方式id    (收钱吧，收款方式表新增)
            "Charge_Manner_Name" => "收钱吧",           //收款方式名称
            "Money" => 0,              //收款金额
            "Invoice_Flag" => 0,             //是否需要换票（0-不开发票；1-开发票）
            "ChargeUsed_CardNumber" => ""        //如果是使用银行卡或消费卡的，则记录客户使用的卡号
        ];
        //where
        $mWhere = [
            'netId' => $incomeNetWork,
            'date' => $date
        ];

        //合同
        $mContract = json_decode(Curl::getData('/efs-report/contract', $mWhere), true);
        //预收
        $mPrepay = json_decode(Curl::getData('/efs-report/prepay', $mWhere), true);
        //其他
        $mOther = json_decode(Curl::getData('/efs-report/other', $mWhere), true);

        //缴费金额  差额
        $mSpread = $mInvoicePaymentAmount = [];
        $index = 0;
        //单据号前缀
        $invoice_prefix = 'BOSS';
        //判断合同,预收,其他接口是否都可用
        if (!$mContract || !isset($mContract['isSuccess']) || $mContract['isSuccess'] != true) {
            return 100601;
        }
        if (!$mPrepay || !isset($mPrepay['isSuccess']) || $mPrepay['isSuccess'] != true) {
            return 100601;
        }
        if (!$mOther || !isset($mOther['isSuccess']) || $mOther['isSuccess'] != true) {
            return 100601;
        }
        //合同
        if (isset($mContract['data']) && !empty($mContract['data'])) {
            foreach ($mContract['data'] as $invoiceK => $invoiceV) {
                //字符串转换int
                if (isset($invoiceV['Item_ID'])) {
                    $mContract['data'][$invoiceK]['Item_ID'] = intval($invoiceV['Item_ID']);
                }
                if (isset($invoiceV['Course_ID'])) {
                    $mContract['data'][$invoiceK]['Course_ID'] = intval($invoiceV['Course_ID']);
                }
                if (isset($invoiceV['Student_ID'])) {
                    $mContract['data'][$invoiceK]['Student_ID'] = intval($invoiceV['Student_ID']);
                }
                if (isset($invoiceV['Information_ID'])) {
                    $mContract['data'][$invoiceK]['Information_ID'] = intval($invoiceV['Information_ID']);
                }
                if (isset($invoiceV['Invoice_ID'])) {
                    $mContract['data'][$invoiceK]['Invoice_ID'] = $invoice_prefix . $invoiceV['Invoice_ID'];
                }
                //缴费金额
                $mInvoicePaymentAmount[$invoice_prefix . $invoiceV['Invoice_ID']] = $invoiceV['payment_amount'];
                //差额
                $mSpread[$invoice_prefix . $invoiceV['Invoice_ID']] = 0;
            }

            foreach ($mContract['data'] as $conK => $conV) {
                //赋值给返回数组
                $data['data1'][$index] = $mDefaultData1;
                $data['data2'][$index] = $mDefaultData2;

                //获取所有要返回key
                $mDefaultData1Copy = array_keys($mDefaultData1);
                $mDefaultData2Copy = array_keys($mDefaultData2);
                //重新赋值
                foreach ($mDefaultData1Copy as $d1K) {
                    if (array_key_exists($d1K, $conV)) {
                        $data['data1'][$index][$d1K] = $conV[$d1K];
                    }
                }
                foreach ($mDefaultData2Copy as $d2K) {
                    if (array_key_exists($d2K, $conV)) {
                        $data['data2'][$index][$d2K] = $conV[$d2K];
                    }
                }
                //单项金额
                $mMoney = ($conV['payment_amount'] * $conV['prc_amount']) / $conV['prc_original_amount'];
                //差额
                $mSpread[$conV['Invoice_ID']] = $mSpread[$conV['Invoice_ID']] + intval(round($mMoney));
                //原报名单据号
                $data['data1'][$index]['Old_Invoice_ID'] = $conV['Invoice_ID'];
                //单据类型
                $data['data1'][$index]['Type_Code'] = '15';
                $data['data1'][$index]['Type_Name'] = '报课单';
                //应缴实缴金额
                $data['data1'][$index]['Payable_Money_Total'] = intval(round($mMoney));
                $data['data1'][$index]['Paid_Money_Total'] = intval(round($mMoney));
                $data['data2'][$index]['Money'] = intval(round($mMoney));
                //收款性质
                $data['data1'][$index]['Charge_Nature_ID'] = 1;
                $data['data1'][$index]['Charge_Nature_Name'] = '现收';
                //班级业务的收费内容
                $data['data1'][$index]['Charge_Contents_ID'] = 29;
                $data['data1'][$index]['Charge_Contents_Name'] = '学费';

                $index = $index + 1;
            }

            //是否存在差额
            foreach ($mSpread as $spreadK => $spreadV) {
                if ($mSpread[$spreadK] != $mInvoicePaymentAmount[$spreadK]) {
                    $mSpread[$spreadK] = $mInvoicePaymentAmount[$spreadK] - $mSpread[$spreadK];
                } else {
                    unset($mSpread[$spreadK]);
                }
            }

            //减掉差额
            if (!empty($mSpread)) {
                foreach ($mSpread as $spK => $spV) {
                    $true = true;
                    foreach ($data['data1'] as $dataK => $dataV) {
                        if ($spK == $dataV['Invoice_ID']) {
                            if ($true == true) {
                                $true = false;
                                $data['data1'][$dataK]['Payable_Money_Total'] = $data['data1'][$dataK]['Payable_Money_Total'] + $spV;
                                $data['data1'][$dataK]['Paid_Money_Total'] = $data['data1'][$dataK]['Paid_Money_Total'] + $spV;
                                $data['data2'][$dataK]['Money'] = $data['data1'][$dataK]['Paid_Money_Total'];
                                unset($mSpread[$spK]);
                                continue;
                            }
                        }
                        continue;
                    }
                }
            }

        }

        //预收
        if (isset($mPrepay['data']) && !empty($mPrepay['data'])) {
            foreach ($mPrepay['data'] as $prepayK => $prepayV) {
                $data['data1'][$index] = $mDefaultData1;
                $data['data2'][$index] = $mDefaultData2;
                //获取所有要返回key
                $mDefaultData1Copy = array_keys($mDefaultData1);
                $mDefaultData2Copy = array_keys($mDefaultData2);
                //重新赋值
                foreach ($mDefaultData1Copy as $d1K) {
                    if (array_key_exists($d1K, $prepayV)) {
                        $data['data1'][$index][$d1K] = $prepayV[$d1K];
                    }
                }
                foreach ($mDefaultData2Copy as $d2K) {
                    if (array_key_exists($d2K, $prepayV)) {
                        $data['data2'][$index][$d2K] = $prepayV[$d2K];
                    }
                }
                //字符串转换int
                if (isset($prepayV['Student_ID'])) {
                    $data['data1'][$index]['Student_ID'] = intval($prepayV['Student_ID']);
                }
                if (isset($prepayV['Information_ID'])) {
                    $data['data1'][$index]['Information_ID'] = intval($prepayV['Information_ID']);
                }
                if (isset($prepayV['Invoice_ID'])) {
                    $data['data1'][$index]['Invoice_ID'] = $invoice_prefix . $prepayV['Invoice_ID'];
                }
                //原报名单据号
                $data['data1'][$index]['Old_Invoice_ID'] = $invoice_prefix . $prepayV['Invoice_ID'];
                //单据类型
                $data['data1'][$index]['Type_Code'] = '9';
                $data['data1'][$index]['Type_Name'] = '预收单';
                //收款性质
                $data['data1'][$index]['Charge_Nature_ID'] = 4;
                $data['data1'][$index]['Charge_Nature_Name'] = '预收';
                $data['data2'][$index]['Invoice_ID'] = $invoice_prefix . $prepayV['Invoice_ID'];
                $data['data2'][$index]['Money'] = $prepayV['Paid_Money_Total'];
                $index = $index + 1;

            }
        }
        //其他
        if (isset($mOther['data']) && !empty($mOther['data'])) {
            foreach ($mOther['data'] as $otherK => $otherV) {
                $data['data1'][$index] = $mDefaultData1;
                $data['data2'][$index] = $mDefaultData2;
                //获取所有要返回key
                $mDefaultData1Copy = array_keys($mDefaultData1);
                $mDefaultData2Copy = array_keys($mDefaultData2);
                //重新赋值
                foreach ($mDefaultData1Copy as $d1K) {
                    if (array_key_exists($d1K, $otherV)) {
                        $data['data1'][$index][$d1K] = $otherV[$d1K];
                    }
                }
                foreach ($mDefaultData2Copy as $d2K) {
                    if (array_key_exists($d2K, $otherV)) {
                        $data['data2'][$index][$d2K] = $otherV[$d2K];
                    }
                }
                //字符串转换int
                if (isset($otherV['Student_ID'])) {
                    $data['data1'][$index]['Student_ID'] = intval($otherV['Student_ID']);
                }
                if (isset($otherV['Information_ID'])) {
                    $data['data1'][$index]['Information_ID'] = intval($otherV['Information_ID']);
                }
                if (isset($otherV['Invoice_ID'])) {
                    $data['data1'][$index]['Invoice_ID'] = $invoice_prefix . $otherV['Invoice_ID'];
                }
                //原报名单据号
                $data['data1'][$index]['Old_Invoice_ID'] = $invoice_prefix . $otherV['Invoice_ID'];
                //单据类型
                $data['data1'][$index]['Type_Code'] = 'Q';
                $data['data1'][$index]['Type_Name'] = '其他收费单';
                $data['data2'][$index]['Invoice_ID'] = $invoice_prefix . $otherV['Invoice_ID'];
                $data['data2'][$index]['Money'] = $otherV['Paid_Money_Total'];
                $index = $index + 1;

            }
        }

        return $data;
    }

    /**
     * 财务回调接口（上报确认）
     * @param String $incomeNetWork 网点ID
     * @param String $date 上报日期
     * @return mixed
     */
    public function notify($incomeNetWork, $date) {
        //更新该网点下该日期的收入上报状态
        return PayRedis::updateReportStatus($incomeNetWork, $date);

    }
}