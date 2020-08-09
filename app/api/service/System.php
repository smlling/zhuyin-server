<?php

namespace app\api\service;

use app\api\model\SystemCounter;

class System {

    /**
     * 获取用户相关计数器
     * @author lwtting <smlling@hotmail.com>
     * @return array
     */
    public function userCounter () {
        return [
            'available'   => (SystemCounter::get(SystemCounter::USER_AVAILABLE_COUNT, true))->count,
            'freezed'     => (SystemCounter::get(SystemCounter::USER_FREEZED_COUNT, true))->count,
            'abandon'     => (SystemCounter::get(SystemCounter::USER_ABANDON_COUNT, true))->count
        ];
    }

    /**
     * 获取广场动态相关计数器
     * @author lwtting <smlling@hotmail.com>
     * @return array
     */
    public function squareActivityCounter () {
        return [
            'available'   => (SystemCounter::get(SystemCounter::SQUARE_ACTIVITY_AVAILABLE_COUNT, true))->count,
            'private'     => (SystemCounter::get(SystemCounter::SQUARE_ACTIVITY_PRIVATE_COUNT, true))->count,
            'delete'      => (SystemCounter::get(SystemCounter::SQUARE_ACTIVITY_DELETE_COUNT, true))->count
        ];
    }
    
    /**
     * 获取当前系统计数器
     * @author lwtting <smlling@hotmail.com>
     * @return array
     */
    public function systemCounter(){

        $data = [
            'user'              => $this->userCounter(),
            'square_activity'   => $this->squareActivityCounter()
        ];

        return $data;
    }

    /**
     * 获取系统用户总数
     * @author lwtting <smlling@hotmail.com>
     * @return integer
     */
    public function userCount () {
        $userCounter = $this->userCounter();
        $userCount = 0;
        foreach ($userCounter as $count) {
            $userCount += $count;
        }
        return $userCount;
    }

    /**
     * 获取广场动态总数
     * @author lwtting <smlling@hotmail.com>
     * @return integer
     */
    public function squareActivityCount () {
        $squareActivityCounter = $this->squareActivityCounter();
        $squareActivityCount = 0;
        foreach ($squareActivityCounter as $count) {
            $squareActivityCount += $count;
        }
        return $squareActivityCount;
    }
}