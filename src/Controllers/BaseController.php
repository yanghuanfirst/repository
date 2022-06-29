<?php

namespace Yang\Repository\Controllers;

use Yang\Repository\Controllers\Controller;
use Yang\Repository\Libs\HttpRequest;
use Yang\Repository\Helper\ApiResponse;


class BaseController extends Controller
{
    //
    use ApiResponse;

    /**
     * 获取接口文档token
     * @return false|mixed|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getApiToken()
    {

//        $url = 'https://123.60.223.158:5003/api/user/login';
        $url = 'https://47.108.150.100:5003/api/user/login';
//        $data = [
//            'username'=>'xway',
//            'password'=>'xway@234',
//        ];
        $data = [
            'username'=>'admin1',
            'password'=>'j4I7#xaNXmt9',
        ];
        $data = $this->doPost($url,json_encode($data));
        if ($data['code']!=200){
            return $this->fail('获取token失败');
        }
        $token = $data['data']['token'];
        \Cache::set('api_token',$token,600);
        return $token;
    }


    /**
     * 敏感信息，安全检查对接获取token
     * @return mixed
     */
    public function getToken()
    {
        $parameter_json = [
            'username'=>'mzx_api1',
            'password'=>'7b84a298349b11ec90d4f889d2393248',
        ];
        $url = 'http://119.28.16.153:8001/user/login/';
        $ret = $this->HttpPost($url,json_encode($parameter_json,true),true);
        $api_token = $ret['token'];
        return $api_token;
    }
}
