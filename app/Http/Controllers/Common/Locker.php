<?php

/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/17
 * Time: 14:30
 */
namespace App\Http\Controllers\Common;

use Illuminate\Support\Facades\Redis;

class Locker
{
    const LOCK_PREFIX = 'LOCK:';

    const TIME_OUT = 5000;

    const TIME_OVERFLOW = 1;

    const TIME_INTERVAL = 1000;

    private static $microTime = 0;

    private static $microTimeOut = 0;

    /**
     * 创建锁
     * @param $model
     * @return mixed
     */
    public static function createLock($model) {
        self::$microTime = microtime(true) * self::TIME_INTERVAL;
        self::$microTimeOut = self::$microTime + self::TIME_OUT + self::TIME_OVERFLOW;
        // 上锁
        $res = Redis::setnx(self::LOCK_PREFIX . $model, self::$microTimeOut);
        return $res;
    }

    /**
     * 上锁
     * @param $model
     */
    public static function lock($model){
        do {
            $isLock = self::createLock($model);
            if (!$isLock) {
                if(self::isWait($model)){
                    self::wait($model);
                    continue;
                }
            }
            break;
        } while (!$isLock);
    }

    /**
     * 解锁
     * @param $model
     * @return mixed
     */
    public static function unLock($model) {
        return Redis::del(self::LOCK_PREFIX . $model);
    }

    /**
     * 是否需要等待
     * @param $model
     * @return bool
     */
    public static function isWait($model){
        $microTime = microtime(true) * self::TIME_INTERVAL;
        $getTime = Redis::get(self::LOCK_PREFIX . $model);
        if ($getTime > $microTime)
            return true;
    }

    /**
     * 等待
     */
    public static function wait(){
        // 睡眠 降低抢锁频率　缓解redis压力
        usleep(self::TIME_OUT);
    }

    /**
     * 获取加锁Key
     * @param $model
     * @return string
     */
    public static function getLockKey($model){
        return self::LOCK_PREFIX . $model;
    }

    /**
     * 获取加锁时间
     * @return int
     */
    public static function getMicroTime(){
        return self::$microTime;
    }

    /**
     * 获取解锁时间
     * @return int
     */
    public static function getMicroTimeOut(){
        return self::$microTimeOut;
    }

}