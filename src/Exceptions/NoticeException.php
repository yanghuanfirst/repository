<?php

namespace Yang\Repository\Exceptions;

use Exception;
use Yang\Repository\Helpers\ApiResponse;
use Throwable;

class NoticeException extends Exception
{
    use ApiResponse;


    //
    public function render($request)
    {
        $message = json_decode($this->getMessage(),true);
        if(isset($message['code'])){
            return $this->jsonResponse($message['code'],$message['msg']);
        }else{
            return $this->fail($message['msg']);
        }
    }
}
