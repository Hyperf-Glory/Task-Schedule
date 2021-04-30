CREATE TABLE `edge` (
                        `edge_id` int unsigned NOT NULL AUTO_INCREMENT,
                        `start_vertex` int NOT NULL DEFAULT '0',
                        `end_vertex` int DEFAULT '0',
                        PRIMARY KEY (`edge_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `task` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        `workflow_id` int unsigned NOT NULL DEFAULT '0',
                        `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
                        `status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'open',
                        `result` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
                        `parents` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
                        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `vertex_edge` (
                               `id` int unsigned NOT NULL AUTO_INCREMENT,
                               `workflow_id` int unsigned NOT NULL DEFAULT '0',
                               `task_id` int unsigned DEFAULT '0',
                               `pid` int unsigned DEFAULT '0',
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
CREATE TABLE `workflow` (
                            `id` int unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
                            `is_active` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0 否 1 是',
                            `status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
                            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for application
-- ----------------------------
DROP TABLE IF EXISTS `application`;
CREATE TABLE `application` (
                               `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
                               `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
                               `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否审核 0:否 1:是',
                               `app_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '应用名称',
                               `app_key` char(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'APP KEY',
                               `secret_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SECRET KEY',
                               `step` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '重试间隔(秒)',
                               `retry_total` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
                               `link_url` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '接口地址',
                               `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
                               `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                               `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
                               PRIMARY KEY (`id`) USING BTREE,
                               UNIQUE KEY `unq_app_key` (`app_key`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='工作任务';

-- ----------------------------
-- Table structure for task
-- ----------------------------
DROP TABLE IF EXISTS `task`;
CREATE TABLE `task` (
                        `id` bigint(20) unsigned NOT NULL COMMENT '主键ID',
                        `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
                        `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '任务状态 0:待处理 1:处理中 2:已处理 3:已取消',
                        `app_key` char(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT 'APP KEY',
                        `task_no` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '任务编号',
                        `step` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '重试间隔(秒)',
                        `runtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '执行时间',
                        `content` longtext CHARACTER SET utf8 NOT NULL COMMENT '任务内容',
                        `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                        `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
                        PRIMARY KEY (`id`),
                        KEY `idx_task_no` (`app_key`,`task_no`),
                        KEY `idx_is_deleted` (`is_deleted`,`status`,`runtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='任务列表';

-- ----------------------------
-- Table structure for task_abort
-- ----------------------------
DROP TABLE IF EXISTS `task_abort`;
CREATE TABLE `task_abort` (
                              `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
                              `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
                              `task_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '任务ID',
                              `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '拦截状态 0:未知 1:拦截成功',
                              `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                              `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
                              PRIMARY KEY (`id`),
                              KEY `idx_task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='拦截记录';

-- ----------------------------
-- Table structure for task_log
-- ----------------------------
DROP TABLE IF EXISTS `task_log`;
CREATE TABLE `task_log` (
                            `id` bigint(20) unsigned NOT NULL COMMENT '主键ID',
                            `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
                            `task_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '任务ID',
                            `retry` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
                            `remark` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '备注信息',
                            `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                            `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
                            PRIMARY KEY (`id`),
                            KEY `idx_task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='系统日志';

SET FOREIGN_KEY_CHECKS = 1;
