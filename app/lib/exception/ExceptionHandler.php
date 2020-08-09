<?php
namespace app\lib\exception;

use think\Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ThrowableError;
use think\exception\RouteNotFoundException;

use app\lib\exception\ApiException;
use app\lib\exception\Status;

/**
 * 自定义异常类
 */
class ExceptionHandler extends Handle {
    protected $ignoreReport = [
        'app\lib\exception\ApiException',
        'think\exception\RouteNotFoundException'
    ];
    /**
     * http状态码
     */
    public $httpCode = 200;

    public function render(\Exception $e){
        $debug_status = config("app_debug");
        if( 0 && $debug_status){
            return parent::render($e);
        }
        else{
            // 自定义异常
            if ($e instanceof ApiException) {
                return json([
                    'success'       => false,
                    'msg'           => $e->message,
                    'data'          => [],
                    'status_code'   => $e->status_code
                ], $e->httpCode);
            }

            if ($e instanceof RouteNotFoundException){
                return response('page not found', 404);
            }

            $msg = '发生内部错误，请联系管理员';
            // if ($e instanceof \PDOException) {
            //     $msg = '数据库繁忙，请联系管理员';
            // } else if ($e instanceof DbException) {
            //     $msg = '数据表错误，请联系管理员';
            // } else if ($e instanceof \PHPMailer\PHPMailer\Exception) {
            //     $msg = '邮件服务暂时不可用';
            // } else if ($e instanceof \AlibabaCloud\Client\Exception\ClientException || $e instanceof \AlibabaCloud\Client\Exception\ServerException) {
            //     $msg = '短信服务暂时不可用';
            // } else if ($e instanceof RouteNotFoundException){
            //     $msg = '404';
            // } else {
            //     $msg = '发生内部错误，请联系管理员';
            // }
            $data = [
                'success' => false, 
                'msg' => $msg,
                'data' => [],
                'error_code' => Status::API_INNER_ERROR
            ];
            trace('[ ERROR_MSG  ] ' . var_export($e->getMessage(), true), 'info');
            trace('[ ERROR_FILE ] ' . var_export($e->getFile(), true), 'info');
            trace('[ ERROR_LINE ] ' . var_export($e->getLine(), true), 'info');
            trace('[ CALL_STACK ] ' . var_export($e->getTraceAsString(), true), 'info');
            trace('[ ROUTE ] ' . var_export(request()->routeInfo(), true), 'info');
            trace('[ HEADER ] ' . var_export(request()->header(), true), 'info');
            trace('[ PARAM ] ' . var_export(request()->param(), true), 'info');
            trace('[ RETURN_DATA ] ' . var_export($data, true), 'info');
            return json($data, $this->httpCode);
        }
    }
}