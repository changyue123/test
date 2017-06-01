<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 17-5-16
 * Time: 下午4:33
 */

namespace App\Http\Controllers\Common;

use Monolog\Logger;
use Illuminate\Log\Writer;

class Log
{
    //LOG路径
    const LOG_PATH = 'logs';
    //LOG前缀
    const LOG_PREFIX = 'laravel';
    //异常类型
    const LOG_EXCEPTION = 'exception';
    //LOG收集器
    private static $loggers = array();
    //LOG级别
    private static $levels = array(
        'debug',   //DEBUG类型
        'info',    //信息类型
        'notice',  //NOTICE类型
        'warning', //WARNING类型
        'error',   //ERROR类型
        'critical',//应用组件异常
        'alert',   //服务器down机
        'emergency'//紧急提醒
    );

    /**
     * 获取日志实例
     * @param string $model 日志模块名称
     * @param string $prefix 日志模块名称
     * @param int $num 有效日志数量
     * @return mixed 返回实例数组
     */
    public static function getLogger($model, $prefix = self::LOG_PREFIX, $num = 30){
        if (empty(self::$loggers[$model])) {
            self::$loggers[$model] = new Writer(new Logger($model));
            self::$loggers[$model]->useDailyFiles(storage_path().'/'.self::LOG_PATH.'/' . $prefix . '.log', $num);
        }
        return self::$loggers[$model];
    }

    /**
     * 日志记录
     * @param $data
     * @param $model
     * @param string $level
     * @param string $suffix
     */
    public static function setLog($data, $model, $level='error', $suffix = ''){
        if(!is_array($data)){
            $data = json_decode($data, true);
        }
        if($suffix){
            $model .= $suffix;
        }
        if(in_array($level,self::$levels)){
            self::getLogger(mb_strtolower($model))->$level(json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * 异常信息记录
     * @param $exception
     * @param $request
     */
    public static function setExceptionLog($exception,$request = null){
        $error = array(
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode()
        );
        $source = self::LOG_EXCEPTION;
        //记录请求参数
        if($request){
            $error['visitUrl'] = $request->fullUrl();
            $error['clientIp'] = $request->getClientIp();
            $error['fileInfo'] = $request->allFiles();
            $error['params'] = $request->all();
            $source = 'http';
        }
        self::getLogger($source)->error(json_encode($error, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
    }
}