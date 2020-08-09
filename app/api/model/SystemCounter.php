<?php

namespace app\api\model;

use think\Model;

class SystemCounter extends Model {


    const USER_AVAILABLE_COUNT              = 11;
    const USER_ABANDON_COUNT                = 12;
    const USER_FREEZED_COUNT                = 13;

    const SQUARE_ACTIVITY_AVAILABLE_COUNT   = 21;
    const SQUARE_ACTIVITY_PRIVATE_COUNT     = 22;
    const SQUARE_ACTIVITY_DELETE_COUNT      = 23;

    const SYSTEM_COUNTER = [
        self::USER_AVAILABLE_COUNT                  => '正常状态用户数量',
        self::USER_ABANDON_COUNT                    => '注销状态用户数量',
        self::USER_FREEZED_COUNT                    => '冻结状态用户数量',
        self::SQUARE_ACTIVITY_AVAILABLE_COUNT       => '正常可见状态广场动态数量',
        self::SQUARE_ACTIVITY_PRIVATE_COUNT         => '仅发布者可见广场动态数量',
        self::SQUARE_ACTIVITY_DELETE_COUNT          => '已被删除状态广场动态数量'
    ];


    /**
     * 隐藏动态
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public static function hideActivity () {

        $available_counter = self::get(self::SQUARE_ACTIVITY_AVAILABLE_COUNT, true);
        $private_counter = self::get(self::SQUARE_ACTIVITY_PRIVATE_COUNT, true);

        $available_counter->count--;
        $private_counter->count++;

        $available_counter->isUpdate(true)->save();
        $private_counter->isUpdate(true)->save();
    }

    /**
     * 公开动态
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public static function unHideActivity () {

        $available_counter = self::get(self::SQUARE_ACTIVITY_AVAILABLE_COUNT, true);
        $private_counter = self::get(self::SQUARE_ACTIVITY_PRIVATE_COUNT, true);

        $available_counter->count++;
        $private_counter->count--;

        $available_counter->isUpdate(true)->save();
        $private_counter->isUpdate(true)->save();
    }

    /**
     * 发布动态
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public static function postActivity ($private = false) {

        if (!$private) {
            $available_counter = self::get(self::SQUARE_ACTIVITY_AVAILABLE_COUNT, true);
            $available_counter->count++;
            $available_counter->isUpdate(true)->save();
        } else {
            $private_counter = self::get(self::SQUARE_ACTIVITY_PRIVATE_COUNT, true);
            $private_counter->count++;
            $private_counter->isUpdate(true)->save();
        }
    }

    /**
     * 删除动态
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public static function deleteActivity ($private = false) {

        if (!$private) {
            $available_counter = self::get(self::SQUARE_ACTIVITY_AVAILABLE_COUNT, true);
            $available_counter->count--;
            $available_counter->isUpdate(true)->save();
        } else {
            $private_counter = self::get(self::SQUARE_ACTIVITY_PRIVATE_COUNT, true);
            $private_counter->count--;
            $private_counter->isUpdate(true)->save();
        }
        $delete_counter = self::get(self::SQUARE_ACTIVITY_DELETE_COUNT);
        $delete_counter->count++;
        $delete_counter->isUpdate(true)->save();
    }

    /**
     * 添加用户
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public static function addUser () {

        $available_counter = self::get(self::USER_AVAILABLE_COUNT, true);

        $available_counter->count++;

        $available_counter->isUpdate(true)->save();
    }

    /**
     * 冻结用户
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public static function freezeUser () {

        $available_counter = self::get(self::USER_AVAILABLE_COUNT, true);
        $freezed_counter = self::get(self::USER_FREEZED_COUNT, true);

        $available_counter->count--;
        $freezed_counter->count++;

        $available_counter->isUpdate(true)->save();
        $freezed_counter->isUpdate(true)->save();
    }

    /**
     * 解冻用户
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public static function unfreezeUser () {

        $available_counter = self::get(self::USER_AVAILABLE_COUNT, true);
        $freezed_counter = self::get(self::USER_FREEZED_COUNT, true);

        $available_counter->count++;
        $freezed_counter->count--;

        $available_counter->isUpdate(true)->save();
        $freezed_counter->isUpdate(true)->save();

    }

    /**
     * 注销用户
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public static function abandonUser () {

        $available_counter = self::get(self::USER_AVAILABLE_COUNT, true);
        $abandon_counter = self::get(self::USER_ABANDON_COUNT, true);

        $available_counter->count--;
        $abandon_counter->count--;

        $available_counter->isUpdate(true)->save();
        $abandon_counter->usUpdate(true)->save();
    }
}