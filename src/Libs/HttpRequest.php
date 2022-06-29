<?php


namespace Yang\Repository\Libs;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class HttpRequest
{
    public $client;
    public $response;

    function __construct($ssl = false)
    {
        $client = new Client(['verify' => $ssl]);
        $this->client = $client;
    }
    //GET请求
    function getRequest($url,array $query = [],array $header = [],$cookie = [], $timeout = 30){
        $host = parse_url($url);
        $cookieJar = $this->setCookie($host['host'],$cookie);
        $this->response = $this->client->request("GET",$url,[
            "query"=>$query,
            "connect_timeout"=>$timeout,
            "timeout"=>$timeout,
            "headers"=>$header,
            "cookies"=>$cookieJar
        ]);
        return $this;
    }
    //POST请求
    function postRequest($url,array $data = [],array $header = [],$cookie = [], $timeout = 30){
        $host = parse_url($url);
        if(!$header)$header = ['Content-Type'=>'application/x-www-form-urlencoded;charset=utf-8;'];
        $cookieJar = $this->setCookie($host['host'],$cookie);
        $this->response = $this->client->request("POST",$url,[
            "body"=>http_build_query($data),
            'headers'=>$header,
            "connect_timeout"=>$timeout,
            "cookies"=>$cookieJar,
            "timeout"=>$timeout
        ]);
        return $this;
    }
    //POST请求,请求头是application/json
    function postJsonRequest($url,array $data = [],array $header = [],$cookie = [], $timeout = 30){
        $host = parse_url($url);
        if(!$header)$header = ['Content-Type'=>'application/json;charset=utf-8;'];
        $cookieJar = $this->setCookie($host['host'],$cookie);
        $this->response = $this->client->request("POST",$url,[
            "body"=>json_encode($data),
            'headers'=>$header,
            "connect_timeout"=>$timeout,
            "cookies"=>$cookieJar,
            "timeout"=>$timeout
        ]);
        return $this;
    }
    //获取请求返回内容
    function getContent(){
        return json_decode($this->response->getBody()->getContents(),1);
    }

    //获取请求状态码  200 ，404
    function getStatusCode(){
        return $this->response->getStatusCode();
    }
    //设置cookie
    function setCookie($host,array $cookie = []){
        $cookieJar = [];
        if($cookie)
            $cookieJar = CookieJar::fromArray($cookie,$host);
        return $cookieJar;
    }
    //用来请求websocket
    function curlPostReqeust($url,$data,$header = [],$timeout = 3){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($header){
            curl_setopt($ch,CURLOPT_HEADER,$header);
        }
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
    }
    public static function makeRequest_apk($url, $param)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            $res = curl_exec($ch);
            $status = curl_getinfo($ch);
        } catch (\Exception $e) {
        }
        curl_close($ch);
        if (intval($status["http_code"]) == 200) {
            return $res;
        } else {
            return FALSE;
        }

    }

    public function http_post_data($url,$data_string,$timeout=30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        echo $return_content;
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
    }


    function get_api($url,$header)
    {
        $curl = curl_init(); // 启动一个CURL会话
        $header[] = "Content-type: text/html;charset=utf-8";
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        $contents = curl_exec($curl);     //返回api的json对象
        $contents = mb_convert_encoding($contents, 'utf-8');
        //关闭URL请求
        curl_close($curl);
//    dd($contents);
        return $contents;
    }







}
