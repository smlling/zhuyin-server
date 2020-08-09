<?php
namespace app\api\validate;

use think\Validate;
use DfaFilter\SensitiveHelper;

use app\api\model\Admin as AdminM;

class Admin extends Validate {

    protected $rule =   [

        'nickname'                      => 'require|length:1,20|chsDash|checkBanWords',//汉字、字母、数字和下划线_及破折号-
        'old_password'                  => 'require',
        'new_password'                  => 'require|length:8,16|different:old_password|checkPassword',
        'identity_type'                 => 'require|checkIdentityType',
        'identifier'                    => 'require',
        'credential'                    => 'require'

    ];
    
    protected $field = [

        'nickname'                      => '昵称',
        'old_password'                  => '旧密码',
        'new_password'                  => '新密码',

    ];

    protected $message  =   [

        'nickname.checkBanWords'        => '昵称中含有敏感词',
        'old_password.different'        => '新密码与旧密码不能相同',
        'new_password.checkPassword'    => '新密码过于简单,请至少使用字母/数字/特殊字符至少两种组合',

    ];
    
    protected $scene = [

        'changePassword'                => ['old_password', 'new_password'],
        'changeNickname'                => ['nickname'],
        'login'                         => ['identity_type', 'identifier', 'credential']

    ];

    /**
     * 敏感词检查
     * @author lwtting <smlling@hotmail.com>
     * @param    string     $data 
     * @return   boolean           
     */
    protected function checkBanWords ($data) {
        $banwords = config('settings.banwords');
        $handle = SensitiveHelper::init()->setTree($banwords);
        return $handle->islegal($data) ? true : false; // '昵称/简介包含敏感词:' . $handle->getBadWord($data, 1)[0];
    }

    /**
     * 校验密码是否由字母、数字、特殊字符中至少两种组成
     * @author lwtting <smlling@hotmail.com>
     * @param    string     $password 密码
     * @return   boolean               
     */
    protected function checkPassword ($password) {
        $letter='/[A-Za-z]/';  //英文字母
        $char='/[!@#$%^&*()\-_=+{};:,<.>]/';  //特殊字符
        $num='/[0-9]/';  //数字
        return ( 
            (preg_match_all($letter,$password, $o)>0 && preg_match_all($num,$password, $o)>0) ||
            (preg_match_all($letter,$password, $o)>0 && preg_match_all($char,$password, $o)>0) ||
            (preg_match_all($char,$password, $o)>0 && preg_match_all($num,$password, $o)>0)
        ) ? true : false;
    }

    /**
     * 登录方式是否支持
     * @author lwtting <smlling@hotmail.com>
     * @param string $value
     * @return boolean
     */
    protected function checkIdentityType ($value) {
        return in_array($value, AdminM::IDENTITY_TYPE) ? true : '不支持的登录验证类型';
    }
}