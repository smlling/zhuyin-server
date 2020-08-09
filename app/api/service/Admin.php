<?php

namespace app\api\service;

use think\Db;

use app\common\Utils;
use app\lib\exception\Status;
use app\lib\exception\ApiException as Error;

use app\api\model\AdminLog;
use app\api\model\Admin as AdminM;
use app\api\validate\Admin as AdminVal;

class Admin {

    

    /**
     * 当前登录的管理员用户模型
     * @var AdminM
     */
    protected $admin;

    /**
     * 构造函数,构造当前登录管理员模型
     * @author lwtting <smlling@hotmail.com>
     * @param AdminM $admin
     */
    public function __construct ($admin = null) {
        
        if ($admin instanceof AdminM) {
            $this->admin = $admin;
        } 

    }

    /**
     * 管理员登录
     * @author lwtting <smlling@hotmail.com>
     * @param string $identity_type         验证类型phone|username
     * @param string $identifier            身份标识
     * @param string $credential            口令
     * @param string $ip                    ip地址
     * @return array                        token
     */
    public function login ($identity_type, $identifier, $credential, $ip) {
        
        // 验证登录参数
        $validate = new AdminVal;
        if (!$validate->scene('login')->check(compact('identity_type', 'identifier', 'credential'))) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        // 获取管理员模型
        if ('phone' === $identity_type) {
            $admin = AdminM::getByPhone($identifier);
        } else if ('username' === $identity_type) {
            $admin = AdminM::getByUsername($identifier);
        }

        // 检查管理员状态
        // 管理员不存在
        if (is_null($admin)) {
            throw new Error(Status::USER_NOT_EXIST);
        }
        
        // 管理员被冻结
        if ($admin->isFreezed()) {
            throw new Error(Status::USER_FREEZED,'由于' . $admin->freezeReason() . ',管理员账号已被冻结,详情咨询维护人员');
        }

        // 口令正确
        if (md5($credential) === $admin->password) {

            Db::startTrans();
            try {
                // 更新登录信息
                $admin->updateLoginInfo(true, false, $ip);
                // 记录日志
                AdminLog::log($admin->username, '从' . $ip . '登录成功');
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            

            // 生成token
            $data = [
                'uid'               => $admin->id,
                'role'              => $admin->role(),
                'generate_time'     => time(),
                'device_identifier' => $ip
            ];

            $token = Utils::generateUniqueKey();

            // token有效期12小时
            cache('token_' . $token, $data, 12 * 3600);

            return [
                'token'     => $token
            ];
        // 口令错误
        } else {
            // 连续5次密码错误 冻结账户
            if ($admin->login_failed_times >= 4) {
                
                Db::startTrans();
                try {
                    // 更新登录信息
                    $admin->updateLoginInfo(false, true, $ip);
                    // 记录日志
                    AdminLog::log($admin->username, '5次密码错误被冻结,ip:' . $ip);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    throw $e;
                }
                throw new Error(Status::USER_FREEZED,'连续' . $login_failed_times . '次密码错误,当前管理账户已被冻结');
            } else {
                $admin->updateLoginInfo(false);
                throw new Error(Status::USER_PWD_INVAILD);
            }
        }
    }

    /**
     * 获取管理员信息
     * @author lwtting <smlling@hotmail.com>
     * @param integer $id                   指定管理员id
     * @return array                        用户信息
     */
    public function getAdminInfo ($id = 0) {

        if (is_numeric($id) && $id > 0) {
            return (AdminM::get($id, true))->filter($this->admin->isSuper());
        } else {
            return $this->admin->filter($this->admin->isSuper());
        }

    }

    /**
     * 重置管理员密码
     * @author lwtting <smlling@hotmail.com>
     * @param integer $id
     * @return void
     */
    public function resetPassword ($id) {

        if (is_numeric($id) && $id > 0) {

            // 验证当前登录的是否是超级管理员
            if (!$this->admin->isSuper()) {
                throw new Error(Status::NEED_PRIVILEGE);
            }

            // 获取模型
            $admin = AdminM::get($id);

            if (is_null($admin)) {
                throw new Error(Status::USER_NOT_EXIST);
            }

            if ($admin->isSuper()) {
                throw new Error(Status::NEED_PRIVILEGE);
            }

            Db::startTrans();

            try {
                // 生成一个十二位的随机密码
                $new_password = Utils::generateUniqueKey(12);
                // 更新密码
                $admin->setPassword(md5($new_password));
                // 记录日志
                AdminLog::log($this->admin->username, '重置管理员: ' . $admin->username . '密码');
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }

            return $admin->filter(true);

        } else {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的管理员id');
        }

    }

    /**
     * 更改用户密码
     * @author lwtting <smlling@hotmail.com>
     * @param string $old_password      旧密码
     * @param string $new_password      新密码
     * @return void
     */
    public function changePassword ($old_password, $new_password) {

        $validate = new AdminVal;
        if (!$validate->scene('changePassword')->check(compact('old_password', 'new_password'))) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }
        
        if ($this->admin->password !== md5($old_password)) {
            throw new Error(Status::USER_PWD_INVAILD);
        }

        // 更新密码
        $this->admin->setPassword(md5($new_password));
        
    }

    /**
     * 更改昵称
     * @author lwtting <smlling@hotmail.com>
     * @param string $nickname          昵称
     * @return array                    管理员信息
     */
    public function changeNickname ($nickname) {

        $validate = new AdminVal;
        if (!$validate->scene('changeNickname')->check(compact('nickname'))) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        // $super_admin = ($this->admin->role === AdminM::ROLE_SUPER_ADMIN);

        return $this->admin->setNickname($nickname)->filter($this->admin->isSuper());
    }
}