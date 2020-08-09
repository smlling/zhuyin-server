<?php
namespace app\api\validate;

use think\Validate;
use DfaFilter\SensitiveHelper;
use app\api\model\SquareComment as SquareCommentM;

class SquareComment extends Validate {
    protected $rule =   [
        'content'               => 'require|length:2,300|checkBanWords',
        'delete_reason'         => 'require|checkDeleteReason',
        'sort_condition'        => 'require|checkSortCondition',
        'sort_type'             => 'require|checkSortType'
    ];
    
    protected $field = [
        'content'               => '评论内容',
    ];

    protected $message = [
        'content.checkBanWords' => '评论内容中含有敏感词'
    ];

    protected $scene = [
        'post'                  => ['content'],
        'delete'                => ['delete_reason'],
        'sort'                  => ['sort_condition', 'sort_type']
    ];
    
    /**
     * 敏感词检查
     * @author lwtting <smlling@hotmail.com>
     * @param    string     $data 
     * @return   boolean
     */
    protected function checkBanWords($data){
        $banwords = config('settings.banwords');
        $handle = SensitiveHelper::init()->setTree($banwords);
        return $handle->islegal($data) ? true : false;
    }

    /**
     * 检查删除原因是否受限
     * 用于管理员删帖时检查
     * @author lwtting <smlling@hotmail.com>
     * @param integer $value
     * @return mixed
     */
    protected function checkDeleteReason ($value) {
        if (isset(SquareCommentM::DELETE_REASON[$value]) && 'limit' !== $value) {
            return true;
        } else {
            return '不允许的删除原因';
        }
    }

    /**
     * 检查排序依据是否支持
     * @author lwtting <smlling@hotmail.com>
     * @param integer $value
     * @return mixed
     */
    protected function checkSortCondition ($value) {
        if (in_array($value, SquareCommentM::SORT_CONDITION)) {
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