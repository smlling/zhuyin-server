<?php

namespace app\api\service;

use think\Db;
use think\Controller;

use app\common\Utils;
use app\lib\exception\Status;
use app\lib\exception\ApiException as Error;

use app\api\model\AdminLog;
use app\api\model\AttachFile;
use app\api\model\SquareLike;
use app\api\model\SystemCounter;
use app\api\model\User as UserM;
use app\api\model\Admin as AdminM;
use app\api\model\SquareComment as SquareCommentM;
use app\api\model\SquareActivity as SquareActivityM;
use app\api\validate\SquareComment as SquareCommentVal;
use app\api\validate\SquareActivity as SquareActivityVal;

class Square extends Controller {

    /**
     * 当前登录的用户模型
     * @var AdminM|UserM
     */
    protected $user;

    /**
     * 当前登录用户是否为管理员
     * @var boolean
     */
    protected $isAdmin = false;

    /**
     * 构造函数,构造当前登录用户模型
     * @author lwtting <smlling@hotmail.com>
     * @param AdminM|UserM              $user 用户模型|管理员模型
     */
    public function __construct ($user = null) {
        
        if ($user instanceof AdminM || $user instanceof UserM) {
            if ($user instanceof AdminM) {
                $this->isAdmin = true;
            }
            $this->user = $user;
        }

    }

    /**
     * 检查动态状态(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param SquareActivityM $activity      动态模型
     * @return void
     */
    protected function checkActivityStatus ($activity) {

        // 动态被删除
        if (is_null($activity) || $activity->isDeleted()) {
            throw new Error(Status::ACTIVITY_NOT_EXIST);
        }

        // 动态非公开且当前用户非其发布者
        if ($activity->isPrivate() && $this->user->id !== $activity->uid) {
            throw new Error(Status::ACTIVITY_PRIVATE);
        }
    }

    /**
     * 检查评论状态(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param SquareCommentM $comment        评论模型
     * @return void
     */
    protected function checkCommentStatus ($comment) {

        // 评论被删除
        if (is_null($comment) || $comment->isDeleted()) {
            throw new Error(Status::COMMENT_NOT_EXIST);
        }
    }

    /**
     * 获取附件访问路径
     * @author lwtting <smlling@hotmail.com>
     * @param SquareActivityM $activity     动态模型
     * @return array
     */
    protected function getAttachFileUrl ($activity) {
        $attach_files = $activity->attachFileList();
        $data = [];
        $data['image_count'] = 0;
        $data['video_count'] = 0;
        $data['images_list'] = [];
        $data['videos_list'] = [];
        foreach ($attach_files as $filename => $file_id) {
            $file = AttachFile::get($file_id, true);
            if ('image' === $file->file_type) {
                $data['image_count']++;
                $data['images_list'][$filename] = [
                    'large' => config('settings.host') . 'square/activity/attach?activity=' . $activity->id . '&attach=' . $filename . '&type=large',
                    'small' => config('settings.host') . 'square/activity/attach?activity=' . $activity->id . '&attach=' . $filename . '&type=small'
                ];
            } else if ('video' === $file->file_type) {
                $data['video_count']++;
                $data['videos_list'][$filename] = [
                    'large' => config('settings.host') . 'square/activity/attach?activity=' . $activity->id . '&attach=' . $filename . '&type=large',
                    'small' => config('settings.host') . 'square/activity/attach?activity=' . $activity->id . '&attach=' . $filename . '&type=small'
                ];
            }
        }
        
        return $data;
    }

    /**
     * 获取附件物理路径
     * @author lwtting <smlling@hotmail.com>
     * @param integer $activity_id           动态id
     * @param integer $filename              文件名
     * @return array
     */
    public function getAttachFilePath ($activity_id, $filename) {

        if (!(is_numeric($activity_id) && $activity_id > 0 && $filename)) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的动态id或附件');
        }

        // 获取动态模型
        $activity = SquareActivityM::get($activity_id, true);

        if (is_null($activity) || (!$this->isAdmin && ($activity->isDeleted() || $activity->isPrivate()))) {
            return [];
        }

        // 获取动态的附件列表
        $attach_files = $activity->attachFileList();

