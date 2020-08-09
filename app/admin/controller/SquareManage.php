<?php

namespace app\admin\controller;

use think\Controller;
use app\lib\exception\Status;
use app\api\service\Square;


class SquareManage extends Controller {

    protected $middleware = [
        'RoleCheck:admin' => [
            'except' => [

            ]
        ]
    ];

    /**
     * 实例化广场服务
     * @author lwtting <smlling@hotmail.com>
     * @return SquareService
     */
    protected function service () {
        return new Square($this->request->loginUserM);
    }

    /**
     * 获取动态列表
     * @api get:admin/SquareManage/activity/list
     * @author lwtting <smlling@hotmail.com>
     * @return json                     用户列表
     */
    public function activityList(){

        return $this->success('获取动态列表成功',$this->service()->getActivityList([
            'status'            => input('get.status/s', 'all'),
            'page'              => input('get.page/d', 1),
            'limit'             => input('get.limit/d', 20),
            'activity_id'       => input('get.activity_id/d', 0),
            'uid'               => input('get.uid/d', 0),
            'sort_condition'    => input('get.sort_condition/s', 'post_time'),
            'sort_type'         => input('get.sort_type/s', 'desc')
        ]));

    }

    /**
     * 获取评论列表
     * @api get:admin/SquareManage/comment/list
     * @author lwtting <smlling@hotmail.com>
     * @return json
     */
    public function getCommentList () {

        return $this->success('获取评论列表成功',$this->service()->getCommentList([
            'status'            => input('get.status/s', 'all'),
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
     * @api get:admin/SquareManage/omment/reply/list
     * @param integer $comment_id
     * @return json
     */
    public function getReplyList () {

        $comment_id = input('get.id/d', 0);

        return $this->success('获取回复列表成功', $this->service()->getReplyList($comment_id));
    }
    
    /**
     * 删除动态
     * @api post:admin/SquareManage/activity/delete
     * @author lwtting <smlling@hotmail.com>
     * @param integer $activity_id
     * @param integer $delete_reason
     * @return json
     */
    public function deleteActivity() {

        $activity_id = input('get.id/d', 0);
        $delete_reason = input('get.reason/d', 0);

        return $this->success('删除成功', $this->service()->deleteActivity($activity_id, $delete_reason));

    }

    /**
     * 删除评论/回复
     * @api post:admin/SquareManage/comment/delete
     * @param integer $comment_id
     * @return json
     */
    public function deleteComment () {
        
        $comment_id = input('get.id/d', 0);
        $delete_reason = input('get.reason/d', 0);
        
        return $this->success('删除评论成功', $this->service()->deleteComment($comment_id, $delete_reason));

    }
}