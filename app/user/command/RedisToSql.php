<?php

namespace app\user\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use \think\Db;
use think\facade\Cache;

class RedisToSql extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('redistosql');
        // 设置参数
        
    }

    protected function execute(Input $input, Output $output)
    {
        //点赞数据表插入队列缓存key
        $redis_like_insert_queue_list_key = 'like_insert_queue';
        $redis_like_insert_queue_list = Cache::pull($redis_like_insert_queue_list_key) ?: [];

        //点赞数据表更新队列缓存key
        $redis_like_update_queue_list_key = 'like_update_queue';
        $redis_like_update_queue_list = Cache::pull($redis_like_update_queue_list_key) ?: [];

        //帖子数据表更新队列缓存key
        $redis_invitation_update_queue_list_key = 'invitation_update_queue';
        $redis_invitation_update_queue_list = Cache::pull($redis_invitation_update_queue_list_key) ?: [];

        //评论数据表更新队列缓存key
        $redis_comment_update_queue_list_key = 'comment_update_queue';
        $redis_comment_update_queue_list = Cache::pull($redis_comment_update_queue_list_key) ?: [];

        

        $like_insert_list = [];
        // $like_update_list = [];
        // $invitation_update_list = [];
        // $comment_update_list = [];
        
        Db::startTrans();
        try {
            foreach ($redis_like_insert_queue_list as $redis_like_info_key) {
                $like_info = cache($redis_like_info_key) ?: false;
                if ($like_info) {
                    // $like_insert_list[] = $like_info;
                    $like_info['id'] = Db::name('like')->insertGetId($like_info);
                    cache($redis_like_info_key, $like_info);
                }
            }
            // Db::name('like')
            //     ->insertAll($like_insert_list);

            foreach ($redis_like_update_queue_list as $redis_like_info_key) {
                $like_info = cache($redis_like_info_key) ?: false;
                if ($like_info) {
                    // $like_update_list[] = $like_info;
                    Db::name('like')
                        ->update($like_info);
                }
            }

            foreach ($redis_invitation_update_queue_list as $redis_invitation_info_key) {
                $invitation_info = cache($redis_invitation_info_key) ?: false;
                if ($invitation_info) {
                    // $invitation_update_list[] = $invitation_info;
                    Db::name('invitation')
                        ->update($invitation_info);
                }
            }

            foreach ($redis_comment_update_queue_list as $redis_comment_info_key) {
                $comment_info = cache($redis_comment_info_key) ?: false;
                if ($comment_info) {
                    // $comment_update_list[] = $comment_info;
                    Db::name('comment')
                        ->update($comment_info);
                }
            }
            // 提交事务
            Db::commit();
            // foreach ($redis_like_insert_queue_list as $redis_like_info_key) {
            //     cache($redis_like_info_key, null);
            // }
            $output->writeln('【' . date('Y-m-d h:i:s') . '】缓存入库成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            //获取本脚本执行过程中新增的入队列表并与脚本执行前的列表合并后写入缓存，防止数据丢失
            $new_redis_like_insert_queue_list = Cache::pull($redis_like_insert_queue_list_key) ?: [];
            cache($redis_like_insert_queue_list_key, array_merge($new_redis_like_insert_queue_list, $redis_like_insert_queue_list));

            $new_redis_like_update_queue_list = Cache::pull($redis_like_update_queue_list_key) ?: [];
            cache($redis_like_update_queue_list_key, array_merge($new_redis_like_update_queue_list, $redis_like_update_queue_list));

            $new_redis_invitation_update_queue_list = Cache::pull($redis_invitation_update_queue_list_key) ?: [];
            cache($redis_invitation_update_queue_list_key, array_merge($new_redis_invitation_update_queue_list, $redis_invitation_update_queue_list));

            $new_redis_comment_update_queue_list = Cache::pull($redis_comment_update_queue_list_key) ?: [];
            cache($redis_comment_update_queue_list_key, array_merge($new_redis_comment_update_queue_list, $redis_comment_update_queue_list));
            
            $output->writeln('【' . date('Y-m-d h:i:s') . '】缓存入库失败，错误:' . $e->getMessage());
        }
    }
}
