# 主音社区-服务端

## 一款基于[ThinkPHP 5.1](https://github.com/top-think/framework/tree/5.1)开发的一个简单的社区后端服务

## 本项目的管理后台页面前端项目为[zhuyin-admin](https://github.com/smlling/zhuyin-admin)

## Usage
1. 创建数据库并导入表结构[zhuyin.sql](zhuyin.sql)
2. 修改config目录下的各个配置文件填写你实际的数据库、redis、域名等信息
3. 系统初始化
    > ```bash
    > #安装依赖
    > composer install
    > #初始化超级管理员账户admin/zhuyin_admin
    > php think initSuperAdmin
    > ```
4. 按照常规web服务器搭建流程将<font color="red">public目录</font>映射为根目录

## TODO List:
+ 用户接口
    - [x] 用户注册/登陆
    - [x] 用户信息获取/修改
    - [x] 广场发布/删除动态/评论
    - [x] 广场动态/评论点赞
    - [ ] 消息系统
    - [ ] 广告系统
+ 管理接口
    - [x] 管理员登陆/注销
    - [x] 管理员信息获取/修改
    - [x] 用户管理
    - [x] 广场动态管理
    - [x] 广场评论管理
    - [ ] 广告管理
    - [ ] 系统设置
