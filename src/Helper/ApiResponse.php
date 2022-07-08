<?php
namespace Yang\Repository\Helper;

use Yang\Repository\Exceptions\NoticeException;
use Yang\Repository\Libs\HttpRequest;
use Exception;
use Illuminate\Support\Facades\Log;

trait ApiResponse
{
    public $successCode = 1;
    public $failCode = -1;
    /**
     * 返回一个json
     * @param $code 状态码
     * @param $message 返回说明
     * @param $data 返回数据集合
     * @return false | string
     */
    public function jsonResponse($code, $message, $data=[]){
        $content = [
            'code' => $code,
            'message'  => $message,
            'data' => $data
        ];
        return response()->json($content);
    }

    function success($message,$data = []){
        if(is_array($data)){
            $tmp['route_name'] = $this->getRouteName();
            $res = array_merge($data,$tmp);
            return $this->jsonResponse($this->successCode,$message,$res);
        }else{
            return $this->jsonResponse($this->successCode,$message,$data);
        }

    }

    function fail($message,$data = [],$e = null){
        if(is_array($data)){
            $tmp['route_name'] = $this->getRouteName();
            $res = array_merge($data,$tmp);
            return $this->jsonResponse($this->failCode,$message,$res);
        }else{
            return $this->jsonResponse($this->failCode,$message,$data);
        }
    }

    function exception($messsage){
        if(is_string($messsage)){
            throw new NoticeException($messsage);
        }else{
            $msg = json_encode($messsage);
            throw new NoticeException($msg);
        }

    }

    function fatalError($e){
        Log::error($e->getMessage()."--file:".$e->getFile()."--line:".$e->getLine());
        return $this->fail("网络错误。请稍后再试");
    }
    /**
     * @param $arr 数组
     * @param $id   id
     * @return array
     */
    function sonTree($arr,$id=0)
    {
        $list =array();
        foreach ($arr as $k=>$v){
            if ($v['pid'] == $id){
                $v['son'] = $this->sonTree($arr,$v['id']);
                $list[] = $v;
            }
        }
        return $list;
    }
    //递归
    function getMenuTree($arrCat, $parent_id = 0)
    {
        static $arrTree = []; //使用static代替global
        if (empty($arrCat)) return [];

        foreach ($arrCat as $key => $value) {
            if ($value['pid'] == $parent_id) {
                $arrTree[] = $value['id'];
                unset($arrCat[$key]); //注销当前节点数据，减少已无用的遍历
                $this->getMenuTree($arrCat, $value['id']);
            }
        }
        return $arrTree;
    }
    //是否是平台管理员
    function isSuperMan($user){
        return $user['type'] == 1?true:false;
    }
    //是否是超级管理员
    function isSuperSuperMan($user){
        return $user['is_superman'] == 1?true:false;
    }
    //是否是单位管理员
    function isSLocationMan($user){
        return $user['type'] == 2?true:false;
    }
    //是否拥有全国的权限
    function isCountry($user){
        $except = config("common_config")["province_id"];
        return in_array($user['provincial_id'],$except);
    }


    //获取路由名
    function getRouteName(){
        return request()->route()->getName();
    }

