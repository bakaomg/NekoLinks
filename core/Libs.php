<?php
/**
 * 插件常用类
 * 
 * @Author: ohmyga
 * @Date: 2022-08-23 15:24:15
 * @LastEditTime: 2022-08-23 16:32:45
 */

namespace TypechoPlugin\NekoLinks\core;

use Typecho\Db;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Libs
{
    /**
     * 单例句柄
     * 
     * @access private
     * @var Libs
     */
    private static Libs $instance;

    /**
     * 数据库句柄
     * 
     * @access private
     * @var DB
     */
    private DB $db;

    /**
     * 初始化
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        $this->db = DB::get();
    }

    /**
     * 获取单例句柄
     * 
     * @static
     * @access public
     * @return Libs
     */
    public static function getInstance(): Libs
    {
        if (!isset($instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 判断数据库表是否存在
     * 
     * @param string $table
     * @return bool
     */
    public function checkTable(string $table): bool
    {
        return empty($this->db->fetchAll($this->db->select("table_name")->from("information_schema.TABLES")->where("table_name = ?", $this->db->getPrefix() . $table))) ? false : true;
    }
}
