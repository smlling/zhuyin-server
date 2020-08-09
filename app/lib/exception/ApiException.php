<?php
namespace app\lib\exception;

use think\Exception;

class ApiException extends Exception {
    // 错误信息
    public $message;
    // http状态码
    public $httpCode;
    // 业务状态码
    public $status_code;
    
    public function __construct($status_code = 0, $message = '', $httpCode = 200){
        $this->message = $message ?: Status::MESSAGE[$status_code];
        $this->httpCode = $httpCode;
        $this->status_code = $status_code;
    }

}