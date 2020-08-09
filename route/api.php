<?php
/**
 * 移动端接口路由文件
 */

//异步请求：http://serverName/index.php?api=路由表达式

use app\common\Authcode;
use app\api\service\Square;
use app\lib\exception\Status;

//测试接口
Route::get('user/t','user/Demo/demo');
Route::post('user/t','user/Demo/demo');

// 获取手机验证码 √
Route::get('authcode', function () {
    $phone = input('phone/s');
    $ip = request()->ip();
    $device_id = request()->header('Device_Identifier') ?: $ip;

    (new Authcode($phone, $device_id, $ip))->send();

    return json([
        'success'       => true,
        'msg'           => '验证码获取成功',
        'data'          => [],
        'status_code'   => Status::SUCCESS
    ]);
});

// 获取广场指定动态的指定附件√
Route::get('square/activity/attach', function () {
    $activity_id = input('get.activity/d', 0);
    $filename = input('get.attach/s');
    $type = input('get.type/s', 'small');

    if ($activity_id <= 0 || !$filename) {
        return ;
    }

    $file = (new Square(request()->loginUserM))->getAttachFilePath($activity_id, $filename);
    if (isset($file[$type])) {
        $path = $file[$type];
    } else {
        return ;
    }
    
    return download($file[$type], 'attach', false, 360, true);
})
    ->middleware(['RoleCheck:all'])
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 用户注册(仅手机)√
Route::post('home/register','user/Home/register');

// 用户设置头像√
Route::post('home/avatar', 'user/Home/setAvatar');

// 获取头像√
Route::get('home/avatar', 'user/Home/getAvatar');

// 用户设置个人信息√
Route::post('home/info','user/Home/setInfo');

// 用户获取个人信息√
Route::get('home/info','user/Home/getInfo');

// 用户设置用户名√
Route::post('home/info/username','user/Home/setUsername');

// 用户登录√
Route::post('home/login','user/Home/login');

// 用户新设备登录验证√
Route::post('home/login/verify','user/Home/verifyNewDevice');

// 用户注销登录√
Route::get('home/logout','user/Home/logout');

// 搜索用户√
Route::get('home/searchUser', 'user/Home/searchUser');

// 用户修改密码√
Route::post('home/changePassword','user/Home/changePassword');

// 用户重置密码√ 先获取短信验证码
Route::post('home/resetPassword','user/Home/resetPassword');



// 广场发布动态√
Route::post('square/activity/post', 'user/square/postActivity');

// 广场获取动态列表√
Route::get('square/activity/list', 'user/square/getActivityList');

// 广场获取动态详情√
Route::get('square/activity/detail', 'user/square/getActivityDetail');

// 广场删除动态√
Route::get('square/activity/delete', 'user/square/deleteActivity');

// 广场点赞/取消点赞动态√
Route::get('square/activity/like', 'user/square/likeActivity');

// 广场获取动态评论√
Route::get('square/activity/comment/list', 'user/square/getCommentList');

// 广场获取评论回复√
Route::get('square/activity/comment/reply/list', 'user/square/getReplyList');

// 广场动态内发布评论√
Route::post('square/activity/comment/post', 'user/square/postComment');

// 广场删除评论√
Route::get('square/activity/comment/delete', 'user/square/deleteComment');

// 广场点赞/取消点赞评论√
Route::get('square/activity/comment/like', 'user/square/likeComment');

// 广场搜索动态√
Route::get('square/activity/search', 'user/square/searchActivity');
