/*
 Navicat Premium Data Transfer

 Source Server         : zhuyin
 Source Server Type    : MySQL
 Source Server Version : 50727
 Source Host           : 10.1.1.3:3306
 Source Schema         : zhuyin

 Target Server Type    : MySQL
 Target Server Version : 50727
 File Encoding         : 65001

 Date: 11/04/2020 09:49:59
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for yin_admin
-- ----------------------------
DROP TABLE IF EXISTS `yin_admin`;
CREATE TABLE `yin_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` tinyint(4) NOT NULL DEFAULT '2' COMMENT '角色1=超级管理员2=普通管理员',
  `username` varchar(16) NOT NULL COMMENT '用户名',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `nickname` varchar(20) NOT NULL COMMENT '昵称',
  `email` varchar(50) NOT NULL COMMENT '邮箱',
  `phone` varchar(11) NOT NULL COMMENT '电话',
  `add_time` int(11) NOT NULL COMMENT '添加时间',
  `last_login_ip` varchar(15) NOT NULL DEFAULT '0.0.0.0' COMMENT '上次成功登陆ip',
  `freeze_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '冻结时间',
  `freeze_reason` tinyint(4) NOT NULL DEFAULT '0' COMMENT '冻结原因',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '上次成功登陆时间',
  `password_mtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '密码更改时间',
  `login_failed_times` tinyint(4) NOT NULL DEFAULT '0' COMMENT '连续登录失败次数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='管理员表';

-- ----------------------------
-- Table structure for yin_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `yin_admin_log`;
CREATE TABLE `yin_admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin` varchar(16) NOT NULL COMMENT '管理员用户名',
  `action` varchar(255) NOT NULL COMMENT '行为',
  `time` int(11) NOT NULL COMMENT '操作时间',
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0' COMMENT 'IP',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8 COMMENT='管理员操作日志表';

-- ----------------------------
-- Table structure for yin_attach_file
-- ----------------------------
DROP TABLE IF EXISTS `yin_attach_file`;
CREATE TABLE `yin_attach_file` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '上传者uid',
  `path` varchar(255) NOT NULL COMMENT '路径',
  `file_type` enum('image','video') NOT NULL COMMENT '类型image|video',
  `upload_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0' COMMENT '上传者ip地址',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COMMENT='附件上传表';

-- ----------------------------
-- Table structure for yin_config
-- ----------------------------
DROP TABLE IF EXISTS `yin_config`;
CREATE TABLE `yin_config` (
  `key` varchar(50) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统设置表';

-- ----------------------------
-- Table structure for yin_drum
-- ----------------------------
DROP TABLE IF EXISTS `yin_drum`;
CREATE TABLE `yin_drum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beats` int(11) NOT NULL COMMENT '拍数',
  `speed` int(11) NOT NULL COMMENT '速度',
  `drum_type` int(11) NOT NULL COMMENT '鼓种',
  `section` int(11) NOT NULL COMMENT '分节',
  `name` varchar(50) NOT NULL COMMENT '曲谱名字',
  `publisher` varchar(16) NOT NULL COMMENT '发布者',
  `publish_time` int(11) NOT NULL COMMENT '发布时间',
  `is_public` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否公开',
  `delete_time` int(11) NOT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='鼓谱(待定)';

-- ----------------------------
-- Table structure for yin_drum_comment
-- ----------------------------
DROP TABLE IF EXISTS `yin_drum_comment`;
CREATE TABLE `yin_drum_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invitation_id` int(11) NOT NULL COMMENT '广场主贴id',
  `parent_id` int(11) NOT NULL COMMENT '父评论id',
  `content` varchar(200) NOT NULL COMMENT '内容',
  `publisher` varchar(16) NOT NULL COMMENT '发布者',
  `post_time` int(11) NOT NULL COMMENT '发布时间',
  `delete_time` int(11) NOT NULL COMMENT '删除时间',
  `delete_reason` tinyint(4) NOT NULL COMMENT '删除原因',
  `like_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被赞数量',
  `comment_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='评论';

-- ----------------------------
-- Table structure for yin_square_activity
-- ----------------------------
DROP TABLE IF EXISTS `yin_square_activity`;
CREATE TABLE `yin_square_activity` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布者uid',
  `content` varchar(400) NOT NULL DEFAULT '' COMMENT '正文内容',
  `post_time` int(11) NOT NULL DEFAULT '0' COMMENT '发布时间',
  `private` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否隐藏(仅自己可见)',
  `last_comment_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后一次评论时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  `delete_reason` tinyint(4) NOT NULL DEFAULT '0' COMMENT '删除原因',
  `attach_files` varchar(400) NOT NULL DEFAULT '[]' COMMENT '附件',
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0' COMMENT 'IP地址',
  `like_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `comment_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `comment_count_all` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评论数(包括删除)',
  `share_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分享数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='广场动态表';

-- ----------------------------
-- Table structure for yin_square_comment
-- ----------------------------
DROP TABLE IF EXISTS `yin_square_comment`;
CREATE TABLE `yin_square_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布者uid',
  `root_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '根id 当reply_to=0时此字段表示动态id  不为零则表示一级评论的id',
  `reply_to` int(11) NOT NULL COMMENT '若是对评论的回复则此字段表示父评论id',
  `content` varchar(300) NOT NULL COMMENT '内容',
  `post_time` int(11) NOT NULL COMMENT '发布时间',
  `delete_time` int(11) unsigned NOT NULL COMMENT '删除时间',
  `delete_reason` tinyint(4) NOT NULL COMMENT '删除原因',
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0' COMMENT 'ip地址',
  `like_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `reply_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `reply_count_all` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评论数(包括删除)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COMMENT='广场评论表';

-- ----------------------------
-- Table structure for yin_square_like
-- ----------------------------
DROP TABLE IF EXISTS `yin_square_like`;
CREATE TABLE `yin_square_like` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点赞人uid',
  `like_to` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被赞id',
  `like_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '点赞类型1=动态 2=评论',
  `like_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点赞时间0=点赞已取消',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='广场点赞表';

-- ----------------------------
-- Table structure for yin_system_counter
-- ----------------------------
DROP TABLE IF EXISTS `yin_system_counter`;
CREATE TABLE `yin_system_counter` (
  `id` tinyint(3) unsigned NOT NULL COMMENT '计数器id 非自增',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '计数器名字',
  `count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计数 json_encode',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统计数器表';

-- ----------------------------
-- Table structure for yin_user
-- ----------------------------
DROP TABLE IF EXISTS `yin_user`;
CREATE TABLE `yin_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(25) NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(25) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(80) NOT NULL DEFAULT '' COMMENT '头像',
  `home_bg` varchar(80) NOT NULL DEFAULT '' COMMENT '主页背景图',
  `sex` enum('男','女','保密') NOT NULL DEFAULT '保密' COMMENT '性别',
  `age` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '年龄0=未设置/不显示',
  `location` varchar(30) NOT NULL DEFAULT '' COMMENT '地区',
  `description` varchar(50) NOT NULL DEFAULT '' COMMENT '个人描述',
  `abandon_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '账号注销时间',
  `register_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  `unfreeze_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '解冻时间',
  `freeze_reason` tinyint(4) NOT NULL DEFAULT '0' COMMENT '被冻结原因',
  `phone` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `weibo` varchar(32) NOT NULL DEFAULT '' COMMENT '微博',
  `wechat` varchar(32) NOT NULL DEFAULT '' COMMENT '微信',
  `qq` varchar(15) NOT NULL DEFAULT '' COMMENT 'QQ',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '上次登录时间',
  `last_login_ip` varchar(15) NOT NULL DEFAULT '0.0.0.0' COMMENT '上次登录ip',
  `login_failed_times` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '连续登陆失败次数',
  `username_mtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户名更改时间',
  `password_mtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '密码更改时间',
  `post_activity_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发表的动态数量',
  `post_activity_count_all` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发表的动态数量(包括删除)',
  `post_comment_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发表的评论数量',
  `post_comment_count_all` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发表的评论数量(包括删除)',
  `like_activity_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点赞的动态数量',
  `device_identifier` varchar(300) NOT NULL DEFAULT '' COMMENT '客户端标识符(mac地址或者IMEI等信息)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING HASH COMMENT '用于设置用户名的场景',
  UNIQUE KEY `phone` (`phone`) USING HASH
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='用户信息表';

-- ----------------------------
-- Table structure for yin_user_auth
-- ----------------------------
DROP TABLE IF EXISTS `yin_user_auth`;
CREATE TABLE `yin_user_auth` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL,
  `identity_type` varchar(10) NOT NULL DEFAULT '' COMMENT '登录类型',
  `identifier` varchar(255) NOT NULL DEFAULT '' COMMENT '标识',
  `credential` varchar(255) NOT NULL DEFAULT '' COMMENT '密码凭证',
  `dissolved` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已解除绑定',
  PRIMARY KEY (`id`),
  KEY `联合查询` (`identity_type`,`identifier`,`dissolved`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='用户登录信息表';

SET FOREIGN_KEY_CHECKS = 1;