    //验证ID
    function verifyId($id){
        $res = intval(unlockUrl($id));
        if(!$res)$this->exception("缺少参数");
        return $res;
    }
    //加密函数
    function lock_url($txt,$key='www.xway.cn'){
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
        $nh = rand(0,64);
        $ch = $chars[$nh];
        $mdKey = md5($key.$ch);
        $mdKey = substr($mdKey,$nh%8, $nh%8+7);
        $txt = base64_encode($txt);
        $tmp = '';
        $i=0;$j=0;$k = 0;
        for ($i=0; $i<strlen($txt); $i++) {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = ($nh+strpos($chars,$txt[$i])+ord($mdKey[$k++]))%64;
            $tmp .= $chars[$j];
        }
        return urlencode($ch.$tmp);
    }
    //解密函数
    function unlock_url($txt,$key='www.xway.cn'){

        $txt = urldecode($txt);
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
        $ch = $txt[0];
        $nh = strpos($chars,$ch);
        $mdKey = md5($key.$ch);
        $mdKey = substr($mdKey,$nh%8, $nh%8+7);
        $txt = substr($txt,1);
        $tmp = '';
        $i=0;$j=0; $k = 0;
        for ($i=0; $i<strlen($txt); $i++) {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = strpos($chars,$txt[$i])-$nh - ord($mdKey[$k++]);
            while ($j<0) $j+=64;
            $tmp .= $chars[$j];
        }
        $res = base64_decode($tmp);
        $this->check_str($res);
        return $res;
    }
    function check_str($str){
        $getfilter="'|<[^>]*?>|^\\+\/v(8|9)|\\b(and|or)\\b.+?(>|<|=|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
        $postfilter="^\\+\/v(8|9)|\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|<\\s*img\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
        $cookiefilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
        if (preg_match("/".$getfilter."/is",$str)==1){
            print "<div style=\"position:fixed;top:0px;width:100%;height:100%;background-color:white;color:green;font-weight:bold;border-bottom:5px solid #999;\"><br>不要非法尝试操作,谢谢合作!</div>";
            exit();
        }
        if (preg_match("/".$postfilter."/is",$str)==1){
            print "<div style=\"position:fixed;top:0px;width:100%;height:100%;background-color:white;color:green;font-weight:bold;border-bottom:5px solid #999;\"><br>不要非法尝试操作,谢谢合作!</div>";
            exit();
        }
        if (preg_match("/".$cookiefilter."/is",$str)==1){
            print "<div style=\"position:fixed;top:0px;width:100%;height:100%;background-color:white;color:green;font-weight:bold;border-bottom:5px solid #999;\"><br>不要非法尝试操作,谢谢合作!</div>";
            exit();
        }
    }

