<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Common\Response;
use App\Http\Controllers\Common\Validator;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    /**
     * 参数验证
     * @param array $rule
     * @param array $data
     * @return mixed
     */
    protected function validator(array $rule, array $data = array()){
        if (Validator::execute($data, $rule)) {
            return $data;
        } else {
            return false;
        }
    }

    /**
     * 返回错误信息
     * @return mixed
     */
    protected function getError(){
        return Validator::getError();
    }
    /**
     * 返回成功信息
     * @param $msg
     * @param $data
     * @return string
     */
    protected function response($msg, $data = array()){
        return Response::handle($msg, $data);
    }

    /**
     * 打印信息
     * @param $msg
     * @param array $data
     */
    protected function dump($msg, $data = array()){
        Response::dump($msg, $data);
    }

}
