<?php

namespace app\api\model;

use think\Model;
use app\common\Utils;
use app\lib\exception\Status;
use app\lib\exception\ApiException as Error;

class User extends Model{

    // 验证类型
    const IDENFITY_TYPE                     = ['phone', 'weibo', 'wechat', 'qq'];

    // 冻结原因
    const FREEZE_NOT_FREEZE                 = 0;
    const FREEZE_ILLEGAL_COMMENT            = 1;
    const FREEZE_INSULT_COMMENT             = 2;
    const FREEZE_REPORT                     = 3;
    const FREEZE_ACTION_EXCEPTION           = 4;
    const FREEZE_PWD_INCORRECT              = 5;
    const FREEZE_PWD_RESET                  = 6;
    
    const FREEZE_REASON = [
        'limit'                             => [
            self::FREEZE_NOT_FREEZE         => '未被冻结',
            self::FREEZE_PWD_INCORRECT      => '多次密码错误',
            self::FREEZE_PWD_RESET          => '重置密码'
        ],
        self::FREEZE_ILLEGAL_COMMENT        => '涉嫌发布黄赌毒等违法信息',
        self::FREEZE_INSULT_COMMENT         => '涉嫌发布辱骂他人的言论',
        self::FREEZE_REPORT                 => '账户被多人举报',
        self::FREEZE_ACTION_EXCEPTION       => '账户活动异常',
        
    ];
    
    // 冻结时长
    const FREEZE_HOUR                       = 1;
    const FREEZE_DAY                        = 2;
    const FREEZE_WEEK                       = 3;
    const FREEZE_MONTH                      = 4;
    const FREEZE_YEAR                       = 5;
    const FREEZE_FOREVER                    = 5;

    const FREEZE_TIME = [
        self::FREEZE_HOUR                   => 60 * 60,                         //一小时
        self::FREEZE_DAY                    => 60 * 60 * 24,                    //一天
        self::FREEZE_WEEK                   => 60 * 60 * 24 *7,                 //一周
        self::FREEZE_MONTH                  => 60 * 60 * 24 * 7 * 30,           //一月
        self::FREEZE_YEAR                   => 60 * 60 * 24 * 7 * 365,          //一年
        self::FREEZE_FOREVER                => 4102415999000                    //永久
    ];

    const SORT_CONDITION = ['register_time', 'username', 'last_login_time', 'post_activity_count_all', 'post_comment_count_all'];
    

    /**
     * 添加用户记录
     * 此方法调用前必须先到UserAuth表中查重
     * 否则会出现同一用户的不同验证方式对应了不同账号主体的情况
     * @author lwtting <smlling@hotmail.com>
     * @param string $identity_type           标识类型(注册类型)
     * @param string $identitier              标识
     * @param string $ip                      ip地址
     * @param string $device_identifier       设备标识(手机注册的时候)
     * @return User
     */
    public function add ($identity_type, $identitier, $ip, $device_identifier) {

        if (!in_array($identity_type, self::IDENFITY_TYPE)) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 错误的验证类型');
        }

        if (isset($this->id)) {
            unset($this->id);
        }

        // 生成一个13位的随机用户名
        $id = Utils::generateUniqueKey(13);
        $username = 'zy_' . $id;
        $nickname = '用户' . $id;
        $this->username = $username;
        $this->nickname = $nickname;
        $this->$identity_type = $identitier;
        $this->register_time = time();
        $this->last_login_time = time();
        $this->last_login_ip = $ip;
        $this->device_identifier = json_encode([$device_identifier]);
        $this->isUpdate(false)->save();