    /**
     * GET请求api接口文档
     * @param $url
     * @return mixed
     */
    public function doGet($url)
    {

        $api_token = $this->getApiToken();
        //初始化
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        // 请求头，可以传数组
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'token:'.$api_token,
            )
        );

        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


        //执行并获取HTML文档内容
        $output = curl_exec($ch);

        //释放curl句柄
        curl_close($ch);

        return json_decode($output,true);
    }


    /**
     * POST请求api接口文档
     * @param $url
     * @param $post_data
     * @param bool $token
     * @return mixed
     */
    public function doPost($url,$post_data,$token=false)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        curl_setopt($ch,CURLOPT_TIMEOUT,10);
        // 请求头，可以传数组
        //请求是否需要token
        if ($token==false){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($post_data))
            );
        }else{
            $api_token = $this->getApiToken();
            // 请求头，可以传数组
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'token:'.$api_token,
                )
            );
        }
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output,true);
    }


    /**
     * 敏感信息，安全检查对接
     * @param $url
     * @param $param
     * @param bool $is_json
     * @param string $token
     * @return mixed
     */
    public function HttpPost($url,$param,$is_json=false,$token=''){
        $ch = curl_init();
        //如果$param是数组的话直接用
        curl_setopt($ch, CURLOPT_URL, $url);
        //如果$param是json格式的数据，则打开下面这个注释
        if ($is_json == true){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($param),
                    'token: ' . $token,
                )
            );
        }else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'token: ' . $token,
                )
            );
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //如果用的协议是https则打开鞋面这个注释
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);

        curl_close($ch);
        return json_decode($data,true);

    }

    /**
     * @param $url
     * @param array $param
     * @param bool $token  是否需要token
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpCurlPost($url,$param=[],$token=false)
    {
        $client = new HttpRequest();
        $ret = $client->client->post($url,$param);
        return $ret->getBody()->getContents();
    }


    /**
     * 多维数组降维
     * @param $array
     * @return array
     */
    public function arrayJiang($array)
    {
        $arr = [];
        foreach ($array as $k=>$v){
            foreach ($v as $kk=>$vv){
                $arr[] = $vv;
            }
        }
        return $arr;
    }

    /**
     * 生成随机字符串
     * @return string
     */
    public function strRandom()
    {
        return '61'.substr(md5(time()),0,20).rand(0000,9999);
    }
    /**
     * $user
     * 是否是uc统一登录的管理员
     */
    function isUcManager($user){
        if($user['user_type'] == 1)
            return true;
        else
            return false;
    }
    //是否是app项目超级管理员
    function isAppSuperman($user){
        if($user['user_type'] == 1)
            return true;
        else
            return false;
    }
    //是否是app项目管理员
    function isAppManager($user){
        if($user['user_type'] == 2)
            return true;
        else
            return false;
    }
    /**
     * $user
     * 是否是红黑平台的管理员
     */
    function isRedManager($user){
        if($user['type'] == 1)
            return true;
        else
            return false;
    }

    /**
     * 上传文件
     * @param $file    //上传文件
     * @param $type_arr      //需要判断的文件类型,例如['jpg','png','gif']
     * @param $dir          //文件要保存的目录名称
     * @return bool
     */
    function uploadFile($file,$type_arr,$dir){
        $request = request();
        $apkInfo = $request->file($file);
        $extension = $apkInfo->extension();
        $fileType_map = $type_arr;
        if(!in_array($extension,$fileType_map)){
            return false;
        }
        $newName = md5(time() . rand(100000, 999999) . $apkInfo->getClientOriginalName()) . '.' . $extension;
        $dir_url = 'public/'.$dir.'/' . date('Ymd');
        if(!file_exists($dir_url)){
            @mkdir($dir_url,0776,true);
        }
        $apkInfo->storeAs($dir_url, $newName);
        $store_result ='storage/' . $dir .'/' . date('Ymd').'/'.$newName ;
        return $store_result;
    }


    /**
     * 上传多文件
     * @param $Files     //FILES 数组
     * @param bool $thumb           //是否生成小图
     * @param string $userpath         //用户路径基于 uploads/ 根目录
     * @param int $id        //id
     * @param string $fname
     * @param bool $more
     * @param bool $xsKey
     * @return array
     * @throws Exception
     * @param array $fileType            验证文件类型
     * @return array|bool
     * @throws Exception
     */
    function upFiles($Files,$userpath = '',$more = FALSE,$xsKey = FALSE,$fileType=['jpg','png','gif']){

        $sort = 1;
        if ($userpath){
            $userpath = DS . $userpath;
        }
        $data = [];
        foreach ($Files as $k=>$file){
            if ($file['error']){
                throw new Exception('文件错误');
            }
            $file = request()->file($k);
            // 移动到框架应用根目录/public/uploads/ 目录下
            $root =   DIRECTORY_SEPARATOR . 'public\uploads' . $userpath .DS . date('Ymd') ;
            $file_url =   'storage\uploads' . $userpath .DS . date('Ymd') ;
            //是否需要根据序号增加文件夹【当没有使用xsKey的时候，则开启】
            if ( $more && ! $xsKey){
                $root .=  DS . ($sort++);
            }
            $extension = $file->extension();
            $newName = md5(time() . rand(100000, 999999) . $file->getClientOriginalName()) . '.' . $extension;
            if(in_array($file->extension(),$fileType)){
                //文件url目录
                $fileurl = str_replace('\\', '/', $root.'');
                $store_result = $file_url.'\\'.$newName;
                $file->storeAs($fileurl, $newName);
                $data = [
                    'status'=>2,
                    'path'=>$store_result,
                ];
            }else{
                // 上传失败获取错误信息
                $data = [
                    'status'=>0,
                    'msg'=>'文件上传类型不符合要求',
                ];
            }
        }
        return $data;
    }



}


