<?php
/**
 * Neko Links Core
 * 
 * @Author: ohmyga
 * @Date: 2022-08-23 14:16:15
 * @LastEditTime: 2023-01-10 05:46:40
 */

namespace TypechoPlugin\NekoLinks\core;

use Typecho\Db;
use Typecho\Db\Exception as DbException;
use Typecho\Plugin\Exception as PluginException;
use Typecho\Plugin as TypechoPlugin;
use Utils\Helper;

use function explode;
use function strtolower;
use function trim, str_replace;
use function file_exists, file_get_contents;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Neko
{
    /**
     * 初始化
     * 
     * @static
     * @access public
     * @return void
     */
    public static function init(): void
    {
        $db = Db::get();

        // 适配器名称
        $adapter = strtolower(explode('_', $db->getAdapterName())[1] ?? "mysql");
        // 数据库表前缀
        $prefix = $db->getPrefix();

        // 判断数据表是否存在
        if (Libs::getInstance()->checkTable("nekolinks")) {
            return;
        }

        // 数据库初始化文件目录
        $sqlfile = __DIR__ . "/../files/{$adapter}.sql";
        if (!file_exists($sqlfile)) throw new PluginException("NekoLinks 数据库初始化文件不存在");

        // 数据库语句
        $sql_scripts = file_get_contents($sqlfile);
        $sql_scripts = str_replace("%prefix%", $prefix, $sql_scripts); // 替换表前缀
        $sql_scripts = str_replace("%engine%", "InnoDB", $sql_scripts); // 替换数据库引擎
        $sql_scripts = str_replace("%charset%", "utf8mb4", $sql_scripts); // 替换字符集
        $sql_scripts = explode(';', $sql_scripts); // 分割 SQL 语句

        try {
            foreach ($sql_scripts as $sql) {
                if ($sql = trim($sql)) {
                    $db->query($sql, Db::WRITE);
                }
            }
        } catch (DbException $e) {
            throw new PluginException("NekoLinks 数据表建立出错，错误代码：" . $e->getCode());
        }
    }

    /**
     * 获取插件版本号
     * 
     * @static
     * @access public
     * @return string
     */
    public static function getVersion(): string
    {
        $info = TypechoPlugin::parseInfo(__DIR__ . '/../Plugin.php');
        return $info["version"] ?? "";
    }

    /**
     * 注册与禁用 Action
     * 
     * @static
     * @access public
     * @param bool $add      是否添加
     * @return void
     */
    public static function registerAction(bool $add = true): void
    {
        if ($add) {
            Helper::addAction('nekolinks', '\TypechoPlugin\NekoLinks\Action');
            Helper::addAction('nekolinks-api', '\TypechoPlugin\NekoLinks\Action');
        } else {
            Helper::removeAction('nekolinks');
            Helper::removeAction('nekolinks-api');
        }
    }
}
