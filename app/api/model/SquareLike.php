<?php

namespace app\api\model;

use think\Model;

class SquareLike extends Model{

    // 点赞类型
    const LIKE_ACTIVITY     = 1;            //常量，标识点赞/取消点赞动态
    const LIKE_COMMENT      = 2;            //常量，标识点赞/取消点赞评论


    /**
     * 添加点赞记录
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid                  点赞者uid
     * @param integer $like_to              点赞目标id
     * @param integer $type                 点赞类型
     * @return SquareLike
     */
    public function add ($uid, $like_to, $type) {
        if (isset($this->id)) {
            unset($this->id);
        }
        $this->uid = $uid;
        $this->like_to = $like_to;
        $this->like_type = $type;
        $this->like_time = time();
        $this->isUpdate(false)->save();
        return $this;
    }

    /**
     * 更改点赞状态
     * @author lwtting <smlling@hotmail.com>
     * @return SquareLike
     */
    public function changeLikeStatus () {
        if ($this->like_time) {
            $this->like_time = 0;

        } else {
            $this->like_time = time();
        }
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 获取当前点赞状态
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function liked () {
        return $this->like_time ? true : false;
    }

    /**
     * 检查指定用户对指定目标是否点赞
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid                  点赞者uid
     * @param integer $like_to              点赞目标id
     * @param integer $type                 点赞类型
     * @return boolean
     */
    public static function isLiked ($uid, $like_to, $type) {
        $like = self::where('uid = ? AND like_to = ? AND like_type = ?' , [$uid, $like_to, $type])
                    // ->cache('square_like_' . $uid . '_' . $like_to . '_' . $type)
                    ->find();
        if (is_null($like) || !$like->like_time) {
            return false;
        } else {
            return true;
        }
    }

}