<?php

namespace app\api\service;

use think\Db;
use think\Controller;

use app\common\Utils;
use app\common\Authcode;
use app\lib\exception\Status;
use app\lib\exception\ApiException as Error;

use app\api\model\AdminLog;
use app\api\model\SystemCounter;
use app\api\model\User as UserM;
use app\api\model\Admin as AdminM;
use app\api\validate\User as UserVal;
use app\api\model\UserAuth as UserAuthM;
use app\api\validate\UserAuth as UserAuthVal;

class User {
    
    /**
     * 当前登录的用户模型
     * @var AdminM|UserM
     */
    protected $user;

    /**
     * 当前登录用户是否为管理员
     * @var boolean
     */
    protected $isAdmin = false;

    /**
     * 构造函数,构造当前登录用户模型
     * @author lwtting <smlling@hotmail.com>
     * @param AdminM|UserM              $user 用户模型|管理员模型
     */
    public function __construct ($user = null) {
        
        if ($user instanceof AdminM || $user instanceof UserM) {
            if ($user instanceof AdminM) {
                $this->isAdmin = true;
            }
            $this->user = $user;
        }

    }

    /**
     * 检查用户状态(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param UserM $user           用户模型
     * @return void
     */
    protected function checkUserStatus ($user) {

        if (is_null($user)) {
            throw new Error(Status::USER_NOT_EXIST);
        }

        if ($user->isAbandon()) {
            throw new Error(Status::USER_ABANDON);
        }
    }

    /**
     * 获取用户信息(admin|user)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid  用户uid
     * @return json         用户信息
     */ 
    public function getUserInfo ($uid = 0) {

        if ($uid === 0) {
            if (!$this->isAdmin) {
                return $this->user->filter(true);
            } else {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的用户id');
            }
        }
        
        if ($uid === $this->user->id && !$this->isAdmin) {
            return $this->user->filter(true);
        }

        $user = UserM::get($uid, true);

        if (is_null($user)) {
            throw new Error(Status::USER_NOT_EXIST);
        }

        if ($this->isAdmin) {
            return $user->filter(false, true);
        } else {
            return $user->filter(false);
        }

    }

    /**
     * 获取用户列表(admin only)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $page                 页码
     * @param integer $limit                页限
     * @param string $status                指定用户状态available|freezed|abandon
     * @param integer $uid                  指定用户uid
     * @param string $sort_condition        指定排序依据
     * @param string $sort_type             指定排序类型
     * @return array
     */
    public function getUserList ($page, $limit, $status = 'all', $uid = 0, $sort_condition = 'register_time', $sort_type = 'desc') {

        if (is_numeric($uid) && $uid > 0) {
            return [
                'limit' => $limit,
                'page'  => 1,
                'total_page'    => 1,
                'list'  => [
                    $this->getUserInfo($uid)
                ]
            ];
        }

        $validate = new UserVal;
        if (!$validate->scene('sort')->check(compact('sort_condition', 'sort_type'))) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        if ('all' === $status) {
            $total_count = (new System)->userCount();
            $select = UserM::where(true);
        } else if ('available' === $status) {
            $select = UserM::where('unfreeze_time = 0 AND abandon_time = 0');
        } else if ('freezed' === $status) {
            $select = UserM::where('unfreeze_time > 0 AND abandon_time = 0');
        } else if ('abandon' === $status) {
            $select = UserM::where('abandon_time > 0');
        } else {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的统计类型');
        }
        
        $select_copy = clone $select;

        if (!isset($total_count)) {
            $total_count = $select->count();
        }
        

        $user_list = $select_copy
                    ->page($page, $limit)
                    ->order($sort_condition, $sort_type)
                    ->select();
        $data = [];
        $data['limit'] = $limit;
        $data['page'] = $page;
        $data['total_page'] = ceil($total_count / $limit);
        $data['list'] = [];
        foreach ($user_list as $user) {
            $data['list'][] = $user->filter(false, true);
        }

        return $data;
    }

    

