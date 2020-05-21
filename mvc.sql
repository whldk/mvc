/*
Navicat MySQL Data Transfer

Source Server         : root
Source Server Version : 50717
Source Host           : 127.0.0.1:3306
Source Database       : mvc

Target Server Type    : MYSQL
Target Server Version : 50717
File Encoding         : 65001

Date: 2020-05-21 15:22:31
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for admin
-- ----------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '' COMMENT 'hash id 对外显示',
  `group` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户分组(0：超级管理员）',
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '登录名',
  `authkey` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '授权key',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '密码hash',
  `realname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`_id`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of admin
-- ----------------------------

-- ----------------------------
-- Table structure for app
-- ----------------------------
DROP TABLE IF EXISTS `app`;
CREATE TABLE `app` (
  `_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '' COMMENT 'hash id 对外显示',
  `app_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `app_secret` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '分类',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `info` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '简介',
  `site_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '网站地址',
  `callback_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '回调地址',
  `logo100x100` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '100x100图片',
  `logo64x64` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '100x100图片',
  `logo32x32` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '100x100图片',
  `logo16x16` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '100x100图片',
  `status` tinyint(1) unsigned DEFAULT NULL COMMENT '0: 失效，1：通过，2：审核',
  `approver_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '' COMMENT '审核人（超管）',
  `created_at` int(10) unsigned NOT NULL,
  `updated_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`_id`),
  UNIQUE KEY `id` (`id`) USING BTREE,
  UNIQUE KEY `app_key` (`app_key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of app
-- ----------------------------

-- ----------------------------
-- Table structure for app_token
-- ----------------------------
DROP TABLE IF EXISTS `app_token`;
CREATE TABLE `app_token` (
  `app_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `owner_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `access_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `refresh_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expired_at` int(10) unsigned DEFAULT NULL,
  `created_at` int(10) unsigned DEFAULT NULL,
  `updated_at` int(10) unsigned DEFAULT NULL,
  `group` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户分组(0：未分组；1：学校管理员；2：老师；3：学生）',
  PRIMARY KEY (`app_id`),
  KEY `app_owner` (`app_id`,`owner_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of app_token
-- ----------------------------

-- ----------------------------
-- Table structure for ip
-- ----------------------------
DROP TABLE IF EXISTS `ip`;
CREATE TABLE `ip` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `segment1` tinyint(3) unsigned DEFAULT NULL COMMENT '禁止访问的IP 或者IP 段',
  `segment2` tinyint(3) unsigned DEFAULT NULL COMMENT '禁止访问的IP 或者IP 段',
  `segment3` tinyint(3) unsigned DEFAULT NULL COMMENT '禁止访问的IP 或者IP 段',
  `segment4` tinyint(3) unsigned DEFAULT NULL COMMENT '禁止访问的IP 或者IP 段',
  `mask` tinyint(3) unsigned DEFAULT NULL COMMENT '掩码',
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of ip
-- ----------------------------

-- ----------------------------
-- Table structure for ip_list
-- ----------------------------
DROP TABLE IF EXISTS `ip_list`;
CREATE TABLE `ip_list` (
  `from` int(10) unsigned NOT NULL COMMENT '地址范围开始',
  `to` int(10) unsigned NOT NULL COMMENT '地址范围结束',
  `byte1` tinyint(3) unsigned DEFAULT NULL,
  `byte2` tinyint(3) unsigned DEFAULT NULL,
  `byte3` tinyint(3) unsigned DEFAULT NULL,
  `byte4` tinyint(3) unsigned DEFAULT NULL,
  `mask_len` tinyint(3) unsigned DEFAULT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`from`,`to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of ip_list
-- ----------------------------

-- ----------------------------
-- Table structure for post
-- ----------------------------
DROP TABLE IF EXISTS `post`;
CREATE TABLE `post` (
  `_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `cid` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pic` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `thumbnail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `digest` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` mediumtext COLLATE utf8_unicode_ci,
  `view_count` int(10) unsigned NOT NULL DEFAULT '0',
  `top` tinyint(3) unsigned DEFAULT '0',
  `pending` tinyint(3) unsigned DEFAULT NULL COMMENT '0 不提交审核， 1 提交审核',
  `status` tinyint(4) DEFAULT NULL COMMENT '0 未发布，1 发布',
  `created_at` int(10) DEFAULT NULL,
  `updated_at` int(10) DEFAULT NULL,
  `author_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `order` smallint(6) DEFAULT '0',
  `final_order` char(16) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `document` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `download_count` int(10) DEFAULT NULL,
  PRIMARY KEY (`_id`),
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `status_default_order_cid` (`status`,`final_order`,`cid`) USING BTREE,
  KEY `cid_default_order_status` (`cid`,`final_order`,`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of post
-- ----------------------------

-- ----------------------------
-- Table structure for post_category
-- ----------------------------
DROP TABLE IF EXISTS `post_category`;
CREATE TABLE `post_category` (
  `_id` int(10) NOT NULL AUTO_INCREMENT,
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `pid` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `level` tinyint(4) DEFAULT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0' COMMENT '0 未发布/软删除，1 发布，2 审核',
  `section` char(20) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '页面版块，由前端指定和使用',
  `order` int(11) DEFAULT NULL,
  `section_nav` tinyint(4) DEFAULT NULL COMMENT '页面版块导航显示  1 or 0',
  `sort` int(11) DEFAULT '0',
  `template` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`_id`),
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=213 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of post_category
-- ----------------------------

-- ----------------------------
-- Table structure for school
-- ----------------------------
DROP TABLE IF EXISTS `school`;
CREATE TABLE `school` (
  `_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '' COMMENT 'hash id 对外显示',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '学校名称',
  `alias` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '学校别名',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '可用状态',
  `contact_person` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `contact_phone` char(11) COLLATE utf8_unicode_ci DEFAULT '',
  `contact_addr` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `contact_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `server` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '学校服务器域名',
  `bg` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '学校logo',
  `banner` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '学校logo',
  `declaration_doc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `declaration_video` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `show_declaration` tinyint(3) DEFAULT '0',
  `site_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '学校名称',
  `site_introduction` varchar(500) COLLATE utf8_unicode_ci DEFAULT '',
  `login_logo` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '学校logo',
  `logo` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '学校logo',
  `inside_logo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `project_name` varchar(600) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`_id`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of school
-- ----------------------------
INSERT INTO `school` VALUES ('1', 'pa', 'doctoru', 'mvc', '1', 'xx1', '110', null, null, '', 'upload/school/2018/11/83c7fcd39057e5533f99f0f752f7678c.jpg', 'upload/school/2018/08/d1a0736d2f44311cdc6e5abeee8282ca.jpg', '', '', '0', '', '', 'upload/school/2018/10/157c56d9214cb4b66197955da9c60914.png', 'upload/school/2018/10/6f02af9004009d2b300675ab7d368b14.png', null, '');

-- ----------------------------
-- Table structure for school_class
-- ----------------------------
DROP TABLE IF EXISTS `school_class`;
CREATE TABLE `school_class` (
  `_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '' COMMENT 'hash id 对外显示',
  `school_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT 'hash id 对外显示',
  `code` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '学校编码',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '学校名称',
  `grade` smallint(5) unsigned DEFAULT NULL COMMENT '年级',
  `major` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '专业',
  `status` tinyint(1) unsigned DEFAULT '1' COMMENT '可用状态',
  `created_at` int(10) unsigned DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`_id`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of school_class
-- ----------------------------
INSERT INTO `school_class` VALUES ('146', 'EO4', 'pa', '', '测试班级', '2019', '测试', '1', '1560820690', '1560842125');
INSERT INTO `school_class` VALUES ('147', '3lP', 'pa', '', '1111', '2019', '111', '1', '1582186035', '1582186035');

-- ----------------------------
-- Table structure for session
-- ----------------------------
DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `expire` int(10) unsigned NOT NULL,
  `data` varchar(2000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of session
-- ----------------------------
INSERT INTO `session` VALUES ('ufak65dhf6rt5e9mch7q6sunuj', '1590051864', '');

-- ----------------------------
-- Table structure for student_class
-- ----------------------------
DROP TABLE IF EXISTS `student_class`;
CREATE TABLE `student_class` (
  `school_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0' COMMENT '所属学校id',
  `class_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0' COMMENT '所属班级id',
  `user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0' COMMENT '用户id',
  `is_main` tinyint(4) NOT NULL,
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`user_id`,`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of student_class
-- ----------------------------
INSERT INTO `student_class` VALUES ('pa', '3lP', 'NXL', '0', '1582454986');
INSERT INTO `student_class` VALUES ('pa', 'EO4', 'NXL', '1', '1560842118');
INSERT INTO `student_class` VALUES ('pa', 'EO4', 'RXZ', '1', '1560820704');
INSERT INTO `student_class` VALUES ('pa', 'EO4', 'rWK', '1', '1560820704');

-- ----------------------------
-- Table structure for teacher_class
-- ----------------------------
DROP TABLE IF EXISTS `teacher_class`;
CREATE TABLE `teacher_class` (
  `school_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0' COMMENT '学校id',
  `class_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0' COMMENT '班级id',
  `user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0' COMMENT '教师id',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`user_id`,`class_id`),
  KEY `class_id` (`class_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of teacher_class
-- ----------------------------

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'hash id 对外显示的id',
  `school_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0' COMMENT '学校id',
  `group` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户分组(0：未分组；1：学校管理员；2：老师；3：学生）',
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '登录名',
  `iusername` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `authkey` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '授权key',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '密码hash',
  `realname` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '登录名',
  `gender` tinyint(4) DEFAULT NULL COMMENT 'null代表性别不确定',
  `avatar` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '头像',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_temp` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `intro_page` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '头像',
  `intro` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`_id`),
  UNIQUE KEY `username` (`username`) USING BTREE,
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `status_school_id_group_id` (`status`,`group`,`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1145 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='用户表';

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES ('902', 'j0', 'pa', '1', 'test1', 'test1', 'Kgje5PFHeFHxcTkoSTt-vpNuEkq69ep7', '$2y$10$kmrkj8RAdHbkl1NnRIkf9OkWu4DBxRyp0zzgx1Y1o2tAHHrmqcIWi', 'test1', '1', '', '1', '1520842912', '1560842554', '0', 'www', '<p>fsdfds</p>');
INSERT INTO `user` VALUES ('1031', 'xrd', 'pa', '1', 'admin_tj', null, 'bMifU1uiiGmMtlPH1uY_TnXy5zUIbUL9', '$2y$10$8QKKC1Z0wwNbzVLKKzy/mOUwaBFu3YdscxOuKi.2LbGopkuTMIk7y', 'tj', '1', '', '1', '1559185211', '1559185211', '0', '', '');
INSERT INTO `user` VALUES ('1032', 'rWK', 'pa', '3', 'xs_tj', null, 'kr-Ve7xUB9bVykwEzcfO1S33zih-b0bQ', '$2y$10$TL0i1awyLAMUJNZx.qHk2OmETX85YDGiy9BdPefplP89mWY6Z4t3W', 'tj', '1', '', '1', '1559198051', '1560820288', '0', '', null);
INSERT INTO `user` VALUES ('1141', 'RXZ', 'pa', '3', 'xs', null, 'Sm8_erGxrMwZqMzF_bQCLDn2oXhm-caa', '$2y$10$uyhNE1FUuKBvBp50NID65.zUvZX84D3pLL/hq1GZr1D4kd9yqWEw2', '学生', '1', '', '1', '1560820704', '1560820704', '0', '', null);
INSERT INTO `user` VALUES ('1142', 'NXL', 'pa', '3', 'xs007', null, '', '$2y$10$ZJLd.hmLBmLEZUklw1IvM..j0NBDiBB1ONvTCyGmVL2GrFXU51Eye', 'whldk', '1', '', '1', '1560842118', '1570844405', '0', '', null);
INSERT INTO `user` VALUES ('1143', 'v2w', 'pa', '2', 'laoshi007', null, '', '$2y$10$lXHFvHmcp5rnKKayY4z66e6VAtD9LDFJ.8qQS0T.SXJ.8Cbi0MVE2', 'laoshi007', '1', '', '1', '1560842191', '1560842191', '0', '', '');
INSERT INTO `user` VALUES ('1144', 'nWj', 'pa', '2', 'wh_mw', null, '', '$2y$10$zTUUVS99CIckPWRB7rC.5uanXXR40mBppsDTUuZDVSqaxQWrVRdai', 'mw', '1', '', '1', '1570844390', '1570844390', '0', '', '');

-- ----------------------------
-- Table structure for user_ilab
-- ----------------------------
DROP TABLE IF EXISTS `user_ilab`;
CREATE TABLE `user_ilab` (
  `user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ilab_id` int(10) unsigned DEFAULT NULL,
  `ilab_un` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ilab_dis` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `ilab_id` (`ilab_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of user_ilab
-- ----------------------------
