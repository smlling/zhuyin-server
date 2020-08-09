<?php

namespace app\admin\controller;

use think\Controller;
use app\common\Status;
use app\common\controller\Utils;
use app\api\service\Admin;

class Home extends Controller {

    protected $middleware = [
        'RoleCheck:admin' => [
            'except' => [
                'login'
            ]
        ]
    ];

    /**
     * 实例化服务
     * @author lwtting <smlling@hotmail.com>
     * @return Admin
     */
    protected function service () {

        return new Admin($this->request->loginUserM);

    }

    /**
     * 管理员登录
     * @api post:admin/home/login
     * @author lwtting <smlling@hotmail.com>
     * @return array        token
     */
    public function login(){
        $identity_type = input('post.identity_type/s');
        $identifier = input('post.identifier/s');
        $credential = input('post.credential/s');
        $ip = $this->request->ip();

        return $this->success('登录成功', $this->service()->login($identity_type, $identifier, $credential, $ip));
    }

    /**
     * 获取管理员信息
     * @api get:admin/home/info
     * @author lwtting <smlling@hotmail.com>
     * @return json         用户信息
     */ 
    public function getInfo () {
        
        return $this->success('获取用户信息成功', $this->service()->getAdminInfo());

    }

    /**
     * 退出登录
     * @api get:admin/home/logout
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function logout () {
        // 撤销token
        cache('token_' . request()->header('Authorization'), null);
        return $this->success('注销成功');
    }

    /**
     * 更改密码
     * @api post:admin/home/changePassword
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function changePassword () {
        $old_password = input('post.old_password/s');
        $new_password = input('post.new_password/s');
        $adminService = $this->service();
        $adminService->changePassword($old_password, $new_password);
        $this->logout();
        return $this->success('密码更改成功,请重新登录');
    }

    /**
     * 设置昵称
     * @api post:admin/home/setNickname
     * @author lwtting <smlling@hotmail.com>
     * @return json             用户信息
     */
    public function changeNickname () {
        
        $nickname = input('post.nickname/s');

        $adminService = $this->service();

        return $this->success('更改昵称成功', $adminService->changeNickname($nickname));
    }

}