<?php

namespace app\admin\controller;

use think\Controller;

use app\api\service\System;

class SystemManage extends Controller{

    protected $middleware = [
        'RoleCheck:admin' => [

        ]
    ];
    
    /**
     * 系统总览
     * @api get:admin/systemmanage/dashboard
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function Dashboard(){

        return $this->success('获取系统状态成功',(new System)->systemCounter());
    }
}