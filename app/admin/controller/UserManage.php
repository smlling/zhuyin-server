<?php

namespace app\admin\controller;

use think\Controller;

use app\common\controller\Utils;
use app\lib\exception\Status;
use app\api\service\User;

class UserManage extends Controller{
    
    protected $middleware = [
        'RoleCheck:admin' => [
            'except' => [

            ]
        ]
    ];

    /**
     * 实例化服务
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    protected function service () {
        return new User($this->request->loginUserM);
    }

    /**
     * 获取用户列表
     * @api get:admin/UserManage/user/list
     * @author lwtting <smlling@hotmail.com>
     * @return json                     用户列表
     */
    public function userList(){

        $limit = input('get.limit/d', 20);
        $page = input('get.page/d', 1);
        $status = input('get.status/s', 'all');
        $uid = input('get.uid/d', 0);
        $sort_condition = input('get.sort_condition/s', 'register_time');
        $sort_type = input('get.sort_type/s', 'desc');

        return $this->success('获取用户列表成功',$this->service()->getUserList($page, $limit, $status, $uid, $sort_condition, $sort_type));
    }

    /**
     * 查看用户信息
     * @api get:admin/UserManage/user/info
     * @author lwtting <smlling@hotmail.com>
     * @return json                     用户信息
     */
    public function getInfo () {

        $uid = input('get.uid/d');

        if ($uid <= 0) {
            return $this->error(Status::FORM_PARAM_ERROR, '参数错误: 无效的用户id');
        }

        return $this->success('获取用户信息成功', $this->service()->getUserInfo($uid));
    }

    /**
     * 冻结用户
     * @api post:admin/UserManage/user/freeze
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid
     * @return json                     用户信息
     */
    public function freezeUser(){

        $uid = input('post.uid/d');

        if ($uid <= 0) {
            return $this->error(Status::FORM_PARAM_ERROR, '参数错误: 无效的用户id');
        }

        $freeze_reason = input('post.freeze_reason/d');
        $freeze_time = input('post.freeze_time/d');

        return $this->success('冻结用户成功', $this->service()->freezeUser($uid, $freeze_reason, $freeze_time));

    }

    /**
     * 解冻用户
     * @api post:admin/UserManage/user/unfreeze
     * @author lwtting <smlling@hotmail.com>
     * @return json                     用户信息
     */
    public function unfreezeUser(){

        $uid = input('post.uid/d');

        if ($uid <= 0) {
            return $this->error(Status::FORM_PARAM_ERROR, '参数错误: 无效的用户id');
        }

        return $this->success('解冻用户成功', $this->service()->unfreezeUser($uid));

    }

    /**
     * 重置用户密码
     * @api post:admin/UserManage/user/reset
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function resetUser(){
        
        $uid = input('post.uid/d');

        if ($uid <= 0) {
            return $this->error(Status::FORM_PARAM_ERROR, '参数错误: 无效的用户id');
        }

        return $this->success('用户密码重置成功', $this->service()->resetUserPassword($uid));
    }

}