<?php 

namespace app\api\model;

use think\Model;
use app\lib\exception\Status;
use app\lib\exception\ApiException as Error;
// use app\common\Utils;

class UserAuth extends Model {
    // 验证类型
    const IDENTITY_TYPE         = [
        'allow_register'        => [
            'phone'
        ],
        'allow_login'           => [
            'phone',
            'username'
        ],
        'allow_bind'            => [
            
        ]
    ];

    /**
     * 添加一条用户认证信息记录
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid              用户uid
     * @param string $identity_type     身份标识类型phone|weibo|wechat|qq|username
     * @param string $identifier        身份标识
     * @param string $credential        口令
     * @param string $ip                ip地址
     * @return UserAuth
     */
    public function add ($uid, $identity_type, $identifier, $credential) {

        if (isset($this->id)) {
            unset($this->id);
        }

        $this->uid = $uid;
        $this->identity_type = $identity_type;
        $this->identifier = $identifier;
        $this->credential = $credential;
        $this->isUpdate(false)->save();
        return $this;
    }

    /**
     * 设置密码(手机|用户名认证)
     * @author lwtting <smlling@hotmail.com>
     * @param string $credential
     * @return UserAuth
     */
    public function setCredential ($credential) {
        $this->credential = $credential;
        $this->isUpdate(true)->save();
        return $this;
    }

    

}