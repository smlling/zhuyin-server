<?php

namespace app\http\middleware;

use app\lib\exception\Status;
use app\api\model\Admin;
use app\api\model\User;
use app\lib\exception\ApiException as Error;

class RoleCheck {

    public function handle($request, \Closure $next, $role){

        if ('guest' === $role) {

            $device_identifier = $request->header('Device_Identifier');
            $request->device_id = $device_identifier;

            if (!$device_identifier) {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无法识别的设备');
            }

            return $next($request);
        }

        // 获取token
        $token = request()->header('Authorization') ?: input('post.token/s');

        if (!$token) {
            throw new Error(Status::LOGIN_EXPIRE);
        }
        
        $redis_key = 'token_' . $token;
        $userData = cache($redis_key);

        // token时效检查
        if (!$userData) {
            throw new Error(Status::LOGIN_EXPIRE);
        }

        // 权限检查
        if ('admin' === $role) {

            if (('admin' === $userData['role']) || ('superadmin' === $userData['role'])) {
                $device_identifier = $request->ip();
                $user = Admin::get($userData['uid']);
            } else {
                throw new Error(Status::NEED_PRIVILEGE);
            }

        } else if (('user' === $role) && ('user' === $userData['role'])) {
            $device_identifier = $request->header('Device_Identifier');
            $user = User::get($userData['uid']);
        } else if (('superadmin' === $role) && ('superadmin' === $userData['role'])) {
            $device_identifier = $request->ip();
            $user = Admin::get($userData['uid']);
        } else if ('all' === $role) {
            if ('user' === $userData['role']) {
                $device_identifier = $request->header('Device_Identifier');
                $user = User::get($userData['uid']);
            } else {
                $device_identifier = $request->ip();
                $user = Admin::get($userData['uid']);
            }
        }
        else {
            throw new Error(Status::NEED_PRIVILEGE);
        }

        // 若用户因某种原因不存在了(数据表被破坏)
        if (!$user) {
            cache($redis_key, null);
            throw new Error(Status::USER_NOT_EXIST);
        }

        // 账户被冻结 
        if ($user->isFreezed()) {
            cache($redis_key, null);
            throw new Error(Status::USER_FREEZED, '用户由于"' . $user->freezeReason() . '"已被冻结' . ('user' === $userData['role']) ? ('至' . date('Y-m-d h:i:s', $user->unfreeze_time)) : ',详情咨询维护人员');
        }

        // 账户密码更改
        if ($user->password_mtime > $userData['generate_time']) {
            cache($redis_key, null);
            throw new Error(Status::LOGIN_EXPIRE, '由于账户密码更改,当前登录状态已失效,请重新登录');
        }

        // 设备检查
        if (!$device_identifier || $device_identifier !== $userData['device_identifier']) {
            cache($redis_key, null);
            throw new Error(Status::NEW_DEVICE_DETECTED, '检测到设备更换,当前登录状态已失效,请重新登录');
        }

        // 向控制器传递当前登录用户模型
        $request->loginUserM = $user;
        $request->device_id = $device_identifier;

        // 延长token时效
        cache($redis_key, $userData, 12 * 3600);

        return $next($request);
    }
}
