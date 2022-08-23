CREATE TABLE `%prefix%nekolinks` (
    `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT "ID",
    `name` varchar(200) default NULL COMMENT '名称',
    `url` varchar(250) default NULL COMMENT '链接',
    `avatar` varchar(250) default NULL COMMENT '头像',
    `description` varchar(250) default NULL COMMENT '介绍',
    `mail` varchar(200) default NULL COMMENT '邮箱',
    `sort` varchar(200) default NULL COMMENT '分类',
    `data` varchar(300) default NULL COMMENT '自定义内容',
    `order` int(10) unsigned default '0' COMMENT '排序',
    PRIMARY KEY  (`uid`)
) ENGINE=%engine% DEFAULT CHARSET=%charset%;