        var_dump($attach_files);
        
        if (!isset($attach_files[$filename])) {
            return [];
        }

        // 获取附件模型
        $file = AttachFile::get($attach_files[$filename], true);

        return [
            // 源文件路径
            'large' => $file->path,
            // 缩略图路径
            'small' => $file->path . '.thumb'
        ];
    }

    /**
     * 获取动态详情
     * @author lwtting <smlling@hotmail.com>
     * @param integer|SquareActivityM $activity     动态id|动态模型
     * @return void
     */
    public function getActivityInfo ($activity) {

        if (is_numeric($activity) && $activity > 0) {
            // 获取动态模型
            $activity = SquareActivityM::get($activity, true);

            // 检查动态状态
            if (is_null($activity)) {
                throw new Error(Status::ACTIVITY_NOT_EXIST);
            }
        } else if (!($activity instanceof SquareActivityM)) {
            throw new Error(Status::API_INNER_ERROR);
        }

        if (!$this->isAdmin) {
            // 动态被删除
            if ($activity->isDeleted()) {
                throw new Error(Status::ACTIVITY_NOT_EXIST);
            }

            // 动态非公开且当前用户非其发布者
            if ($activity->isPrivate() && $this->user->id !== $activity->uid) {
                throw new Error(Status::ACTIVITY_PRIVATE);
            }
        }

        // 过滤
        $info = $activity->filter($this->isAdmin);
        // 注入发表者信息
        $info['user'] = (new User($this->user))->getUserInfo($activity->uid);
        // 注入附件信息
        $info['attach_files'] = $this->getAttachFileUrl($activity);
        // 注入点赞信息
        if (!$this->isAdmin) {
            $info['is_liked'] = SquareLike::isLiked($this->user->id, $activity->id, SquareLike::LIKE_ACTIVITY);
        }

        return $info;

    }

    /**
     * 获取评论/回复详情
     * @author lwtting <smlling@hotmail.com>
     * @param integer|SquareCommentM $comment     评论id|评论模型
     * @return void
     */
    public function getCommentInfo ($comment) {

        if (is_numeric($comment) && $comment > 0) {
            // 获取评论模型
            $comment = SquareCommentM::get($comment, true);

            // 检查评论状态
            if (is_null($comment)) {
                throw new Error(Status::COMMENT_NOT_EXIST);
            }
        } else if (!($comment instanceof SquareCommentM)) {
            throw new Error(Status::API_INNER_ERROR);
        }

        // 过滤
        $info = $comment->filter($this->isAdmin);
        // 注入发表者信息
        $info['user'] = (new User($this->user))->getUserInfo($comment->uid);
        // 注入点赞信息
        if (!$this->isAdmin) {
            $info['is_liked'] = SquareLike::isLiked($this->user->id, $comment->id, SquareLike::LIKE_COMMENT);
        }
        // 注入上文信息
        if ($comment->isReply()) {
            // 是2+级回复则注入上级回复对象的信息
            $info['context'] = $this->getCommentInfo($comment->reply_to);
        } else if ($comment->isSubComment()) {
            // 是顶级回复则不需要注入上文
            $info['context'] = null;
        } else {
            // 不是回复(是一级评论)则注入主动态信息
            $info['context'] = $this->getActivityInfo($comment->root_id);
        }

        return $info;

    }

    /**
     * 获取动态列表
     * @author lwtting <smlling@hotmail.com>
     * @param array $options            参数
     * @return array
     */
    public function getActivityList ($options) {

        $params = [
            'status'            => 'all',
            'page'              => 1,
            'limit'             => 20,
            'activity_id'       => 0,
            'uid'               => 0,
            'sort_condition'    => 'post_time',
            'sort_type'         => 'desc'
        ];

        $params = array_merge($params, $options);
        extract($params);

        if ($page <= 0 || $limit <= 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 错误的页码或页限');
        }

        if ($activity_id < 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的动态id');
        }

        if ($uid < 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的用户id');
        }

        if (!$this->isAdmin) {
            $status = 'available';
        }

        if ($activity_id > 0) {
            return [
                'limit' => $limit,
                'page'  => 1,
                'total_page'    => 1,
                'list'  => [
                    $this->getActivityInfo($activity_id)
                ]
            ];
        }

        // 验证排序类型
        $validate = new SquareActivityVal;
        if (!$validate->scene('sort')->check(compact('sort_condition', 'sort_type'))) {
            if (!in_array($sort_condition, SquareActivityM::SORT_CONDITION['limit'])) {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
            }
        }

        if ($uid > 0) {
            // 获取用户模型
            $user = UserM::get($uid, true);

            // 检查用户状态
            if (is_null($user)) {
                throw new Error(Status::USER_NOT_EXIST);
            }

            // 根据分页条件获取评论列表
            if ('all' === $status) {
                $select = SquareActivityM::where('uid = ?', [$uid]);
            } else if ('available' === $status) {
                $select = SquareActivityM::where('uid = ? AND private = false AND delete_time = 0', [$uid]);
            } else if ('private' === $status) {
                $select = SquareActivityM::where('uid = ? AND private = true AND delete_time = 0', [$uid]);
            } else if ('delete' === $status) {
                $select = SquareActivityM::where('uid = ? AND delete_time > 0', [$uid]);
            } else {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的统计类型');
            }
        } else {
            if ('all' === $status) {
                $total_count = (new System)->squareActivityCount();
                $select = SquareActivityM::where(true);
            } else if ('available' === $status) {
                $select = SquareActivityM::where('private = false AND delete_time = 0');
            } else if ('private' === $status) {
                $select = SquareActivityM::where('private = true AND delete_time = 0');
            } else if ('delete' === $status) {
                $select = SquareActivityM::where('delete_time > 0');
            } else {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的统计类型');
            }
        }
        
        $select_copy = clone $select;
        if (!isset($total_count)) {
            $total_count = $select->count();
        }

        $activity_list = $select_copy
                        ->page($page, $limit)
                        ->order($sort_condition, $sort_type)
                        ->select();
        $data = [];
        $data['limit'] = $limit;
        $data['page'] = $page;
        $data['total_page'] = ceil($total_count / $limit);
        $data['list'] = [];

        foreach ($activity_list as $activity) {
            $data['list'][] = $this->getActivityInfo($activity);
        }

        return $data;

    }

    /**
     * 获取动态的评论列表
     * @author lwtting <smlling@hotmail.com>
     * @param array   $options              参数
     * @return array                        评论数组
     */
    public function getCommentList ($options = []) {

        $params = [
            'status'            => 'all',
            'page'              => 1,
            'limit'             => 20,
            'activity_id'       => 0,
            'uid'               => 0,
            'sort_condition'    => 'post_time',
            'sort_type'         => 'desc'
        ];

        $params = array_merge($params, $options);
        extract($params);

        if ($page <= 0 || $limit <= 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 错误的页码或页限');
        }

        if ($activity_id < 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的动态id');
        }

        if ($uid < 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的用户id');
        }

        // 普通用户只能获取正常状态的评论列表
        if (!$this->isAdmin) {
            $status = 'available';
        }

        // 验证排序类型
        $validate = new SquareCommentVal;
        if (!$validate->scene('sort')->check($params)) {
            if (!in_array($sort_condition, SquareCommentM::SORT_CONDITION['limit'])) {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
            }
        }

        if (is_numeric($activity_id) && $activity_id > 0) {
            // 获取动态模型
            $activity = SquareActivityM::get($activity_id, true);

            // 检查动态状态
            if (is_null($activity) || (!$this->isAdmin && $activity->isDeleted())) {
                throw new Error(Status::ACTIVITY_NOT_EXIST);
            }

            // 目标动态隐藏
            if (!$this->isAdmin && $activity->isPrivate()) {
                throw new Error(Status::ACTIVITY_PRIVATE);
            }

            // 根据分页条件获取评论列表
            if ('all' === $status) {
                $select = SquareCommentM::where('root_id = ? AND reply_to = 0', [$activity_id]);
            } else if ('available' === $status) {
                $select = SquareCommentM::where('root_id = ? AND reply_to = 0 AND delete_time = 0', [$activity_id]);
            } else if ('delete' === $status) {
                $select = SquareCommentM::where('root_id = ? AND reply_to = 0 AND delete_time > 0', [$activity_id]);
            } else {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的统计类型');
            }
        } else {
            // 普通用户只能获取指定动态的评论列表
            if (!$this->isAdmin) {
                throw new Error(Status::NEED_PRIVILEGE);
            }

            if ($uid > 0) {
                if ('all' === $status) {
                    $select = SquareCommentM::where('reply_to = 0 AND uid = ?', [$uid]);
                } else if ('available' === $status) {
                    $select = SquareCommentM::where('reply_to = 0 AND delete_time = 0 AND uid = ?', [$uid]);
                } else if ('delete' === $status) {
                    $select = SquareCommentM::where('reply_to = 0 AND delete_time > 0 AND uid = ?', [$uid]);
                } else {
                    throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的统计类型');
                }
            } else {
                if ('all' === $status) {
                    $select = SquareCommentM::where('reply_to = 0');
                } else if ('available' === $status) {
                    $select = SquareCommentM::where('reply_to = 0 AND delete_time = 0');
                } else if ('delete' === $status) {
                    $select = SquareCommentM::where('reply_to = 0 AND delete_time > 0');
                } else {
                    throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的统计类型');
                }
            }
        }
        
        $select_copy = clone $select;
        $total_count = $select->count();
        $comment_list = $select_copy
                        ->page($page, $limit)
                        ->order($sort_condition, $sort_type)
                        ->select();
        
        $data = [];
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['total_page'] = ceil($total_count / $limit);
        $data['list'] = [];

        foreach ($comment_list as $comment) {
            $data['list'][] = $this->getCommentInfo($comment);
        }

        return $data;

    }

    /**
     * 获取指定评论的回复列表
     * @author lwtting <smlling@hotmail.com>
     * @param integer $comment_id
     * @param string $sort_condition        指定排序依据
     * @param string $sort_type             指定排序类型
     * @return array
     */
    public function getReplyList ($comment_id, $sort_condition = 'post_time', $sort_type = 'DESC') {

        if (!is_numeric($comment_id) || $comment_id <= 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的评论id');
        }

        // 验证排序类型
        $validate = new SquareCommentVal;
        if (!$validate->scene('sort')->check(compact('sort_condition', 'sort_type'))) {
            if (!in_array($sort_condition, SquareCommentM::SORT_CONDITION['limit'])) {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
            }
        }

        // 获取评论模型
        $comment = SquareCommentM::get($comment_id, true);

        // 检查评论状态
        if(is_null($comment)) {
            throw new Error(Status::COMMENT_NOT_EXIST);
        }

        // 获取回复列表
        if ($this->isAdmin) {
            $reply_list = SquareCommentM::where('root_id = ? AND reply_to > 0', [$comment_id])
                                        ->order($sort_condition, $sort_type)
                                        ->select();
        } else {
            $reply_list = SquareCommentM::where('root_id = ? AND reply_to > 0 AND delete_time = 0', [$comment_id])
                                        ->order($sort_condition, $sort_type)
                                        ->select();
        }

        $data = [];

        foreach ($reply_list as $reply) {
            $data[] = $this->getCommentInfo($reply);

        }
        
        return $data;

    }

    /**
     * 删除动态
     * @author lwtting <smlling@hotmail.com>
     * @param integer $activity_id          动态id
     * @param integer $delete_reason        删除原因
     * @return array                        动态信息
     */
    public function deleteActivity($activity_id, $delete_reason = null) {

        if (!is_numeric($activity_id) || $activity_id <= 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的动态id');
        }

        if (!$this->isAdmin) {
            $delete_reason = SquareActivityM::DEL_USER_INITIACTIVE;
        } else {
            // 检查删除原因合法性
            $validate = new SquareActivityVal;
            if (!$validate->scene('delete')->check(compact('delete_reason'))) {
                throw new Error(Status::FORM_PARAM_ERROR, $validate->getError());
            }
        }
        
        // 获取动态模型
        $activity = SquareActivityM::get($activity_id, true);
        // 检查动态状态
        if (is_null($activity) || $activity->isDeleted()) {
            throw new Error(Status::ACTIVITY_NOT_EXIST);
        }
        if (!$this->isAdmin && $activity->uid !== $this->user->id) {
            throw new Error(Status::NEED_PRIVILEGE);
        }

        // 获取动态发布者模型
        $user = $this->isAdmin ? UserM::get($activity->uid, true) : $this->user;

        Db::startTrans();
        try {
            // 获取一级评论列表
            $comment_list = SquareCommentM::where('root_id = ? AND reply_to = 0 AND delete_time = 0', [$activity_id])
                                            ->limit($activity->comment_count)
                                            ->select();
            // 逐条删除评论
            foreach ($comment_list as $comment) {
                // 对于每个一级评论获取其所有回复
                $reply_list = SquareCommentM::where('root_id = ? AND reply_to > 0 AND delete_time = 0', [$comment->id])
                                            ->limit($comment->reply_count)
                                            ->select();
                // 逐条删除回复
                foreach ($reply_list as $reply) {
                    $reply->del(SquareCommentM::DEL_RELATE);
                }
                $comment->del(SquareCommentM::DEL_RELATE);
            }
            // 删除附件
            $attach_files = json_decode($activity->attach_files);
            foreach ($attach_files as $file_id) {
                $file = AttachFile::get($file_id, true);
                $file->del();
            }
            // 删除主动态
            $activity->del($delete_reason);
            // 自减发布者的动态发布数量
            $user->decPostActivityCount();
            // 更新系统动态量计数器
            SystemCounter::deleteActivity($activity->isPrivate());

            if ($this->isAdmin) {
                // 记录日志
                AdminLog::log($this->user->username, '删除动态: "id:' . $activity_id .'"成功');
            }
            
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        
        if ($this->isAdmin) {
            return $this->getActivityInfo($activity);
        }

    }

    /**
     * 删除评论
     * @author lwtting <smlling@hotmail.com>
     * @param integer $comment_id           欲删评论id
     * @param integer $delete_reason        删除原因
     * @return array|void                   被删除评论的信息
     */
    public function deleteComment ($comment_id, $delete_reason = null) {

        if (!is_numeric($comment_id) || $comment_id <= 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的评论id');
        }

        if (!$this->isAdmin) {
            $delete_reason = SquareCommentM::DEL_USER_INITIACTIVE;
        } else {
            // 检查删除原因是否允许
            $validate = new SquareCommentVal;
            if (!$validate->scene('delete')->check(compact('delete_reason'))) {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
            }
        }

        // 获取评论模型
        $comment = SquareCommentM::get($comment_id, true);

        // 检查评论状态
        if (is_null($comment) || $comment->isDeleted()) {
            throw new Error(Status::COMMENT_NOT_EXIST);
        }

        if (!$this->isAdmin && $comment->uid !== $this->user->id) {
            throw new Error(Status::NEED_PRIVILEGE);
        }

        // 获取发布者用户模型
        $user = $this->isAdmin ? UserM::get($comment->uid) : $this->user;

        Db::startTrans();
        try {
            // 如果欲删除的是根评论(一级评论)
            // 则获取回复列表并逐条删除
            // 否则只删除本身
            if (0 == $comment->reply_to) {
                $reply_list = SquareCommentM::where('root_id = ? AND reply_to > 0 AND delete_time = 0', [$comment_id])
                                        ->limit($comment->reply_count)
                                        ->select();
                foreach ($reply_list as $reply) {
                    $reply->del(SquareCommentM::DEL_RELATE);
                }
            }
            
            // 如果删除的评论是一级评论则将主动态评论数自减
            if (0 == $comment->reply_to) {
                $activity = SquareActivityM::get($comment->root_id, true);
                $activity->decCommentCount();
                // 自减发布者的评论发布数量
                $user->decCommentActivityCount();
            } else {
                // 删除的是对评论的回复则将根评论的评论数自减
                $root_comment = SquareCommentM::get($comment->root_id, true);
                $root_comment->decReplyCount();
            }
            // 删除评论
            $comment->del($delete_reason);
            AdminLog::log($this->user->username, '删除评论: "id:' . $comment_id .'"成功');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        if ($this->isAdmin) {
            return $this->getCommentInfo($comment);
        }
        
    }

    /**
     * 广场发表动态(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param string    $content              内容
     * @param boolean   $private              是否隐藏(仅自己可见)
     * @param array     $attach_file          附件
     * @param string    $ip                   ip地址
     * @return array                          动态信息
     */
    public function postActivity ($content, $private, $attach_files, $ip) {

        // 检查当前用户是否绑定手机号
        if (!$this->user->isBindPhone()) {
            throw new Error(Status::PHONE_NOT_BAND);
        }

        // 检查发帖频率
        if (cache('activity_post_' . $this->user->id)) {
            throw new Error(Status::POST_FREQUENTLY);
        }
        
        // 检查内容合法性
        $validate = new SquareActivityVal;
        if (!$validate->scene('post')->check(compact('content'))) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }
        
        $file = new AttachFile;
        $attach_list = [];

        Db::startTrans();

        try {
            
            // 添加附件入库
            foreach ($attach_files as $attach_file) {
                $attach_list[$attach_file['filename']] = $file->add ($this->user->id, $attach_file['path'], $attach_file['type'], $ip)->id;
            }
            // 添加动态入库
            $activity = (new SquareActivityM)->add($this->user->id, $content, $private, $attach_list, $ip);

            // 自增发布者的动态发布数量
            $this->user->incPostActivityCount();

            // 更新系统动态量计数器
            SystemCounter::postActivity($private);
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        // 写入缓存 用于控制发帖频率
        cache('activity_post_' . $this->user->id, '1', config('settings.square.post_invitation_interval'));

        return $this->getActivityInfo($activity);

    }

    /**
     * 发表评论(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $root_id              根id(当reply_id=0时root_id表示的是动态id,此时的评论是一级评论;否则表示的是一级评论的评论id)
     * @param integer $reply_to             回复id(当不为0时,此时的评论是子评论,即对评论的评论)
     * @param string  $content              评论内容
     * @param string  $ip                   ip地址
     * @return array
     */
    public function postComment ($root_id, $reply_to, $content, $ip) {

        if (!is_numeric($root_id) || $root_id <= 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的根id');
        }

        if (!is_numeric($reply_to) || $reply_to < 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的回复对象');
        }

        // 检查当前用户是否绑定手机号
        if (!$this->user->isBindPhone()) {
            throw new Error(Status::PHONE_NOT_BAND);
        }
        
        $validate = new SquareCommentVal;
        if (!$validate->scene('post')->check(compact('content'))) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: ' . $validate->getError());
        }

        // 发表一级评论
        if (0 == $reply_to) {

            // 获取主动态模型
            $activity = SquareActivityM::get($root_id, true);
            // 检查主动态状态
            $this->checkActivityStatus($activity);
            
            Db::startTrans();

            try {
                // 自增发布者的评论发布数量
                $this->user->incCommentActivityCount();
                // 自增动态的回复数量
                $activity->incCommentCount();
                // 更新主动态最新评论时间
                $activity->updateCommentTime();
                // 添加评论
                $comment = (new SquareCommentM)->add($this->user->id, $root_id, $reply_to, $content, $ip);

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        // 发表二级评论|回复
        } else {
            // 获取根评论模型
            $root_comment = SquareCommentM::get($root_id, true);
            // 检查根评论状态
            $this->checkCommentStatus($root_comment);
            // 检查根评论是否是根评论
            if (!$root_comment->isComment()) {
                throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的根评论id');
            }
            // 获取主动态模型
            $activity = SquareActivityM::get($root_comment->root_id, true);
            // 检查主动态状态
            $this->checkActivityStatus($activity);

            // 当root_id !== reqly_id 时表示此时是对一个评论的回复进行回复, 需要检查回复对象的状态
            if ($root_id !== $reply_to) {
                $reply_comment = SquareCommentM::get($reply_to, true);
                $this->checkCommentStatus($reply_comment);

                // 检查回复对象的根评论id和输入的是否一致 不是一级评论
                if ($reply_comment->root_id !== $root_id || $reply_comment->isComment()) {
                    throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的回复对象id');
                }
            }

            Db::startTrans();

            try {

                // 添加评论
                $comment = (new SquareCommentM)->add($this->user->id, $root_id, $reply_to, $content, $ip);
                // 自增根评论的评论数量
                $root_comment->incReplyCount();
                // 更新主动态最新评论时间
                $activity->updateCommentTime();
                
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();

                throw $e;
            }
        }

        return $this->getCommentInfo($comment);

    }

    /**
     * 点赞广场动态(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $activity_id          动态id
     * @return array
     */
    public function likeActivity ($activity_id) {

        if (!is_numeric($activity_id) || $activity_id <= 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的动态id');
        }

        // 检查当前用户是否绑定手机号
        if (!$this->user->isBindPhone()) {
            throw new Error(Status::PHONE_NOT_BAND);
        }

        // 获取动态模型
        $activity = SquareActivityM::get($activity_id, true);
        // 检查动态状态
        $this->checkActivityStatus($activity);

        // 查找当前登录用户对目标动态的点赞记录
        $like = SquareLike::where('uid = ? AND like_to = ? AND like_type = ?', [$this->user->id, $activity_id, SquareLike::LIKE_ACTIVITY])
                            // ->cache('square_like_' . $this->user->id . '_' . $activity_id . '_' . SquareLike::LIKE_ACTIVITY)
                            ->find();

        Db::startTrans();

        try {
            // 未找到点赞记录则新增
            if (is_null($like)) {
                $like = (new SquareLike)->add($this->user->id, $activity_id, SquareLike::LIKE_ACTIVITY);
            } else {
                $like->changeLikeStatus();
            }

            // 根据点赞/取消后的点赞状态确定执行的到底是点赞还是取消点赞
            // 并作出相应的数据更新
            if ($like->liked()) {
                // 操作为点赞
                $activity->incLikeCount();
                $this->user->incLikeActivityCount();
            } else {
                // 操作为取消点赞
                $activity->decLikeCount();
                $this->user->decLikeActivityCount();
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        
        return $this->getActivityInfo($activity);

    }

    /**
     * 点赞动态内的评论/回复(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param integer $comment_id          评论id
     * @return array
     */
    public function likeComment ($comment_id) {

        if (!is_numeric($comment_id) || $comment_id <= 0) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 无效的评论id');
        }

        // 检查当前用户是否绑定手机号
        if (!$this->user->isBindPhone()) {
            throw new Error(Status::PHONE_NOT_BAND);
        }

        // 获取评论模型
        $comment = SquareCommentM::get($comment_id, true);
        // 检查评论状态
        $this->checkCommentStatus($comment);

        // 查找当前登录用户对目标评论的点赞记录
        $like = SquareLike::where('uid = ? AND like_to = ? AND like_type = ?', [$this->user->id, $comment_id, SquareLike::LIKE_COMMENT])
                            // ->cache('square_like_' . $this->user->id . '_' . $comment_id . '_' . SquareLike::LIKE_COMMENT)
                            ->find();

        Db::startTrans();

        try {
            // 未找到点赞记录则新增
            if (is_null($like)) {
                $like = (new SquareLike)->add($this->user->id, $comment_id, SquareLike::LIKE_COMMENT);
            } else {
                $like->changeLikeStatus();
            }

            // 根据点赞/取消后的点赞状态确定执行的到底是点赞还是取消点赞
            // 并作出相应的数据更新
            if ($like->liked()) {
                $comment->incLikeCount();
            } else {
                $comment->decLikeCount();
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        return $this->getCommentInfo($comment);

    }

    /**
     * 搜索动态(user only)
     * @author lwtting <smlling@hotmail.com>
     * @param string $keyword          搜索关键词
     * @return array
     */
    public function searchActivity ($keyword) {

        if (!$keyword) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 缺少搜索关键词');
        }

        $activity_list = SquareActivityM::where('delete_time = 0 AND private = false AND content LIKE ?', ['%' . $keyword . '%'])
                                ->limit(50)
                                ->cache('search_activity_' . $keyword, 120)
                                ->select();
        $data = [];
        foreach ($activity_list as $activity) {
            $data[] = $this->getActivityInfo($activity);
        }

        return $data;
    }
}