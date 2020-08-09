<?php
namespace app\api\validate;

use think\Validate;
use app\api\model\UserAuth as UserM;

class UserAuth extends Validate {
    // 状态码
    const IDENTITIER_EXIST              = -1;
    // const INVALID_PHONE             = -2;

    protected $rule =   [
        'identity_type'                 => 'require',
        'identifier'                    => 'require',
        'credential'                    => 'require|length:8,16',
        'action'                        => 'require',
        'old_credential'                => 'require',
        'new_credential'                => 'require|length:8,16|different:old_credential|checkPassword',
    ];
    
    protected $field = [
        'username'                      => '用户名',
        'identity_type'                 => '验证类型',
        'identifier'                    => '身份标识',
        'credential'                    => '口令',
        'old_credential'                => '旧口令',
        'new_credential'                => '新口令',
    ];

    protected $message  =   [
        'identity_type'                 => '不允许的验证类型',
        'identity_type.isLoginType'     => '当前不允许使用该种方式登录',
        'identifier.mobile'             => '手机号不合法',
        'identifier.checkUnique'        => self::IDENTITIER_EXIST,
        'credential.checkPassword'      => '口令过于简单,请至少使用字母/数字/特殊字符至少两种组合',
        'new_credential.different'      => '新口令与旧口令不能相同',
        'new_credential.checkPassword'  => '新口令过于简单,请至少使用字母/数字/特殊字符至少两种组合',
    ];
    
    protected $scene = [
        
        // 'checkAction'               => ['identity_type', 'action'],
        'changePassword'                => ['old_credential', 'new_credential'],
        
    ];

    /**
     * 添加手机验证时的验证场景
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    protected function sceneAddPhone () {
        return $this->only(['identity_type', 'identifier', 'credential'])
                    ->append('identity_type', 'eq:phone')
                    ->append('identifier', 'mobile|checkUnique')
                    ->append('credential', 'checkPassword');
    }

    /**
     * 登录时的验证场景
     * 检查登录方式是否允许
     * @return void
     */
    protected function sceneCheckLogin () {
        return $this->only(['identity_type', 'identifier', 'credential'])
                    ->append('identity_type', 'isLoginType');
    }

    /**
     * 专门检测密码的验证场景
     * @return void
     */
    protected function sceneCheckPassword () {
        return $this->only(['credential'])
                    ->append('credential', 'checkPassword');
    }

      

    /**
     * 手机号校验
     * @author lwtting <smlling@hotmail.com>
     * @param string $tel       手机号
     * @return   boolean         
     */
    protected function isPhoneNumber ($tel) {
        echo $tel;
        return preg_match("/^1[345789]{1}\d{9}$/",$tel) ? true : false;
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
     * 检查指定类型的账户唯一性
     * @author lwtting <smlling@hotmail.com>
     * @param string $action
     * @param array $rule
     * @param array $data
     * @return mixed
     */
     protected function checkUnique ($identifier, $rule = [], $data) {

        // 验证唯一性
        $user_auth = UserM::where('identity_type = ? AND identifier = ? AND dissolved = 0', [$data['identity_type'], $identifier])->find();
        return $user_auth ? false : true;
    }

    /**
     * 验证是否是可登录的验证类型
     * @author lwtting <smlling@hotmail.com>
     * @param string $value
     * @return boolean
     */
    protected function isLoginType ($value) {

        return in_array($value, UserM::IDENTITY_TYPE['allow_login']);
    }
}