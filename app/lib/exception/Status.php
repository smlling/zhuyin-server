<?php

namespace app\lib\exception;

class Status {

    // 内部错误
    const API_MAINTANCE                             = 10;
    const API_INNER_ERROR                           = 11;

    // 操作成功
    const SUCCESS                                   = 1000;

    // 验证相关
    const AUTHCODE_INVALID                          = 1001;
    const NEED_LOGIN                                = 1002;
    const LOGIN_EXPIRE                              = 1003;
    const NEED_PRIVILEGE                            = 1004;
    const AUTHCODE_GENERATE_FREQUENTLY              = 1005;
    const NEED_GENERATE_AUTHCODE                    = 1006;
    const AUTHCODE_GENERATE_LIMIT                   = 1007;
    const AUTHCODE_EXPIRE                           = 1008;
    const FORM_PARAM_ERROR                          = 1009;
    const NEW_DEVICE_DETECTED                       = 1010;
    const USER_PWD_INVAILD                          = 1011;
    const USERNAME_EXIST                            = 1012;
    const POST_FREQUENTLY                           = 1013;

    // 用户相关
    const PHONE_EXIST                               = 2001;
    const USER_FREEZED                              = 2002;
    const USER_NOT_EXIST                            = 2003;
    const USER_ABANDON                              = 2004;
    const PHONE_NOT_BAND                            = 2005;
    const USER_ALREADY_SET_USERNAME                 = 2006;
    const USER_NOT_FREEZED                          = 2007;
    const USER_NOT_BAND_PHONE                       = 2008;


    //文件相关
    const NO_FILE_UPLOADED                          = 3001;
    const INVALID_FILE                              = 3002;
    const FILE_NOT_EXIST                            = 3003;

    // 广场相关
    const ACTIVITY_NOT_EXIST                        = 4001;
    const ACTIVITY_PRIVATE                          = 4002;
    const COMMENT_NOT_EXIST                         = 4003;


    const MESSAGE = [
        self::API_MAINTANCE                         => '接口维护',
        self::API_INNER_ERROR                       => '内部错误',

        self::SUCCESS                               => '操作成功',

        self::AUTHCODE_INVALID                      => '验证码错误',
        self::NEED_LOGIN                            => '请先登录',
        self::LOGIN_EXPIRE                          => '登录状态已失效',
        self::NEED_PRIVILEGE                        => '无权操作',
        self::AUTHCODE_GENERATE_FREQUENTLY          => '验证码获取频繁',
        self::NEED_GENERATE_AUTHCODE                => '请先获取验证码',
        self::AUTHCODE_GENERATE_LIMIT               => '今日验证码获取次数已达上限',
        self::AUTHCODE_EXPIRE                       => '验证码已失效请重新获取',
        self::FORM_PARAM_ERROR                      => '参数错误',
        self::NEW_DEVICE_DETECTED                   => '检测到设备更换',
        self::USER_PWD_INVAILD                      => '口令有误',
        self::USERNAME_EXIST                        => '用户名已被占用',
        self::POST_FREQUENTLY                       => '发布过于频繁,请稍后再试',

        self::PHONE_EXIST                           => '该手机号已被注册',
        self::USER_FREEZED                          => '用户被冻结',
        self::USER_NOT_EXIST                        => '用户不存在',
        self::USER_ABANDON                          => '用户已注销',
        self::PHONE_NOT_BAND                        => '用户未绑定手机,无法进行此操作',
        self::USER_ALREADY_SET_USERNAME             => '用户已设置过用户名,无法再次设定',
        self::USER_NOT_FREEZED                      => '用户未被冻结',
        self::USER_NOT_BAND_PHONE                   => '用户未绑定手机号',

        self::NO_FILE_UPLOADED                      => '没有文件上传',
        self::INVALID_FILE                          => '无效的文件',
        self::FILE_NOT_EXIST                        => '文件不存在',

        self::ACTIVITY_NOT_EXIST                    => '目标动态不存在或已被删除',
        self::ACTIVITY_PRIVATE                      => '目标动态发布者设置了访问权限',
        self::COMMENT_NOT_EXIST                     => '目标评论不存在或已被删除',
        
    ];
    
}