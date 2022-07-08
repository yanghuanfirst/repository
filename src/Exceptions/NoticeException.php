<?php

namespace Yang\Repository\Exceptions;

use Exception;
use Yang\Repository\Helper\ApiResponse;
use Throwable;

class NoticeException extends Exception
{
    use ApiResponse;

    function render(){
        $msg = $this->message;
        $message = json_decode($msg,true);
        if(!is_array($message)){//说明传的是字符串，不是json字符串
            return $this->fail($msg);
        }else{
            if(isset($message['code'])){
                return $this->jsonResponse($message['code'],$message['msg']);
            }else{
                return $this->fail($message['msg']);
            }
        }
    }

}
