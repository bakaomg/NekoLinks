<?php

namespace TypechoPlugin\NekoLinks;

use ReflectionException;
use Utils\Helper;
use Typecho\Widget\Helper\Form;
use Typecho\Plugin\PluginInterface;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 为 Typecho 1.2.0+ 提供友情链接支持
 *
 * @package NekoLinks
 * @author ohmyga
 * @version 0.1.0
 * @link https://github.com/bakaomg/NekoLinks
 */

class Plugin implements PluginInterface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws ReflectionException
     */
    public static function activate(): void
    {
        // 初始化插件
        \TypechoPlugin\NekoLinks\core\Neko::init();
        // 注册 Action
        \TypechoPlugin\NekoLinks\core\Neko::registerAction(true);
        // 在后台「管理」菜单中添加友链管理入口
        Helper::addPanel(3, 'NekoLinks/manage-links.php', '链接管理', '链接管理', 'administrator');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws ReflectionException
     */
    public static function deactivate(): void
    {
        // 移除 Action
        \TypechoPlugin\NekoLinks\core\Neko::registerAction(false);
        // 移除后台菜单
        Helper::removePanel(3, 'NekoLinks/manage-links.php');
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        $rmTable = new \Typecho\Widget\Helper\Form\Element\Checkbox('rmTable', [
            'areYouSure' => '禁用 NekoLinks 时一并删除数据表'
        ], NULL, NULL, NULL);
        $form->addInput($rmTable);
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }
}
