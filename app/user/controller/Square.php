<?php

namespace app\user\controller;

use think\Controller;
use app\common\Upload;
use app\lib\exception\Status;
use app\api\service\Square as SquareService;

class Square extends Controller {

    protected $middleware = [ 
        'RoleCheck:user'     => ['except'     => [ 
            
        ] ]
    ];

    /**
     * 实例化广场服务
     * @author lwtting <smlling@hotmail.com>
     * @return SquareService
     */
    protected function service () {
        return new SquareService($this->request->loginUserM);
    }

    /**
     * 广场发表动态
     * @author lwtting <smlling@hotmail.com>
     * @api post:square/activity/post
     * @return json         发表的动态信息
     */
    public function postActivity () {

        $content = input('post.content/s');
        $private = input('post.private/b', false);
        $ip = $this->request->ip();

        $attach_files = (new Upload())->uploadSquareFile($this->request->loginUserM->username);

        return $this->success('动态发表成功', $this->service()->postActivity($content, $private, $attach_files, $ip));

    }

    /**
     * 获取广场动态详情
     * @api get:square/activity/detail
     * @author lwtting <smlling@hotmail.com>
     * @return json                     动态信息
     */
    public function getActivityDetail () {

        $activity_id = input('get.id/d', 0);

        return $this->success('获取动态详情成功', $this->service()->getActivityInfo($activity_id));

    }

    /**
     * 发布评论
     * @api post:square/activity/comment/post
     * @author lwtting <smlling@hotmail.com>
     * @return json                     发表的评论信息
     */
    public function postComment () {

        $content = input('post.content/s');
        $root_id = input('post.root/d');
        $reply_to = input('post.to/d', 0);
        $ip = $this->request->ip();

        return $this->success('发表成功', $this->service()->postComment($root_id, $reply_to, $content, $ip));

    }

    /**
     * 删除广场动态
     * @api get:square/activity/delete
     * @author lwtting <smlling@hotmail.com>
     * @return json                     操作结果
     */
    public function deleteActivity () {

        $activity_id = input('get.id/d', 0);

        $this->service()->deleteActivity($activity_id);

        return $this->success('动态删除成功');

    }

    /**
     * 删除动态评论
     * @api get:square/activity/comment/delete
     * @author lwtting <smlling@hotmail.com>
     * @return json                     操作结果
     */
    public function deleteComment () {

        $comment_id = input('get.id/d', 0);

        $this->service()->deleteComment($comment_id);

        return $this->success('评论删除成功');

    }

    /**
     * 获取广场动态列表
     * @api get:square/activity/list
     * @author lwtting <smlling@hotmail.com>
     * @return json                     动态列表
     */
    public function getActivityList () {

        return $this->success('获取广场动态列表成功', $this->service()->getActivityList([
            'page'              => input('get.page/d', 1),
            'limit'             => input('get.limit/d', 20),
            'uid'               => input('get.uid/d', 0),
            'sort_condition'    => input('get.sort_condition/s', 'post_time'),
            'sort_type'         => input('get.sort_type/s', 'desc')
        ]));

    }

    /**
     * 获取指定动态的评论列表
     * @api get:square/activity/comment/list
     * @author lwtting <smlling@hotmail.com>
     * @return json                     评论列表
     */
    public function getCommentList () {

        return $this->success('获取评论列表成功', $this->service()->getCommentList ([
            'page'              => input('get.page/d', 1),
            'limit'             => input('get.limit/d', 20),
            'activity_id'       => input('get.activity_id/d', 0),
            'uid'               => input('get.uid/d', 0),
            'sort_condition'    => input('get.sort_condition/s', 'post_time'),
            'sort_type'         => input('get.sort_type/s', 'desc')
        ]));

    }

    /**
     * 获取指定评论的回复列表
     * @api get:square/activity/comment/reply/list
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function getReplyList () {

        $root_id = input('get.root_id/d', 0);

        return $this->success('获取回复列表成功', $this->service()->getReplyList($root_id));

    }


    /**
     * 获取附件
     * @api get:square/activity/:activity_id/attach/:file_id/:type
     * @author lwtting <smlling@hotmail.com>
     * @param integer $activity_id      动态id
     * @param integer $file_id          文件id
     * @param string  $type             获取类型 large|small
     * @return image|video
     */
    public function getAttachFile ($activity_id, $file_id, $type = small) {

        $activity_id = intval($activity_id);
        $file_id = intval($file_id);

        if ($activity_id <= 0 || $file_id <= 0) {
            return ;
        }

        $file = $this->service()->getAttachFilePath($activity_id, $file_id);
        if (isset($file[$type])) {
            $path = $file[$type];
        } else {
            return ;
        }
        
        return download($file[$type], 'attach', false, 360, true);

    }

    /**
     * 点赞/取消点赞动态
     * @api post:square/activity/like
     * @return json                     操作结果
     */
    public function likeActivity () {

        $activity_id = input('get.id/d', 0);

        return $this->success('点赞动态成功', $this->service()->likeActivity($activity_id));

    }

    /**
     * 点赞/取消点赞评论
     * @api post:square/activity/comment/like
     * @param integer $comment_id       评论id
     * @return json                     操作结果
     */
    public function likeComment () {

        $comment_id = input('get.id/d', 0);

        return $this->success('点赞评论成功', $this->service()->likeComment($comment_id));

    }

    /**
     * 搜索动态
     * @api get:square/activity/search
     * @author lwtting <smlling@hotmail.com>
     * @return json                     搜索到的动态列表
     */
    public function searchActivity () {
        
        $keyword = input('get.keyword/s');

        return $this->success('搜索成功', $this->service()->searchActivity($keyword));

    }
}