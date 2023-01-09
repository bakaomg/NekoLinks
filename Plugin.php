<?php

namespace TypechoPlugin\NekoLinks;

use ReflectionException;
use Utils\Helper;
use Typecho\Common;
use Typecho\Widget\Helper\Form;
use Typecho\Plugin\PluginInterface;
use Typecho\Plugin\Exception as PluginException;
use TypechoPlugin\NekoLinks\core\Libs;

use function is_dir, mkdir;
use function json_encode;
use function str_replace;
use function array_slice, shuffle;
use function serialize, unserialize;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 为 Typecho 1.2.0+ 提供友情链接支持
 *
 * @package NekoLinks
 * @author ohmyga
 * @version 0.1.0
 * @link https://github.com/bakaomg/NekoLinks
 */

/**
 *  _   _      _         _     _       _        
 * | \ | |    | |       | |   (_)     | |       
 * |  \| | ___| | _____ | |    _ _ __ | | _____ 
 * | . ` |/ _ \ |/ / _ \| |   | | '_ \| |/ / __|
 * | |\  |  __/   < (_) | |___| | | | |   <\__ \
 * \_| \_/\___|_|\_\___/\_____/_|_| |_|_|\_\___/
 * 
 * Author: ohmyga
 * Github: https://github.com/bakaomg/NekoLinks
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
        // 创建缓存文件夹
        // 判断文件夹是否存在
        if (!is_dir(__DIR__ . "/cache")) {
            // 不存在尝试创建文件夹
            if (!mkdir(__DIR__ . "/cache", 0777, true)) {
                throw new PluginException("NekoLinks 尝试创建缓存目录失败，请检查插件目录读写权限是否充足。");
            }
        }

        // 初始化插件
        \TypechoPlugin\NekoLinks\core\Neko::init();
        // 注册 Action
        \TypechoPlugin\NekoLinks\core\Neko::registerAction(true);
        // 在后台「管理」菜单中添加友链管理入口
        Helper::addPanel(3, 'NekoLinks/manage-links.php', 'NekoLinks / 友链管理', 'NekoLinks 的控制面板', 'administrator');
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

        $options = Libs::getInstance()->getOptions();

        if ($options["deleteData"] === true) {
            $tablePrefix = Libs::getInstance()->db->getPrefix();
            Libs::getInstance()->db->query("DROP TABLE IF EXISTS `{$tablePrefix}nekolinks`");
            Libs::getInstance()->db->query(Libs::getInstance()->db->delete('table.options')->where('name = ?', 'plugin:NekoLinksSortsBackup'));
            unlink(__DIR__ . "/cache/.migrate_links");
        } else {
            $sortsList = Libs::getInstance()->getSorts();
            $backup = Libs::getInstance()->db->fetchRow(
                Libs::getInstance()->db
                    ->select()
                    ->from("table.options")
                    ->where("name = ?", "plugin:NekoLinksSortsBackup")
            );
            if (empty($backup)) {
                $sql = Libs::getInstance()->db->insert("table.options")->rows([
                    "name" => "plugin:NekoLinksSortsBackup",
                    "user" => 0,
                    "value" => serialize(json_encode($sortsList))
                ]);
            } else {
                $sql = Libs::getInstance()->db->update("table.options")->rows([
                    "value" => serialize(json_encode($sortsList))
                ])->where("name = ?", "plugin:NekoLinksSortsBackup");
            }
            Libs::getInstance()->db->query($sql);
        }
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        $options = json_encode([
            "api"                 => false,
            "deleteData"          => false,
            "randomSort"          => false,
            "outputNumberLimit"   => false,
            "outputNumber"        => 0,
            "migrateDismiss"      => false,
        ]);
        $nekolinks = new \Typecho\Widget\Helper\Form\Element\Textarea('options', NULL, $options, "插件配置");
        $form->addInput($nekolinks);

        $sortBackup = Libs::getInstance()->db->fetchRow(
            Libs::getInstance()->db
                ->select()
                ->from("table.options")
                ->where("name = ?", "plugin:NekoLinksSortsBackup")
        )["value"] ?? null;
        $sort = ($sortBackup !== null) ? unserialize($sortBackup) : json_encode([["id" => "default", "name" => "默认分类"]]);
        $sortList = new \Typecho\Widget\Helper\Form\Element\Textarea('sortList', NULL, $sort, '分类列表', NULL);
        $form->addInput($sortList);

        echo "<script type=\"text/javascript\">window.location.href=\"" . Common::url(defined('__TYPECHO_ADMIN_DIR__') ? __TYPECHO_ADMIN_DIR__ : '/admin/', null) . "extending.php?panel=NekoLinks%2Fmanage-links.php\"</script>";
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 输出友链数组
     * 
     * @param bool $order                根据排序字段排序 (如果插件设置中的 randomSort 为 true 则无效)
     * @param bool $asc                  升序 (true) 或降序 (false) (如果插件设置中的 randomSort 为 true 则无效)
     * @param bool $returnDefaultAvatar  当没有头像时是否返回默认头像
     * @return array
     */
    public static function linksArray(bool $order = false, bool $asc = true, $returnDefaultAvatar = false): array
    {
        $links = Libs::getInstance()->getLinks($order, $asc, $returnDefaultAvatar);
        $options = Libs::getInstance()->getOptions();

        if ($options["outputNumberLimit"] === true) {
            $links = array_slice($links, 0, $options["outputNumber"]);
        }

        if ($options["randomSort"] === true) {
            shuffle($links);
        }

        return $links;
    }

    /**
     * 输出不受配置项影响的友链数组
     * 
     * @param bool $order                根据排序字段排序
     * @param bool $asc                  升序 (true) 或降序 (false)
     * @param bool $returnDefaultAvatar  当没有头像时是否返回默认头像
     */
    public static function rawLinksArray(bool $order = false, bool $asc = true, $returnDefaultAvatar = false): array
    {
        return Libs::getInstance()->getLinks($order, $asc, $returnDefaultAvatar);
    }

    /**
     * 输出 HTML 结构的友链
     * 
     * @param string $template  模板
     * @param bool $order       根据排序字段排序 (如果插件设置中的 randomSort 为 true 则无效)
     * @param bool $asc         升序 (true) 或降序 (false) (如果插件设置中的 randomSort 为 true 则无效)
     * @param bool $echo        是否直接输出
     */
    public static function linksOutput(string $template = '', bool $order = false, bool $asc = true, bool $echo = true): string
    {
        $links = self::linksArray($order, $asc, true);
        $template = (!empty($template)) ? $template : NULL;
        $defaultTemplate = '<li><a href="{url}" target="_blank" title="{description}" rel="nofollow"><img src="{avatar}" width="20" height="20" alt="{name}"><span>{name}</span></a></li>' . "\n";

        $output = "";
        foreach ($links as $link) {
            $output .= str_replace(
                ["{url}", "{name}", "{description}", "{avatar}", "{mail}", "{sort}", "{data}", "{order}"],
                [$link["url"], $link["name"], $link["description"], $link["avatar"], $link["mail"], $link["sort"], $link["data"], $link["order"]],
                $template ?? $defaultTemplate
            );
        }
        $output = empty($template) ? '<ul>' . "\n" . $output . '</ul>' : "";

        if ($echo) {
            echo $output;
        }

        return $output;
    }
}
