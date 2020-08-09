<?php

namespace app\api\model;

use think\Model;
use app\api\model\SquareActivityComment as Comment;

class SquareActivity extends Model{

    const SORT_CONDITION = [
        'limit' => [
            'comment_count_all'
        ],
        'post_time',
        'last_comment_time',
        'comment_count',
        'like_count',
        'share_count'
    ];

    const DELETED_INVITATION_INFO = [
        "id"                            => 0,
        "title"                         => "主贴不存在或已被删除",
        "content"                       => "主贴不存在或已被删除",
        "attached_file"                 => [],
        "publisher"                     => null,
        "post_time"                     => null,
        "last_comment_time"             => null,
        "like_count"                    => 0,
        "comment_count"                 => 0,
        "share_count"                   => 0,
        "hottest_comment"               => []
    ];

    // 删除原因
    // const DEL_NOT_DELETE                = 0;
    const DEL_USER_INITIACTIVE          = 1;
    const DEL_ILLEGAL_CONTENT           = 2;
    const DEL_INSULT_CONTENT            = 3;
    const DEL_REPORT                    = 4;

    const DELETE_REASON = [
        'limit'                         => [
            // self::DEL_NOT_DELETE        => '未被删除',
            self::DEL_USER_INITIACTIVE  => '用户自行删除',
        ],
        self::DEL_ILLEGAL_CONTENT       => '涉及黄赌毒等违法信息',
        self::DEL_INSULT_CONTENT        => '涉及辱骂他人的言论',
        self::DEL_REPORT                => '被多人举报'
    ];

    // 检索条件
    // const SEARCH_PUBLISHER              = 1;
    // const SEARCH_CONTENT                = 2;

    // const SEARCH_CONDITION = [
    //     self::SEARCH_PUBLISHER          => 'publisher',
    //     self::SEARCH_CONTENT            => 'content'
    // ];

    const SEARCH_CONDITION = ['publisher', 'content'];

    /**
     * 添加主贴记录
     * @author lwtting <smlling@hotmail.com>
     * @param string     $uid                发布者uid
     * @param string     $content            内容
     * @param boolean    $private            是否隐藏
     * @param array      $attach_files       附件
     * @param string     $ip                 ip地址
     * @return SquareActivity
     */
    public function add ($uid, $content, $private, $attach_files, $ip) {
        if (isset($this->id)) {
            unset($this->id);
        }
        $this->uid = $uid;
        $this->content = $content;
        $this->private = $private;
        $this->attach_files = json_encode($attach_files);
        $this->ip = $ip;
        $this->post_time = time();
        $this->isUpdate(false)->save();
        return self::get($this->id, true);
    }

    /**
     * 是否被删除
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isDeleted () {
        return $this->delete_time ? true : false;
    }

    /**
     * 是否隐藏(仅自己可见)
     * @author lwtting <smlling@hotmail.com>
     * @return boolean
     */
    public function isPrivate () {
        return $this->private ? true : false;
    }

    /**
     * 获取可读性的删除时间
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function deleteTime () {
        if (!$this->isDeleted()) {
            return '未被删除';
        }
        return date('Y-m-d h:i:s', $this->delete_time);
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
     * 获取最新评论时间
     * @author lwtting <smlling@hotmail.com>
     * @return string
     */
    public function lastCommentTime () {
        return $this->last_comment_time ? date('Y-m-d h:i:s', $this->last_comment_time) : '暂无评论';
    }

    /**
     * 过滤器
     * @author lwtting <smlling@hotmail.com>
     * @param boolean         $admin    管理员模式
     * @return array
     */
    public function filter($admin = false){ 
        $info = $this->getData();

        $info['post_time'] = date('Y-m-d h:i:s', $this->post_time);
        $info['last_comment_time'] = $this->lastCommentTime();
        $info['private'] = $this->isPrivate();

        if ($admin) {
            $info['is_deleted'] = $this->isDeleted();
            if ($this->isDeleted()) {
                $info['delete_time'] = date('Y-m-d h:i:s', $this->delete_time);
                $info['delete_reason'] = $this->deleteReason();
            }
        } else {
            unset(
                $info['delete_time'],
                $info['delete_reason'],
                $info['ip'],
                $info['comment_count_all']
            );
        }
        return $info;
    }

    /**
     * 自增评论数量
     * @author lwtting <smlling@hotmail.com>
     * @return SquareActivity
     */
    public function incCommentCount () {

        $this->comment_count++;
        $this->comment_count_all++;
        $this->isUpdate(true)->save();

        return $this;
    }

    /**
     * 自减评论数量
     * @author lwtting <smlling@hotmail.com>
     * @return SquareActivity
     */
    public function decCommentCount () {

        $this->comment_count--;
        $this->isUpdate(true)->save();

        return $this;
    }

    /**
     * 自增点赞数量
     * @author lwtting <smlling@hotmail.com>
     * @return SquareActivity
     */
    public function incLikeCount () {

        $this->like_count++;
        $this->isUpdate(true)->save();

        return $this;
    }

    /**
     * 自减点赞数量
     * @author lwtting <smlling@hotmail.com>
     * @return SquareActivity
     */
    public function decLikeCount () {

        $this->like_count--;
        $this->isUpdate(true)->save();

        return $this;
    }

    /**
     * 删除动态
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public function del ($delete_reason = self::DEL_USER_INITIACTIVE) {
        $this->delete_time = time();
        $this->delete_reason = $delete_reason;
        $this->comment_count = 0;
        $this->isUpdate(true)->save();
    }

    /**
     * 更新最新评论发表时间
     * @author lwtting <smlling@hotmail.com>
     * @return SquareActivity
     */
    public function updateCommentTime () {
        $this->last_comment_time = time();
        $this->isUpdate(true)->save();
        return $this;
    }

    /**
     * 获取附件物理路径
     * @author lwtting <smlling@hotmail.com>
     * @return array
     */
    public function attachFileList () {
        return json_decode($this->attach_files, true);
    }

}