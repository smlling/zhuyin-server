<?php

namespace app\user\controller;

use think\Controller;
use app\lib\exception\Status;
use app\api\service\User as UserService;
use app\common\Upload;
use app\common\Authcode;


class Home extends Controller {

    protected $middleware = [ 
        'RoleCheck:user'=> [
            'except'    => [
                'register', 'login', 'verifyNewDevice', 'resetPassword', 'getAvatar'
            ]
        ],
        'RoleCheck:guest' => [
            'only'      => [
                'register', 'login', 'verifyNewDevice', 'resetPassword'
            ]
        ]
    ];

    /**
     * 实例化服务
     * @author lwtting <smlling@hotmail.com>
     * @return UserService
     */
    protected function service () {
        return new UserService($this->request->loginUserM);
    }

    /**
     * 用户注册接口 (手机注册)
     * @api post:home/register
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function register () {

        

        $phone = $this->request->post('phone/s');
        $password = $this->request->post('password/s');
        $authcode = $this->request->post('authcode/s');
        $device_identifier = $this->request->device_id;
        $ip = $this->request->ip();

        // 校验验证码
        Authcode::verify(Authcode::KEY_PHONE, $authcode, $phone, $device_identifier);

        return $this->success('注册并登录成功', $this->service()->registerUserByPhone($phone, $password, $ip, $device_identifier));
        
    }

    /**
     * 设置头像
     * @api post:home/avatar
     * @author lwtting <smlling@hotmail.com>
     * @return json     用户信息
     */
    public  function setAvatar () {

        $upload = new Upload();
        $avatarPath = $upload->uploadAvatar();

        return $this->success('头像上传成功', $this->service()->setAvatar($avatarPath));

    }

    /**
     * 获取头像
     * @api get:home/avatar
     * @author lwtting <smlling@hotmail.com>
     * @return image
     */
    public function getAvatar() {
        
        $uid = input('get.id/d', 0); 
        $type = input('get.type/s', 'small');

        $avatar = $this->service()->getAvatar($uid);
        if (isset($avatar[$type])) {
            $path = $avatar[$type];
        } else {
            return ;
        }
        
        return download($avatar[$type], 'avatar', false, 360, true);
        
    }

    /**
     * 设置用户信息
     * @api post:home/info
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public function setInfo () {

        $userInfo = $this->request->only([
            'nickname',
            'sex',
            'age',
            'location',
            'description'
        ]);

        return $this->success('用户资料更新成功', $this->service()->setInfo($userInfo));

    }

    /**
     * 获取用户信息
     * @api get:home/info
     * @author lwtting <smlling@hotmail.com>
     * @return json         用户信息
     */ 
    public function getInfo () {

        $uid = input('get.id/d', 0);
        
        return $this->success('获取用户信息成功', $this->service()->getUserInfo($uid));

    }

    
    
    /**
     * 用户登录
     * @api post:home/login
     * @author lwtting <smlling@hotmail.com>
     * @return json         token
     */
    public function login () {

        $identity_type = input('post.identity_type/s');
        $identifier = input('post.identifier/s');
        $credential = input('post.credential/s');
        $device_identifier = $this->request->device_id;
        $ip = $this->request->ip();

        return $this->success('登录成功', $this->service()->login($identity_type, $identifier, $credential, $device_identifier, $ip));

    }

    /**
     * 新设备登录验证
     * @api post:home/login/verify
     * @author lwtting <smlling@hotmail.com>
     * @return json         token
     */
    public function verifyNewDevice () {

        $authcode = $this->request->post('authcode/s');
        $device_identifier = $this->request->device_id;

        // 校验验证码
        $phone = Authcode::verify(Authcode::KEY_DEVICE, $authcode, null, $device_identifier);
        
        return $this->success('验证成功', $this->service()->updateDevice($phone, $device_identifier));

    }

    /**
     * 退出登录
     * @api get:home/logout
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function logout () {

        // 撤销token
        cache('token_' . request()->header('Authorization'), null);
        return $this->success('注销成功');

    }

    /**
     * 设置用户名
     * @api post:home/info/username
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public function setUsername () {

        $username = input('post.username/s');

        return $this->success('用户名设置成功', $this->service()->setUsername($username));
    }

    /**
     * 用户更改密码
     * @api post:home/changePassword
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function changePassword () {

        $old_password = input('post.old_password/s');
        $new_password = input('post.new_password/s');

        // 更改密码
        $this->service()->changePassword($old_password, $new_password);
        // 注销当前登录
        $this->logout();

        return $this->success('密码更改成功,请重新登录');

    }

    /**
     * 重置用户密码
     * @api post:home/resetPassword
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function resetPassword () {

        $new_password = input('post.new_password/s');
        $phone = $this->request->post('phone/s');
        $authcode = $this->request->post('authcode/s');
        $device_identifier = $this->request->device_id;

        // 校验验证码
        Authcode::verify(Authcode::KEY_PHONE, $authcode, $phone, $device_identifier);

        $this->service()->resetPassword($phone, $new_password);

        return $this->success('密码重置成功,现在您可以使用新的密码登录');

    }

    /**
     * 搜索用户
     * @api get:home/user/search
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function searchUser () {

        $keyword = input('get.keyword/s');

        return $this->success('搜索成功', $this->service()->searchUser($keyword));

    }

}