<?php

namespace app\api\model;
use think\Model;

class SquareComment extends Model{

    const SORT_CONDITION = [
        'limit' => [
            'reply_count_all'
        ],
        'post_time',
        'reply_count',
        'like_count'
    ];

    const DELETED_COMMENT_INFO = [
        "id" => 0,
        "invitation_id" => 0,
        "parent_id" => 0,
        "content" => "评论不存在或已被删除",
        "publisher" => null,
        "post_time" => null,
        "like_count" => 0,
        "reply_count" => 0
    ];

    // 删除原因
    const DEL_USER_INITIACTIVE              = 1;
    const DEL_ILLEGAL_CONTENT               = 2;
    const DEL_INSULT_CONTENT                = 3;
    const DEL_REPORT                        = 4;
    const DEL_RELATE                        = 5;

    const DELETE_REASON = [
        'limit' => [
            self::DEL_USER_INITIACTIVE      => '用户自行删除',
            self::DEL_RELATE                => '关联删除'
        ],
        self::DEL_ILLEGAL_CONTENT           => '涉及黄赌毒等违法信息',
        self::DEL_INSULT_CONTENT            => '涉及辱骂他人的言论',
        self::DEL_REPORT                    => '被多人举报'
    ];

    /**
     * 添加评论
     * @author lwtting <smlling@hotmail.com>
     * @param integer $root_id              根id
     * @param integer $uid                  发布者uid
     * @param integer $reply_to             评论id
     * @param string $content               内容
     * @param string $ip                    发布者IP地址
     * @return SquareComment
     */
    public function add ($uid, $root_id, $reply_to, $content, $ip) {
        if (isset($this->id)) {
            unset($this->id);
        }
        $this->uid = $uid;
        $this->root_id = $root_id;
        $this->reply_to = $reply_to;
        $this->content = $content;
        $this->ip = $ip;
        $this->post_time = time();
        $this->isUpdate(false)->save();
        return self::get($this->id, true);
    }
    
    /**
     * 是否已删除
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isDeleted () {
        return $this->delete_time ? true : false;
    }

    /**
     * 自增回复数量
     * @author lwtting <smlling@hotmail.com>
     * @return SquareComment
     */
    public function incReplyCount () {
        $this->reply_count++;
        $this->reply_count_all++;
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 自减回复数量
     * @author lwtting <smlling@hotmail.com>
     * @return SquareComment
     */
    public function decReplyCount () {
        $this->reply_count--;
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 自增点赞数量
     * @author lwtting <smlling@hotmail.com>
     * @return SquareComment
     */
    public function incLikeCount () {

        $this->like_count++;
        $this->isUpdate(true)->save();

        return $this;
    }

    /**
     * 自减点赞数量
     * @author lwtting <smlling@hotmail.com>
     * @return SquareComment
     */
    public function decLikeCount () {

        $this->like_count--;
        $this->isUpdate(true)->save();

        return $this;
    }

    /**
     * 获取可读性的删除原因
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    protected function deleteReason() {
        if (!$this->isDeleted()) {
            return '未被删除';
        }
        $delete_reason = $this->delete_reason;
        if (isset(self::DELETE_REASON[$delete_reason])) {
            return self::DELETE_REASON[$delete_reason];
        }
        if (isset(self::DELETE_REASON['limit'][$delete_reason])) {
            return self::DELETE_REASON['limit'][$delete_reason];
        }
        return '未知原因';
    }

    /**
     * 是否是回复
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isReply () {
        return ($this->reply_to && ($this->reply_to !== $this->root_id)) ? true : false;
    }

    /**
     * 是否是二级评论(顶级回复)
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isSubComment () {
        return ($this->reply_to === $this->root_id) ? true : false;
    }

    /**
     * 是否是一级评论
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isComment () {
        return ($this->reply_to === 0) ? true : false;
    }

    /**
     * 是否有回复
     * @author lwtting <smlling@hotmail.com>
     * @param boolean $admin
     * @return boolean
     */
    public function hasReply ($admin = false) {
        if ($admin) {
            return $this->reply_count_all ? true : false;
        } else {
            return $this->reply_count ? true : false;
        }
    }

    /**
     * 回复数量
     * @author lwtting <smlling@hotmail.com>
     * @param boolean $admin
     * @return boolean
     */
    public function replyCount ($admin = false) {
        if ($admin) {
            return $this->reply_count_all;
        } else {
            return $this->reply_count;
        }
    }

    /**
     * 信息过滤
     * @param boolean $admin
     * @return void
     */
    public function filter ($admin = false) {
        $info = $this->getData();

        $info['post_time'] = date("Y-m-d H:i:s",$this->post_time);
        $info['is_reply'] = $this->isReply();
        $info['has_reply'] = $this->hasReply($admin);
        $info['reply_count'] = $this->replyCount($admin);

        if ($admin) {
            $info['is_deleted'] = $this->isDeleted();
            if ($this->isDeleted()) {
                $info['delete_time'] = date("Y-m-d H:i:s",$this->delete_time);
                $info['delete_reason'] = $this->deleteReason();
            }  
        } else {
            unset(
                $info['delete_time'],
                $info['delete_reason'],
                $info['ip'],
                $info['reply_count_all']
            );
        }
        
        return $info;
    }

    /**
     * 删除评论
     * @author lwtting <smlling@hotmail.com>
     * @param integer $delete_reason
     * @return void
     */
    public function del ($delete_reason = self::DEL_USER_INITIACTIVE) {
        // 若是删除一级评论则将其回复数置零(因为其回复会被遍历删除)
        if ($this->isComment()) {
            $this->reply_count = 0;
        }
        $this->delete_time = time();
        $this->delete_reason = $delete_reason;
        $this->isUpdate(true)->save();
    }

}