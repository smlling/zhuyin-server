<?php

namespace app\common;

use think\facade\Cache;
use app\lib\exception\Status;
use app\lib\exception\ApiException as Error;

class Authcode {

    const KEY_PHONE     = 'phone';
    const KEY_DEVICE    = 'device_id';

    /**
     * 目标手机号
     * @var string
     */
    protected $phone;

    /**
     * 请求来源ip
     * @var string
     */
    protected $ip;

    /**
     * 请求来源设备标识
     * @var string
     */
    protected $device_id;

    /**
     * 验证码
     * @var string
     */
    protected $authcode;

    /**
     * 同手机号当日验证码获取数量
     * @var integer
     */
    protected $smscode_counter_phone;

    /**
     * 同ip当日验证码获取数量
     * @var integer
     */
    protected $smscode_counter_ip;

    /**
     * 同设备当日验证码获取数量
     * @var integer
     */
    protected $smscode_counter_device;

    /**
     * 构造函数
     * @param string $phone
     * @param string $device_id
     * @param string $ip
     */
    public function __construct ($phone, $device_id, $ip) {
        $this->phone = $phone;
        $this->device_id = $device_id;
        $this->ip = $ip;
    }

    /**
     * 手机号校验
     * @author lwtting <smlling@hotmail.com>
     * @return   boolean        手机号是否合法
     */
    protected function checkPhoneNumber () {
        return preg_match("/^1[345789]{1}\d{9}$/",$this->phone) ? true : false;
    }


    /**
     * 当天发送条数限制检查
     * @author lwtting <smlling@hotmail.com>
     * @return boolean          是否超限
     */
    protected function checkSendLimit () {

        // 获取目标手机号今日发送量
        $this->smscode_counter_phone = cache('smscode_counter_' . $this->phone);
        // 获取请求来源ip今日发送量
        $this->smscode_counter_ip = cache('smscode_counter_' . $this->ip);
        // 获取请求来源设备今日发送量
        $this->smscode_counter_device = cache('smscode_counter_' . $this->device_id);

        // 没有找到发送记录
        if (!$this->smscode_counter_phone && !$this->smscode_counter_ip) {
            return true;
        }

        // 同一ip发送限制
        if($this->smscode_counter_ip) {
            // 发送量超限
            if ($this->smscode_counter_ip >= config('settings.authcode.smscode_limit_ip')) {
                return false;
            }
        }

        // 同一手机号发送限制
        if ($this->smscode_counter_phone) {
            // 发送量超限
            if ($this->smscode_counter_phone >= config('settings.authcode.smscode_limit_phone')) {
                return false;
            }
        }

        return true;
    }

    /**
     * 发送频率检查
     * @author lwtting <smlling@hotmail.com>
     * @return boolean          是否获取频繁
     */
    protected function checkSendFrquency () {
        if (cache('smscode_interval_' . $this->phone) || cache('smscode_interval_' . $this->ip)) {
            return false;
        }
        return true;
    }

    /**
     * 发送短信验证码
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    protected function sendSMS () {
        // 生成一个随机验证码
        $this->authcode = Utils::generateRandCode();
        // Utils::sendSMS($this->phone, '{"code":"'. $this->authcode .'"}');
    }

    /**
     * 缓存验证码相关信息
     * @author lwtting <smlling@hotmail.com>
     * @param integer $key      缓存key类型
     * @return string           此条验证码的缓存key
     */
    protected function cache($key = self::KEY_PHONE) {

        // 缓存此条验证码相关信息 用于用户后续认证
        cache('smscode_verify_' . $this->$key , [
            // 验证码
            'code'              => $this->authcode,
            // 生成时间
            'generate_time'     => time(),
            // 剩余尝试机会
            'chance'            => config('settings.authcode.smscode_chance'),
            // 当前请求验证码的设备标识
            'device_identifier' => $this->device_id,
            'phone'             => $this->phone
        ], config('settings.authcode.smscode_expire'));

        // 缓存验证码获取定时器 用于获取频率验证
        cache('smscode_interval_' . $this->phone, true, config('settings.authcode.smscode_interval'));
        cache('smscode_interval_' . $this->ip, true, config('settings.authcode.smscode_interval'));
        cache('smscode_interval_' . $this->device_id, true, config('settings.authcode.smscode_interval'));

        // 记录/更新发送计数 用于获取限制验证
        if ($this->smscode_counter_phone) {
            Cache::inc('smscode_counter_' . $this->phone);
        } else {
            cache('smscode_counter_' . $this->phone, 1, 24 * 3600);
        }

        if ($this->smscode_counter_ip) {
            Cache::inc('smscode_counter_' . $this->ip);
        } else {
            cache('smscode_counter_' . $this->ip, 1, 24 * 3600);
        }

        if ($this->smscode_counter_device) {
            Cache::inc('smscode_counter_' . $this->device_id);
        } else {
            cache('smscode_counter_' . $this->device_id, 1, 24 * 3600);
        }
        
    }

    /**
     * 获取短信验证码
     * @author lwtting <smlling@hotmail.com>
     * @param string $key       缓存key类型
     * @return json             验证码的缓存key
     */
    public function send ($key = self::KEY_PHONE) {

        if (!$this->checkPhoneNumber()) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 不是合法的手机号码');
        }
        
        if (!$this->checkSendFrquency()) {
            throw new Error(Status::AUTHCODE_GENERATE_FREQUENTLY);
        }

        if (!$this->checkSendLimit()) {
            throw new Error(Status::AUTHCODE_GENERATE_LIMIT);
        }

        // 发送验证码
        $this->sendSMS();

        // 缓存
        $this->cache($key);

        //
        //                                         需要删除
        //                                         |||||||
        // return $this->success('获取手机验证码成功' . $this->authcode);

    }

    /**
     * 验证手机验证码
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public static function verify ($key, $code, $phone = null, $device_id = null) {

        if (!$$key) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 验证消息不完整');
        }

        // 从缓存中取出验证数据
        $verified_data = cache('smscode_verify_' . $$key);

        if (!$verified_data) {
            throw new Error(Status::NEED_GENERATE_AUTHCODE);
        }

        if ($code !== $verified_data['code']) {
            
            // 减小一次输入机会
            $verified_data['chance'] -= 1;
            $generate_time = $verified_data['generate_time'];
            cache('smscode_verify_' . $$key, $verified_data, $generate_time + config('settings.authcode.smscode_expire') - time());

            // 输入机会用尽
            if (!$verified_data['chance']) {
                // 撤销该条验证码
                cache('smscode_verify_' . $$key, null);
                throw new Error(Status::AUTHCODE_EXPIRE);
            }

            throw new Error(Status::AUTHCODE_INVALID);
        }

        // 验证当前设备是否是发送获取验证码请求的设备
        if ($device_id !== $verified_data['device_identifier']) {
            throw new Error(Status::NEW_DEVICE_DETECTED, '验证设备不符');
        }

        // 验证成功,撤销该条验证码
        $phone = $verified_data['phone'];
        cache('smscode_verify_' . $$key, null);

        return $phone;

    }

}