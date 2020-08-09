<?php
namespace app\api\validate;

use think\Validate;
use DfaFilter\SensitiveHelper;
use app\api\model\User as UserM;

class User extends Validate {
    // 状态码
    const USERNAME_EXIST                    = -1;

    protected $rule =   [
        'username'                          => 'require|length:5,15|checkUsername|unique:user',
        'nickname'                          => 'require|length:1,20|chsDash|checkBanWords',//汉字、字母、数字和下划线_及破折号-
        'sex'                               => 'checkSex',
        'age'                               => 'between:0,125',
        'location'                          => 'checkLocation',
        'description'                       => 'length:1,50|checkBanWords',
        'freeze_reason'                     => 'require|checkFreezeReason',
        'freeze_time'                       => 'require|checkFreezeTime',
        'sort_condition'                    => 'require|checkSortCondition',
        'sort_type'                         => 'require|checkSortType'
        
    ];
    
    protected $field = [
        'username'                          => '用户名',
        'nickname'                          => '昵称',
        'sex'                               => '性别',
        'age'                               => '年龄',
        'location'                          => '地区',
        'description'                       => '个人描述',
        'freeze_reason'                     => '冻结原因',
        'freeze_time'                       => '冻结时长',
    ];

    protected $message  =   [
        'username.unique'                   => self::USERNAME_EXIST,
        'nickname.checkBanWords'            => '昵称中含有敏感词',
        'description.checkBanWords'         => '个人描述中含有敏感词',
        'freeze_reason.checkFreezeReason'   => '不允许的冻结原因',
        'freeze_time.checkFreezeTime'       => '不允许的冻结时长',
        'location'                          => '无法识别的地区',
        'sex'                               => '无法识别的性别'
    ];
    
    protected $scene = [

        'setInfo'                           => ['nickname', 'sex', 'age', 'location', 'description'],
        'checkUsername'                     => ['username'],
        'freeze'                            => ['freeze_reason', 'freeze_time'],
        'sort'                              => ['sort_condition', 'sort_type']

    ];
    
    
    /**
     * 校验用户名是否是以字母开头且后面以字母、数字、下划线组成的
     * @author lwtting <smlling@hotmail.com>
     * @param    string     $username 用户名
     * @return   boolean
     */
    protected function checkUsername ($username) {
        return preg_match('/^[a-zA-Z][a-zA-Z0-9_]+$/',$username) ? true : '用户名格式非法';
    }

    /**
     * 敏感词检查
     * @author lwtting <smlling@hotmail.com>
     * @param    string     $data 
     * @return   boolean           
     */
    protected function checkBanWords ($data) {
        $banwords = config('settings.banwords');
        // var_dump(config(''));
        // get one helper
        $handle = SensitiveHelper::init()->setTree($banwords);
        return $handle->islegal($data) ? true : false; // '昵称/简介包含敏感词:' . $handle->getBadWord($data, 1)[0];
    }

    /**
     * 检查冻结原因是否是可操作的冻结原因
     * 此用于管理员冻结用户时检查
     * @author lwtting <smlling@hotmail.com>
     * @param integer $value
     * @return boolean
     */
    protected function checkFreezeReason ($value) {
        if (isset(UserM::FREEZE_REASON[$value]) && 'limit' !== $value) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查冻结时长是否是可操作的冻结时长
     * 此用于管理员冻结用户时检查
     * @author lwtting <smlling@hotmail.com>
     * @param integer $value
     * @return boolean
     */
    protected function checkFreezeTime ($value) {
        if (isset(UserM::FREEZE_TIME[$value])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查注册类型是否允许
     * @author lwtting <smlling@hotmail.com>
     * @param string $value
     * @return boolean
     */
    protected function checkRigisterType ($value) {
        if ($value === 'email' || $value === 'phone') {
            return true;
        } else {
            return '不允许的注册方式';
        }
    }

    /**
     * 检查地区是否合法
     * 输入字符串格式--省市之间用逗号隔开 如"北京市,东城区"
     * @author lwtting <smlling@hotmail.com>
     * @param string $value
     * @return boolean
     */
    protected function checkLocation ($value) {
        $locationList = config('settings.location');
        $locationArr = explode(',', $value);

        $provence = $locationArr[0] ?: null;
        $city = $locationArr[1] ?: null;
        
        if (!is_null($provence) && isset($locationList[$provence])) {
            if (!is_null($city)) {
                if (in_array($city, $locationList[$provence])) {
                    return true;
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 检查性别
     * @author lwtting <smlling@hotmail.com>
     * @param string $value
     * @return boolean
     */
    protected function checkSex ($value) {
        return ('男' === $value || '女' === $value || '保密' === $value) ? true : false;
    }

    /**
     * 检查排序依据是否支持
     * @author lwtting <smlling@hotmail.com>
     * @param integer $value
     * @return mixed
     */
    protected function checkSortCondition ($value) {
        if (in_array($value, UserM::SORT_CONDITION)) {
            return true;
        } else {
            return '错误的排序条件';
        }
    }

    /**
     * 检查排序方式是否支持
     * @author lwtting <smlling@hotmail.com>
     * @param integer $value
     * @return mixed
     */
    protected function checkSortType ($value) {
        if ('desc' === strtolower($value) || 'asc' === strtolower($value)) {
            return true;
        } else {
            return '错误的排序方式';
        }
    }
}