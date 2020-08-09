<?php

namespace app\api\model;
use think\Model;

class Admin extends Model{

    // 登录认证方式
    const IDENTITY_TYPE = ['username', 'phone'];

    // 管理员身份
    const ROLE_SUPER_ADMIN = 1;
    const ROLE_ADMIN =2;

    const ADMIN_ROLE = [
        self::ROLE_SUPER_ADMIN          => 'superadmin',
        self::ROLE_ADMIN                => 'admin'
    ];

    // 冻结原因
    const FREEZE_PWD_INCORRECT          = 1;
    const FREEZE_PRIVILEGE_ABUSE        = 2;

    const FREEZE_REASON = [
        1 => '多次密码错误',
        2 => '滥用职权'
    ];

    /**
     * 是否被冻结
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isFreezed () {
        return $this->freeze_reason ? true :false;
    }

    /**
     * 获取冻结原因
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function freezeReason () {
        if ($this->freeze_reason === 0) {
            return '未被冻结';
        } else if (isset(self::FREEZE_REASON[$this->freeze_reason])) {
            return self::FREEZE_REASON[$this->freeze_reason];
        } else {
            return '未知原因';
        }
    }

    /**
     * 获取可读性的身份角色
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function role () {
        if (isset(self::ADMIN_ROLE[$this->role])) {
            return self::ADMIN_ROLE[$this->role];
        } else {
            return '未知身份';
        }
    }

    /**
     * 是否是超级管理员
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isSuper () {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * 更新登录信息
     * @author lwtting <smlling@hotmail.com>
     * @param boolean $success      是否登录成功
     * @param boolean $freeze       是否冻结 当success=false时有效
     * @param string  $ip           ip地址
     * @return Admin
     */
    public function updateLoginInfo ($success = true, $freeze = false, $ip = null) {
        if ($success){
            //更新登陆时间
            $this->last_login_time = time();
            //重置连续登录失败计数
            $this->login_failed_times = 0;
            $this->last_login_ip = $ip;
        } else {
            $this->login_failed_times++;
            $this->freeze_reason = $freeze ? self::FREEZE_PWD_INCORRECT : 0;// 多次密码错误
        }
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 获取可读性的上次登录时间
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function lastLoginTime () {
        return $this->last_login_time ? date('Y-m-d h:i:s', $this->last_login_time) : '从未登录';
    }

    /**
     * 过滤信息
     * @author lwtting <smlling@hotmail.com>
     * @param boolean $isSuperAdmin      超级管理员标识
     * @return array
     */
    public function filter ($isSuperAdmin = false) {
        $info = $this->toArray();
        $info['last_login_time'] = $this->lastLoginTime();
        $info['add_time'] = date("Y-m-d H:i:s", $this->add_time);
        $info['role'] = $this->role();
        unset($info['password']);
        if ($isSuperAdmin) {
            $info['freeze_reason'] = $this->freezeReason();
        } else {
            unset(
                $info['login_failed_times'],
                $info['freeze_reason'],
                $info['last_login_ip']
            );
        }
        return $info;
    }

    /**
     * 更新密码更改时间
     * 可用于用于强制下线
     * @author lwtting <smlling@hotmail.com>
     * @return Admin
     */
    public function updatePasswordMTime () {
        $this->password_mtime = time();
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 设置密码
     * @author lwtting <smlling@hotmail.com>
     * @param string $password      密码
     * @return Admin
     */
    public function setPassword ($password) {
        $this->password = $password;
        $this->password_mtime = time();
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 设置昵称
     * @author lwtting <smlling@hotmail.com>
     * @param string $nickname      昵称
     * @return Admin
     */
    public function setNickname ($nickname) {
        $this->nickname = $nickname;
        $this->isUpdate(true)->save();
        return $this;
    }
}