        return $this;
    }

    /**
     * 是否被冻结
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isFreezed() {
        return $this->unfreeze_time ? true : false;
    }

    /**
     * 获取冻结原因
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function freezeReason () {
        $freeze_reason = $this->freeze_reason;
        if (isset(self::FREEZE_REASON[$freeze_reason])) {
            return self::FREEZE_REASON[$freeze_reason];
        }
        if (isset(self::FREEZE_REASON['limit'][$freeze_reason])) {
            return self::FREEZE_REASON['limit'][$freeze_reason];
        }
        return '未知原因';
    }

    /**
     * 获取账户解冻时间
     * @author lwtting <smlling@hotmail.com>
     * @return mixed        解冻时间(年-月-日 时:分:秒) 若未被冻结则返回未被冻结
     */
    public function unfreezeTime() {
        return $this->unfreeze_time ? date('Y-m-d h:i:s', $this->unfreeze_time) : '未被冻结';
    }

    /**
     * 设置头像路径
     * @author lwtting <smlling@hotmail.com>
     * @param string $avatar_path    头像文件路径(不需要配置文件中配置的前缀)
     * @return User
     */
    public function setAvatar ($avatar_path) {
        if ($avatar_path && file_exists(config('settings.upload.avatar_path') . $avatar_path)) {
            $this->avatar = $avatar_path;
            $this->isUpdate(true)->save();
        } else {
            throw new Error(Status::FILE_NOT_EXIST);
        }

        return $this;
        
    }

    /**
     * 获取头像物理路径
     * @author lwtting <smlling@hotmail.com>
     * @return array
     */
    public function getAvatarPath () {
        if (!$this->avatar) {
            // 未设置头像则显示默认头像
            return [
                'large' => 'static/images/default_avatar.png',
                'small'  => 'static/images/default_avatar.png'
            ];
        }

        $avatar_path = config('settings.upload.avatar_path') . $this->avatar;
        return [
            'large' => $avatar_path,
            'small'  => $avatar_path . '.thumb'
        ];
    }

    /**
     * 获取头像访问路径
     * @author lwtting <smlling@hotmail.com>
     * @return array
     */
    public function getAvatarUrl () {
        return [
            'large' => config('settings.host') . 'home/avatar?id=' . $this->id . '&type=large',
            'small'  => config('settings.host') . 'home/avatar?id=' . $this->id . '&type=small',
        ];
    }

    /**
     * 账号是否注销
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isAbandon () {
        return $this->abandon_time ? true : false;
    }

    /**
     * 是否设置了用户名
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isSetUsername () {
        return $this->username_mtime ? true : false;
    }

    /**
     * 是否绑定了手机号
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isBindPhone () {
        return $this->phone ? true : false;
    }

    /**
     * 获取年龄
     * @author lwtting <smlling@hotmail.com>
     * @return integer|string
     */
    public function getAge () {
        return $this->age ? $this->age : '保密';
    }

    /**
     * 获取可读性的上次登录时间
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function lastLoginTime() {
        return $this->last_login_time ? date('Y-m-d h:i:s', $this->last_login_time) : '从未登录';
    }

    /**
     * 获取可读性的上次更改密码时间
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function passwordMTime () {
        return $this->password_mtime ? date('Y-m-d h:i:s', $this->password_mtime) : '从未更改密码';
    }

    /**
     * 获取可读性的用户名设置时间
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function usernameMTime () {
        return $this->username_mtime ? date('Y-m-d h:i:s', $this->username_mtime) : '未设置用户名';
    }

    /**
     * 获取可读性的设备标识列表
     * @author lwtting <smlling@hotmail.com>
     * @return array
     */
    public function deviceIDList () {
        return json_decode($this->device_identifier);
    }

    /**
     * 过滤信息
     * @author lwtting <smlling@hotmail.com>
     * @param boolean $isSelf   是否是用户本人
     * @param boolean $admin    管理员模式
     * @return array
     */
    public function filter ($isSelf = true, $admin = false){
        $info = $this->getData();
        $info['avatar'] = $this->getAvatarUrl();
        $info['register_time'] = date("Y-m-d H:i:s",$info['register_time']);
        $info['is_abandon'] = $this->isAbandon();
        $info['is_set_username'] = $this->isSetUsername();
        $info['age'] = $this->getAge();
        
        if ($admin) {
            $info['is_freezed'] = $this->isFreezed();
            if ($this->isFreezed()) {
                $info['unfreeze_time'] = date("Y-m-d H:i:s",$this->unfreeze_time);
                $info['freeze_reason'] = $this->freezeReason();
            }
            if ($this->isAbandon()) {
                $info['abandon_time'] = date("Y-m-d H:i:s",$this->abandon_time);
            }
            $info['username_mtime'] = $this->usernameMTime();
            $info['last_login_time'] = $this->lastLoginTime();
            $info['password_mtime'] = $this->passwordMTime();
            $info['device_identifier'] = $this->deviceIDList();

        }else{
            $info['phone'] = $this->getPrivatePhone();
            // 非当前登录用户获取获取
            if (!$isSelf) {
                unset (
                    $info['phone'],
                    $info['weibo'],
                    $info['wechat'],
                    $info['qq'],
                    $info['password_mtime']
                );
            }
            unset(
                $info['freeze_reason'],
                $info['unfreeze_time'],
                $info['login_failed_times'],
                $info['username_mtime'],
                $info['register_time'],
                $info['device_identifier'],
                $info['abandon_time'],
                $info['last_login_time'],
                $info['last_login_ip'],
                $info['post_activity_count_all'],
                $info['post_comment_count_all']
            );
        }
        return $info;
    }

    /**
     * 设置用户资料
     * @author lwtting <smlling@hotmail.com>
     * @param array $user_info
     * @return User
     */
    public function setInfo ($user_info) {
        
        $this->allowField(['nickname', 'sex', 'age', 'location', 'description'])
            ->isUpdate(true)
            ->save($user_info);

        return $this;
    }
    
    /**
     * 更新登录信息
     * @author lwtting <smlling@hotmail.com>
     * @param boolean $success                登录是否成功
     * @param integer $freeze_time            冻结时间 success=false时需要
     * @param string  $ip                     ip地址
     * @return User
     */
    public function updateLoginInfo ($success = true, $freeze_time = 0, $ip = null) {

        // 登录成功时重置"连续登录失败次数"计数器
        if ($success) {
            $this->login_failed_times = 0;
            $this->unfreeze_time = 0;
            $this->freeze_reason = self::FREEZE_NOT_FREEZE;
            $this->last_login_ip = $ip;
        } else {
            // 登录失败时将"连续登录失败次数自增"
            $this->login_failed_times += 1;
            // 若传入了冻结时间则表示多次密码错误
            // 此时需要写入解冻时间以及冻结原因
            if ($freeze_time) {
                $this->unfreeze_time = time() + $freeze_time;
                $this->freeze_reason = self::FREEZE_PWD_INCORRECT;
            }
        }

        $this->isUpdate(true)->save();

        return $this;
    }

    /**
     * 获取隐藏中间号码的手机号
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function getPrivatePhone () {
        if ($this->phone) {
            return substr($this->phone,0,3)."****".substr($this->phone,8,3);
        }
        
        return '未绑定手机';
    }

    /**
     * 添加信任设备
     * @author lwtting <smlling@hotmail.com>
     * @param string $device_identifier
     * @return User
     */
    public function addDevice ($device_identifier) {

        $cur_device_list = json_decode($this->device_identifier);
        $new_device_list = array_merge($cur_device_list, [$device_identifier]);
        $this->device_identifier = json_encode($new_device_list);
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 设置用户名
     * @author lwtting <smlling@hotmail.com>
     * @param string $usrname
     * @return User
     */
    public function setUsername ($usrname) {
        $this->username = $usrname;
        $this->username_mtime = time();
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 更新密码更改时间
     * 用于当用户更改/重置密码时强制下线已登录的其他设备
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    public function updatePasswordMTime () {
        $this->password_mtime = time();
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 检查是否是信任设备
     * @author lwtting <smlling@hotmail.com>
     * @param string $device_identifier        设备标识
     * @return boolean
     */
    public function isAllowDevice ($device_identifier) {
        $allow_device_list = json_decode($this->device_identifier);
        return in_array($device_identifier, $allow_device_list);
    }
    
    /**
     * 自增发表的动态数量
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    public function incPostActivityCount () {
        $this->post_activity_count++;
        $this->post_activity_count_all++;
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 自减发表的动态数量
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    public function decPostActivityCount () {
        $this->post_activity_count--;
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 自增点赞的动态数量
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    public function incLikeActivityCount () {
        $this->like_activity_count++;
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 自减点赞的动态数量
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    public function decLikeActivityCount () {
        $this->like_activity_count--;
        $this->isUpdate(true)->save();
        return $this;
    }
    /**
     * 自增发表的评论数量
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    public function incCommentActivityCount () {
        $this->post_comment_count++;
        $this->post_comment_count_all++;
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 自减发表的评论数量
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    public function decCommentActivityCount () {
        $this->post_comment_count--;
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 冻结
     * @author lwtting <smlling@hotmail.com>
     * @param integer $freeze_reason
     * @param integer $freeze_time
     * @return User
     */
    public function freeze ($freeze_reason, $freeze_time) {
        $this->freeze_reason = $freeze_reason;
        $this->unfreeze_time = time() + $freeze_time;
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 解冻
     * @author lwtting <smlling@hotmail.com>
     * @return User
     */
    public function unfreeze () {
        $this->freeze_reason = self::FREEZE_NOT_FREEZE;
        $this->unfreeze_time = 0;
        $this->isUpdate(true)->save();
        return $this;
    }
}