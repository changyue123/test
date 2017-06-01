<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/24
 * Time: 下午6:12
 */

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;

interface Efs
{
    /**
     * 获取收入上报最小日期
     * @param Request $request
     * @return mixed
     */
    function OnlyReport(Request $request);

    /**
     * 获取收入日报数据
     * @param Request $request
     * @return mixed
     */
    function getReportMinDate(Request $request);

    /**
     * 上报确认
     * @param Request $request
     * @return mixed
     */
    function OnlyReportNotify(Request $request);

    /**
     * 根据网点获取最小日期
     * @param String $incomeNetWork 网点ID
     * @return mixed
     */
    function getDate($incomeNetWork);

    /**
     * 获取收入上报数据
     * @param String $incomeNetWork 网点ID
     * @param String $date 上报日期
     * @return mixed
     */
    function report($incomeNetWork, $date);

    /**
     * 财务回调接口（上报确认）
     * @param String $incomeNetWork 网点ID
     * @param String $date 上报日期
     * @return mixed
     */
    function notify($incomeNetWork, $date);
}