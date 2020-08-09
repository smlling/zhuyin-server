<?php

namespace app\common;

use PHPMailer\PHPMailer\PHPMailer;
use AlibabaCloud\Client\AlibabaCloud;
use Firebase\JWT\JWT;

/**
 * 实用工具类
 * @author lwtting <smlling@hotmail.com>
 */
class Utils {

    /**
     * 生成随机验证码
     * @author lwtting <smlling@hotmail.com>
     * @return   string     
     */
    public static function generateRandCode () {
        $code = '';
        for($i = 0; $i < 6; $i++){
            $data = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $string = substr($data,rand(0, strlen($data)),1);
            $code .= $string;
        }
        return $code;
    }

    /**
     * 发送邮件
     * @author lwtting <smlling@hotmail.com>
     * @param    array  $data 发送参数
     *                            [
     *                              'reciver' => '',//收件人
     *                              'subject' => '',//邮件主题
     *                              'content' => ''//正文内容
     *                            ]
     * @return   void
     */
    public static function sendEmail ($data = []) {
        $mail = new PHPMailer(true); //实例化
        $mail->IsSMTP(); // 启用SMTP  
        $mail->Host = config('settings.phpmailer.email_smtp'); //SMTP服务器 以qq邮箱为例子   
        $mail->Port = 465;  //邮件发送端口  
        $mail->SMTPAuth = true;  //启用SMTP认证  
        $mail->SMTPSecure = "ssl";   // 设置安全验证方式为ssl  
        $mail->CharSet = "UTF-8"; //字符集  
        $mail->Encoding = "base64"; //编码方式  
        $mail->Username = config('settings.phpmailer.email_account');  //发件人邮箱 
        $mail->Password = config('settings.phpmailer.email_password');  //发件人密码 ==>重点：是授权码，不是邮箱密码
        $mail->From = config('settings.phpmailer.email_account');  //发件人邮箱 
        $mail->FromName = '主音';  //发件人姓名  
        if($data && is_array($data)){
            $mail->AddAddress($data['reciver']); //添加收件人
            $mail->IsHTML(true); //支持html格式内容  
            $mail->Subject = $data['subject']; //邮件标题   
            $mail->Body = $data['content']; //邮件主体内容
            $mail->Send();
        }
    }

    /**
     * 发送短信验证码
     * @author lwtting <smlling@hotmail.com>
     * @param string $phone     手机号
     * @param string $code      验证码
     * @return void
     */
    public static function sendSMS ($phone, $code) {
        AlibabaCloud::accessKeyClient(config('settings.aliyun.sms_accessKeyId'), config('settings.aliyun.sms_accessSecret'))
                    ->regionId('cn-hangzhou')
                    ->asDefaultClient();
        $result = AlibabaCloud::rpc()
                                ->product('Dysmsapi')
                                ->scheme('https') // https | http
                                ->version('2017-05-25')
                                ->action('SendSms')
                                ->method('POST')
                                ->host('dysmsapi.aliyuncs.com')
                                ->options([
                                    'query' => [
                                        'RegionId' => "cn-hangzhou",
                                        'PhoneNumbers' => $phone,
                                        'SignName' => "主音",
                                        'TemplateCode' => "SMS_181550642",
                                        'TemplateParam' => $code,
                                    ],
                                ])
                                ->request();
            // return $result->toArray();
    }
    
    /**
     * RSA私钥解密
     * @author lwtting <smlling@hotmail.com>
     * @param    string     $encryptString 密文
     * @return   string                    明文
     */
    public static function privateDecrypt ($encryptString = '') {
        $decrypted = '';
        $privateKey = config('settings.rsa.rsa_private_key');
        openssl_private_decrypt(base64_decode($encryptString), $decrypted, $privateKey);
        return $decrypted;
    }

    /**
     * RSA公钥加密
     * @author lwtting <smlling@hotmail.com>
     * @param    string     $data 明文数据
     * @return   string           密文
     */
    public static function publicEncrypt($data = ''){
        $encrypt_data = '';
        $publicKey = config('settings.rsa.rsa_public_key');
        openssl_public_encrypt($data, $encrypt_data, $publicKey);
        $encrypt_data = base64_encode($encrypt_data);
        return $encrypt_data;
    }

    /**
     * 对数组内每个元素进行trim()
     * @author lwtting <smlling@hotmail.com>
     * @param    array     $arr  待处理数组
     * @return   array     处理后的数组
     */
    public static function array_trim($arr){
        return array_map(
            function (&$value) {
                if(is_array($value)){
                    return deep_array_map($value);
                }
                return trim($value);
            }, $arr
        );
    }


    /**
     * 生成指定位数的随机字符串
     * @author lwtting <smlling@hotmail.com>
     * @param integer $lenght   长度
     * @return string           指定长度的随机字符串
     */
    public static function generateUniqueKey($lenght = 32) {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $lenght);
    }

    /**
     * 生成v4版UUID
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public static function generateUUIDv4(){
        // IN PHP < 7
        // $data = openssl_random_pseudo_bytes(16);

        // IN PHP7
        $data = random_bytes(16);
        // assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


    /**
     * 生成JWT串
     * @author lwtting <smlling@hotmail.com>
     * @param array     $payload        载荷
     * @return string                   JWT字符串
     */
    public static function encodeJWT ($payload) {
        // $payload = [
        //         "iss" => "http://example.org",
        //         "aud" => "http://example.com",
        //         "iat" => 1356999524,
        //         "nbf" => 1357000000
        // ]

        $jwt = JWT::encode($payload, config('settings.rsa.private_key'), 'RS256');
        return $jwt;

    }

    /**
     * JWT串解密
     * @author lwtting <smlling@hotmail.com>
     * @param string    $jwt                jwt字符串
     * @param boolean   $object             是否输出对象格式
     * @return object|array
     */
    public static function decodeJWT ($jwt, $object = true) {

        $decoded = JWT::decode($jwt, config('settings.rsa.public_key'), array('RS256'));
        
        return $object ? $decoded : (array) $decoded;
    }

}