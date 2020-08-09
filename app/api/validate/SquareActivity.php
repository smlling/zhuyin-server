<?php
namespace app\api\validate;

use think\Validate;
use DfaFilter\SensitiveHelper;
use app\api\model\SquareActivity as SquareActivityM;

class SquareActivity extends Validate
{
    
    protected $rule =   [
        'content'               => 'require|length:2,400|checkBanWords',
        'delete_reason'         => 'require|checkDeleteReason',
        'sort_condition'        => 'require|checkSortCondition',
        'sort_type'             => 'require|checkSortType',
        'keyword'               => 'require'
    ];
    
    protected $field = [
        'content'               => '内容',
        'delete_reason'         => '删除原因',
        'search_condition'      => '检索条件',
        'keyword'               => '搜索关键词',
        'sort_type'             => '排序方式'
    ];

    protected $scene = [
        'post'                  => ['content'],
        'delete'                => ['delete_reason'],
        'sort'                  => ['sort_condition', 'sort_type']
    ];

    protected $message = [
        'title.checkBanWords'   => '标题中含有敏感词',
        'content.checkBanWords' => '内容中含有敏感词'
    ];

    /**
     * 敏感词检查
     * @author lwtting <smlling@hotmail.com>
     * @param    string     $data 
     * @return   boolean           
     */
    protected function checkBanWords ($data) {
        $banwords = config('settings.banwords');
        $handle = SensitiveHelper::init()->setTree($banwords);
        return $handle->islegal($data); //'标题/内容包含敏感词: ' . $handle->getBadWord($data, 1)[0];
    }

    /**
     * 检查删除原因是否受限
     * 用于管理员删帖时检查
     * @author lwtting <smlling@hotmail.com>
     * @param integer $value
     * @return mixed
     */
    protected function checkDeleteReason ($value) {
        if (isset(SquareActivityM::DELETE_REASON[$value]) && 'limit' !== $value) {
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
        if (in_array($value, SquareActivityM::SORT_CONDITION)) {
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