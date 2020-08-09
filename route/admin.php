<?php
/**
 * 移动端接口路由文件
 */


//后台入口  默认进入后台
Route::get('/','https://wx.lwting.top:65003');

Route::get('admin/t','admin/Demo/demo');

// 管理员登录√
Route::post('admin/home/login','admin/Home/login')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 管理员信息获取√
Route::get('admin/home/info','admin/Home/getInfo')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 管理员注销登录√
Route::get('admin/home/logout','admin/Home/logout')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 管理员更改密码√
Route::post('admin/home/changePassword','admin/Home/changePassword')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 管理员更改昵称√
Route::post('admin/home/changeNickname','admin/Home/changeNickname')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 系统总览数据√
Route::get('admin/SystemManage/dashboard','admin/SystemManage/Dashboard')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 用户列表√
Route::get('admin/UserManage/user/list','admin/UserManage/userList')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 获取用户信息√
Route::get('admin/UserManage/user/info','admin/UserManage/getInfo')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 冻结用户√
Route::post('admin/UserManage/user/freeze','admin/UserManage/freezeUser')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 解冻用户√
Route::post('admin/UserManage/user/unfreeze','admin/UserManage/unfreezeUser')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 重置用户密码√
Route::post('admin/UserManage/user/reset','admin/UserManage/resetUser')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 动态列表√
Route::get('admin/SquareManage/activity/list','admin/SquareManage/activityList')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 删除动态√
Route::get('admin/SquareManage/activity/delete','admin/SquareManage/deleteActivity')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 评论列表√
Route::get('admin/SquareManage/comment/list','admin/SquareManage/getCommentList')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 评论的回复列表√
Route::get('admin/SquareManage/comment/reply/list','admin/SquareManage/getReplyList')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();

// 删除评论/回复√
Route::get('admin/SquareManage/comment/delete','admin/SquareManage/deleteComment')
    ->header('Access-Control-Allow-Origin','http://localhost:8080')
    // ->header('Access-Control-Allow-Origin','https://wx.lwting.top:65003')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->allowCrossDomain();