<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use \think\Db;
use app\api\model\Admin;

class InitSuperAdmin extends Command
{
    protected function configure () {
        // 指令配置
        $this->setName('initsuperadmin');
        // 设置参数
        
    }

    protected function execute (Input $input, Output $output) {
        $admin = Admin::getByUsername('admin');
        if (!$admin) {
            Admin::create([
                'username' => 'admin',
                'password' => md5('zhuyin_admin'),
                'nickname' => '超级管理员',
                'role' => 1,
                'add_time' => time()
            ]);
        }
        else{
            $admin->password = md5('zhuyin_admin');
            $admin->save();
        }
        $output->writeln('初始化超级管理员成功');
    }
}
