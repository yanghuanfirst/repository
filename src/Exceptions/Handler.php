<?php

namespace Yang\Repository\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;
use App\Api\Helpers\ApiResponse;

class Handler extends ExceptionHandler
{
    use ApiResponse;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        //ajax请求我们才捕捉异常
        //if ($request->ajax()){
            if($exception instanceof NoticeException){
                $msg = $exception->getMessage();
                $message = json_decode($msg,true);
                if(!$message){//说明传的是字符串，不是json字符串
                    return $this->fail($msg);
                }else{
                    if(isset($message['code'])){
                        return $this->jsonResponse($message['code'],$message['msg']);
                    }else{
                        return $this->fail($message['msg']);
                    }
                }

            }
       // }
        return parent::render($request, $exception);
    }
}
