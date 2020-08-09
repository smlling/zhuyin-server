<?php

namespace app\api\model;
use think\Model;

class AdminLog extends Model{

    public static function log($admin, $action){
        AdminLog::create([
            'admin' => $admin,
            'action' => $action,
            'time' => time(),
            'ip' => request()->ip()
        ]);
    }
}