    /**
     * 冻结用户(admin only)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid
     * @return array
     */
    public function freezeUser ($uid, $freeze_reason, $freeze_time) {

        $user = UserM::get($uid, true);

        if (!$user) {
            throw new Error(Status::USER_NOT_EXIST);
        }
        
        if ($user->isFreezed()) {
            throw new Error(Status::USER_FREEZED);
        }

        $validate = new UserVal;
        if (!$validate->scene('freeze')->check(compact('freeze_reason', 'freeze_time'))) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        Db::startTrans();
        try {
            $user->freeze($freeze_reason, $freeze_time);
            // 更新系统计数器
            SystemCounter::freezeUser();
            // 记录日志
            AdminLog::log($this->user->username, '冻结用户"' . $user->username . '"成功');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        return $user->filter(false, true);

    }

    /**
     * 解冻用户(admin only)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid
     * @return array
     */
    public function unfreezeUser ($uid) {

        $user = UserM::get($uid, true);

        if (!$user) {
            throw new Error(Status::USER_NOT_EXIST);
        }
        
        if (!$user->isFreezed()) {
            throw new Error(Status::USER_NOT_FREEZED);
        }

        Db::startTrans();
        try {
            $user->unfreeze();
            // 更新系统计数器
            SystemCounter::unfreezeUser();
            // 记录日志
            AdminLog::log($this->user->username, '解冻用户"' . $user->username . '"成功');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        return $user->filter(false, true);

    }

    /**
     * 重置用户密码(admin only)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid
     * @return array
     */
    public function resetUserPassword ($uid) {

        // 获取用户模型
        $user = UserM::get($uid, true);

        // 检查用户是否绑定手机状态
        if (!$user->isBindPhone()) {
            throw new Error(Status::USER_NOT_BAND_PHONE);
        }

        $userAuthPhone = UserAuthM::where('uid = ? AND identity_type = ? AND dissolved = 0', [$user->id, 'phone'])->find();

        if ($user->isSetUsername()) {
            $userAuthUsername = UserAuthM::where('uid = ? AND identity_type = ? AND dissolved = 0', [$user->id, 'username'])->find();
        }

        // 生成一个十二位的随机密码
        $new_password = Utils::generateUniqueKey(12);

        Db::startTrans();

        try {
            $user->updatePasswordMTime();
            $userAuthPhone->setCredential(md5($new_password));
            if ($user->isSetUsername()) {
                $userAuthUsername->setCredential(md5($new_password));
            }
            // 记录日志
            AdminLog::log($this->user->username, '重置用户"' . $user->username . '"密码成功');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        return [
            'new_password' => $new_password
        ];
    }

    

    /**
     * 手机注册(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param string $phone                 手机号
     * @param string $password              密码
     * @param string $ip                    ip地址
     * @param string $device_identifier     设备标识
     * @return array                        token
     */
    public function registerUserByPhone ($phone, $password, $ip, $device_identifier) {

        $data = [
            'identity_type'     => 'phone',
            'identifier'        => $phone, 
            'credential'        => $password
        ];

        // 用验证器验证数据
        $validate = new UserAuthVal;
        if (!$validate->scene('addPhone')->check($data)) {
            if (UserAuthVal::IDENTITIER_EXIST === $validate->getError()) {
                throw new Error(Status::PHONE_EXIST);
            }
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        // 检查设备标识
        if (!$device_identifier) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无法识别的设备');
        }
        
        $user = new UserM;
        $userAuth = new UserAuthM;
        
        Db::startTrans();

        try {
            $user->add('phone', $phone, $ip, $device_identifier);
            $userAuth->add($user->id, 'phone', $phone, md5($password));
            // 更新系统计数器
            SystemCounter::addUser();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        $data = [
            'uid'               => $user->id,
            'role'              => 'user',
            'generate_time'     => time(),
            'device_identifier' => $device_identifier
        ];

        $token = Utils::generateUniqueKey();
        // token有效期2天
        cache('token_' . $token, $data, 2 * 24 * 3600);

        return [
            'token'     => $token
        ];
    }

    /**
     * 设置用户头像(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param string $avatarPath       头像路径
     * @return array                    用户信息
     */
    public function setAvatar ($avatarPath) {
        
        return $this->user->setAvatar($avatarPath)->filter();

    }

    /**
     * 设置用户资料(user only)
     * @param array $userInfo           用户信息
     * @return array                    更新/设置用户信息后的用户信息
     */
    public function setInfo ($userInfo) {

        $validate = new UserVal;
        if (!$validate->scene('setInfo')->check($userInfo)) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }
        
        return $this->user->setInfo($userInfo)->filter();
    }

    /**
	 * 获取头像
	 * @param integer $uid	用户id
	 * @return image
	 */
	public function getAvatar($uid) {
		
		$user = UserM::get($uid, true);
		if (!$user) {
			return [];
		}

		return $user->getAvatarPath();
    }
    
    /**
     * 用户登录(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param string $identity_type         验证类型
     * @param string $identifier            身份标识
     * @param string $credential            口令
     * @param string $device_identifier     设备标识
     * @param string $ip                    登录ip
     * @return array                        token
     */
    public function login ($identity_type, $identifier, $credential, $device_identifier, $ip) {

        // 用验证器验证参数完整性
        $validate = new UserAuthVal;
        if (!$validate->scene('checkLogin')->check(compact('identity_type', 'identifier', 'credential'))) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        // 获取验证模型
        $userAuth = UserAuthM::where('identity_type = ? AND identifier = ?', [$identity_type, $identifier])->find();

        // 用户不存在
        if (!$userAuth) {
            throw new Error(Status::USER_NOT_EXIST);
        }

        // 获取用户模型
        $user = UserM::get($userAuth->uid, true);

        // 手机登录或用户名登录
        if ('phone' === $identity_type || 'username' === $identity_type) {
            $login_failed_times = $user->login_failed_times;
            $unfreeze_time = $user->unfreeze_time;
            if ($unfreeze_time && time() < $unfreeze_time) {
                throw new Error(Status::USER_FREEZED,'由于' . $user->freezeReason() . '，用户已被冻结至' . $user->unfreezeTime()); 
            }
            // 口令正确
            if (md5($credential) === $userAuth->credential) {
                // 检查登录设备
                if ($user->isAllowDevice($device_identifier)) {
                    // 当前设备标识与上次登录设备标识相同

                    // 若在登录成功前用户处于冻结状态则需要更新系统计数器
                    if ($user->isFreezed()) {
                        Db::startTrans();
                        try {
                            // 更新登录信息
                            $user->updateLoginInfo();
                            // 更新系统计数器
                            SystemCounter::unfreezeUser();
                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                            throw $e;
                        }
                    } else {
                        // 更新登录信息
                        $user->updateLoginInfo();
                    }
                    

                    // 生成token
                    $data = [
                        'uid'               => $user->id,
                        'role'              => 'user',
                        'generate_time'     => time(),
                        'device_identifier' => $device_identifier
                    ];

                    $token = Utils::generateUniqueKey();

                    // token有效期2天
                    cache('token_' . $token, $data, 2 * 24 * 3600);

                    return [
                        'token'     => $token
                    ];
                } else {
                    // 当前设备标识与上次登录设备标识不同
                    (new Authcode($user->phone, $device_identifier, $ip))->send(Authcode::KEY_DEVICE);
                    throw new Error(Status::NEW_DEVICE_DETECTED, '此次登录需要短信验证码,我们已向您的手机' . $user->getPrivatePhone() . '发送短信验证码');

                }
            // 口令错误
            } else {
                // 连续5次及以上密码错误 冻结账户
                if ($login_failed_times >= 4) {
                    $login_failed_times += 1;
                    $freeze_time = ($login_failed_times - 4) * 3 * 60;//冻结时间，连续五次密码错误冻结3分钟，6次2*3分钟，7次3*3分钟...
                    Db::startTrans();
                    try {
                        // 更新用户解冻时间
                        $user->updateLoginInfo(false, $freeze_time);
                        // 更新系统计数器
                        SystemCounter::freezeUser();
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        throw $e;
                    }
                    throw new Error(Status::USER_FREEZED,'连续' . $login_failed_times . '次密码错误，用户已冻结至：' . $user->unfreezeTime());
                }
                else{
                    $user->updateLoginInfo(false);
                    throw new Error(Status::USER_PWD_INVAILD);
                }
            }
        } else {
            // TODO 第三方登录
        }

    }

    /**
     * 更新用户设备标识(添加信任设备)(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $phone                用户手机号
     * @param string $device_identifier     设备标识
     * @return array                        token
     */
    public function updateDevice ($phone, $device_identifier) {

        $user = UserM::getByPhone($phone, true);

        // 检查用户状态
        $this->checkUserStatus($user);

        // 账户被冻结 
        // if ($user->unfreeze_time && time() < $user->unfreeze_time) {
        //     throw new Error(Status::USER_FREEZED, '用户由于' . $user->freezeReason() . '被冻结至' . $user->unfreezeTime() . ',当前登录状态已失效');
        // }

        $user->addDevice($device_identifier);

        $token = Utils::generateUniqueKey();
        $data = [
            'uid'               => $user->id,
            'role'              => 'user',
            'generate_time'     => time(),
            'device_identifier' => $device_identifier
        ];
        // token有效期2天
        cache('token_' . $token, $data, 2 * 24 * 3600);

        return [
            'token'     => $token
        ];
    }

    /**
     * 设置用户名(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param string $username
     * @return array            用户信息
     */
    public function setUsername ($username) {

        if ($this->user->isSetUsername()) {
            throw new Error(Status::USER_ALREADY_SET_USERNAME);
        }

        $validate = new UserVal;
        if (!$validate->scene('checkUsername')->check(compact('username'))) {
            if (UserVal::USERNAME_EXIST === $validate->getError()) {
                throw new Error(Status::USERNAME_EXIST);
            }

            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        // 获取账户密码(手机)
        $credential = UserAuthM::where('uid = ? AND identity_type = ? AND dissolved = 0', [$this->user->id, 'phone'])->find()->credential;

        $userAuth = new UserAuthM;

        Db::startTrans();

        try {
            $this->user->setUsername($username);
            $userAuth->add($this->user->id, 'username', $username, $credential);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        
        return $this->user->filter();
    }

    /**
     * 更改用户密码(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param string $old_password      旧密码
     * @param string $new_password      新密码
     * @return void
     */
    public function changePassword ($old_password, $new_password) {

        // 若用户未绑定手机 提示必须绑定手机
        if (!$this->user->phone) {
            throw new Error(Status::PHONE_NOT_BAND);
        }

        $data = [
            'old_credential' => $old_password,
            'new_credential' => $new_password
        ];

        $validate = new UserAuthVal;
        if (!$validate->scene('changePassword')->check($data)) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        $userAuthPhone = UserAuthM::where('uid = ? AND identity_type = ? AND dissolved = 0', [$this->user->id, 'phone'])->find();
        
        // 由于绑定手机/手机注册时 写入认证条目是通过事务处理的 因此此处无需对其做判空
        if ($userAuthPhone->credential !== md5($old_password)) {
            throw new Error(Status::USER_PWD_INVAILD);
        }

        // 如果设置了用户名 那么要将用户使用用户名登录的认证密码一并更改
        if ($this->user->isSetUsername()) {
            $userAuthUsername = UserAuthM::where('uid = ? AND identity_type = ? AND dissolved = 0', [$this->user->id, 'username'])->find();
        }

        Db::startTrans();

        try {
            // 更新密码修改时间 可使其他在线设备强制下线
            $this->user->updatePasswordMTime();
            // 更新密码
            $userAuthPhone->setCredential(md5($new_password));
            // 若设置了用户名 则一并更改使用用户名登录的密码
            if ($this->user->isSetUsername()) {
                $userAuthUsername->setCredential(md5($new_password));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        
    }

    /**
     * 重置用户密码(user only)
     * 需要先获取验证码
     * @author lwtting <smlling@hotmail.com>
     * @param string $phone             用户手机号
     * @param string $new_password      新密码
     * @return void
     */
    public function resetPassword ($phone, $new_password) {

        $validate = new UserAuthVal;
        if (!$validate->scene('checkPassword')->check(['credential' => $new_password])) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        // 获取用户模型
        $user = UserM::where('phone = ? AND abandon_time = 0', [$phone])->find();

        // 检查用户状态
        $this->checkUserStatus($user);

        $userAuthPhone = UserAuthM::where('uid = ? AND identity_type = ? AND dissolved = 0', [$user->id, 'phone'])->find();

        if ($user->isSetUsername()) {
            $userAuthUsername = UserAuthM::where('uid = ? AND identity_type = ? AND dissolved = 0', [$user->id, 'username'])->find();
        }

        Db::startTrans();

        try {
            $user->updatePasswordMTime();
            $userAuthPhone->setCredential(md5($new_password));
            if ($user->isSetUsername()) {
                $userAuthUsername->setCredential(md5($new_password));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 搜索用户
     * @author lwtting <smlling@hotmail.com>
     * @param string $keyword
     * @return array
     */
    public function searchUser ($keyword) {

        $user_list = UserM::where('abandon_time = 0 AND (username LIKE ? OR nickname LIKE ?)', ['%' . $keyword . '%', '%' . $keyword . '%'])
                            ->limit(50)
                            ->cache('search_user_' . $keyword, 120)
                            ->select();

        $data = [];
        foreach ($user_list as $user) {
            $info = $user->filter(false);
            $data[$user->id] = $info;
        }

        return $data;
    }

}