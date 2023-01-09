<?php

namespace TypechoPlugin\NekoLinks;

use Typecho\Widget;
use TypechoPlugin\NekoLinks\core\Libs;

use function strtolower, strtoupper;
use function explode;
use function file_put_contents;
use function json_decode, json_encode;
use function serialize;
use function is_array, array_map, shuffle;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Action
 * 
 * @Author: ohmyga
 * @Date: 2022-08-23 16:26:11
 * @LastEditTime: 2023-01-10 05:04:27
 */

class Action extends Widget implements \Widget\ActionInterface
{
    /**
     * 每次请求都会执行的函数
     * 
     * @return void
     */
    public function execute(): void
    {
        $this->setCORS();
        $this->dispatch();
    }

    /**
     * Action
     * 
     * @return void
     */
    public function action(): void
    {
    }

    /**
     * 配置 CORS 跨域
     * 
     * @return void
     * @throws \Typecho\Plugin\Exception
     */
    private function setCORS(): void
    {
        $origin = Libs::getInstance()->request->getServer('HTTP_ORIGIN');
        Libs::getInstance()->response->setHeader('Access-Control-Allow-Origin', $origin ?: '*');

        if (strtolower(Libs::getInstance()->request->getServer('REQUEST_METHOD')) == 'options') {
            Libs::getInstance()->response->setStatus(204);
            Libs::getInstance()->response->setHeader('Access-Control-Allow-Headers', 'Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type');
            Libs::getInstance()->response->setHeader('Access-Control-Allow-Methods', 'GET, PUT, DELETE, POST, OPTIONS');
            exit;
        }
    }

    /**
     * 请求分发
     * 
     * @access private
     * @return void
     */
    private function dispatch(): void
    {
        $path = Libs::getInstance()->request->getPathInfo();
        $path = explode('/', $path);
        if (!empty($path[2]) && strtolower($path[2]) === "nekolinks-api") {
            if (!empty(Libs::getInstance()->getOptions()) && Libs::getInstance()->getOptions()["api"] === false) Libs::getInstance()->sendJson(true, 404, "Not Found");
            if (empty($path[3])) Libs::getInstance()->sendJson(true, 200, "NekoLinks API is working.");
            $action = strtolower($path[3]);

            match ($action) {
                "getall" => $this->NekoAPI_getAllLinks(),
                default => (function () {
                    Libs::getInstance()->sendJson(false, 404, "Not Found");
                })()
            };
        } else {
            match (Libs::getInstance()->getQueryParams()) {
                "" => (function () {
                    Libs::getInstance()->sendJson(false, 403, "Forbidden");
                })(),

                // 管理页面
                "type=neko" => (function () {
                    Libs::getInstance()->sendJson(false, 403, "Nya~");
                })(),
                "type=neko&action=migrate" => $this->Neko_migrate(),
                "type=neko&action=migrate_dismiss" => $this->Neko_migrateDismiss(),
                "type=neko&action=init" => $this->Neko_init(),
                "type=neko&action=change_settings" => $this->Neko_changeSettings(),
                "type=neko&action=add_link" => $this->Neko_addLink(),
                "type=neko&action=delete_link" => $this->Neko_deleteLink(),
                "type=neko&action=delete_links" => $this->Neko_deleteLinks(),
                "type=neko&action=edit_link" => $this->Neko_editLink(),
                "type=neko&action=sort_links" => $this->Neko_sortLinks(),
                "type=neko&action=edit_sort_list" => $this->Neko_editSortList(),

                "type=ohmyga" => (function () {
                    Libs::getInstance()->sendJson(false, 500, "啊嘞？你访问这个链接干嘛 (´･ω･`)?");
                })(),

                    // 没有匹配到任何方法
                default => (function () {
                    Libs::getInstance()->sendJson(false, 404, "Not Found");
                })()
            };
        }
    }

    /**
     * NekoLinks API - 获取所有友链 (脱敏处理)
     *
     * @access private
     * @return void
     */
    private function NekoAPI_getAllLinks(): void
    {
        Libs::getInstance()->lockMethod('GET');

        $order = Libs::getInstance()->getParams("order", "true");
        if (!empty($order)) {
            $order = strtolower($order);
            match ($order) {
                "true" => $order = true,
                default => $order = false
            };
        } else {
            $order = true;
        }

        // 友链排序顺序
        $sort_by = Libs::getInstance()->getParams("sort_by", "ASC");
        if (!empty($sort_by)) {
            $sort_by = strtoupper($sort_by);
            match ($sort_by) {
                "ASC", "DESC" => $sort_by = $sort_by,
                default => $sort_by = "ASC"
            };
        } else {
            $sort_by = "ASC";
        }

        // 随机排序
        // 如果为 true 则 order 参数无效
        $random = Libs::getInstance()->getParams("random", "false");
        if (!empty($random)) {
            $random = strtolower($random);
            match ($random) {
                "true" => $random = true,
                default => $random = false
            };
        } else {
            $random = false;
        }

        $links = Libs::getInstance()->getLinks($order, $random);
        if ($random) shuffle($links);

        $links = array_map(function($link) {
            return [
                "name"         => $link["name"],
                "avatar"       => $link["avatar"],
                "url"          => $link["url"],
                "description"  => $link["description"],
                "sort"         => $link["sort"],
                "data"         => $link["data"],
            ];
        }, $links);

        Libs::getInstance()->sendJson(false, 200, "Success", $links, ["count" => (int)count($links), "sorts" => Libs::getInstance()->getSorts()]);
    }

    /**
     * 插件管理页面初始化
     * 
     * @access private
     * @return void
     */
    private function Neko_init(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('GET');

        // 获取所有链接
        $links = Libs::getInstance()->getLinks(true);
        // 获取所有分类
        $sorts = Libs::getInstance()->getSorts();
        // 获取所有设置
        $settings = Libs::getInstance()->getOptions();

        Libs::getInstance()->sendJson(true, 200, "Success", [
            "links"    => $links,
            "sorts"    => $sorts,
            "settings" => $settings,
            "count"    => [
                "links" => count($links),
                "sorts" => count($sorts),
            ]
        ]);
    }

    /**
     * 将 Links 插件的数据迁移到 NekoLinks
     * 
     * @access private
     * @return void
     */
    private function Neko_migrate(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('GET');

        // 判断是否有 Links 插件创建的表
        if (!Libs::getInstance()->checkTable("links")) {
            Libs::getInstance()->sendJson(false, 404, "未找到 Links 插件的数据表，无法迁移");
        }

        $links = Libs::getInstance()->db->fetchAll(
            Libs::getInstance()->db
                ->select()
                ->from('table.links')
                ->order('table.links.order', Libs::getInstance()->db::SORT_ASC)
        );
        // 如果没有任何链接
        if (empty($links)) {
            Libs::getInstance()->sendJson(false, 404, "没有任何链接，无需迁移");
        }

        // 将 Links 插件的数据转换成 NekoLinks 的数据格式
        $links = array_map(function ($link) {
            return [
                "name"         => $link["name"],
                "url"          => $link["url"],
                "avatar"       => $link["image"],
                "sort"         => "default",
                "description"  => $link["description"],
                "mail"         => $link["mail"] ?? NULL,  // 修改版的 Links 插件或许有 mail 字段
                "data"         => $link["user"],
                "order"        => $link["order"],
            ];
        }, $links);

        // 将数据插入到 NekoLinks 的数据表
        $successAddCount = 0;
        foreach ($links as $link) {
            // 判断是否已经存在
            // 正则匹配 URL 防止重复
            preg_match("/^https?:\/\/([^\/]+)/", $link["url"], $matches);
            $exist = Libs::getInstance()->db->fetchRow(Libs::getInstance()->db->select()->from('table.nekolinks')->where('url LIKE ?', "%" . ($matches[1] ?? $link["url"]) . "%"));

            // 如果存在则跳过
            if (!empty($exist)) continue;

            $link["order"] = Libs::getInstance()->db->fetchObject(Libs::getInstance()->db->select(['MAX(order)' => 'maxOrder'])->from('table.nekolinks'))->maxOrder + 1;

            // 插入数据
            Libs::getInstance()->db->query(Libs::getInstance()->db->insert('table.nekolinks')->rows($link));
            $successAddCount++;
        }

        // 如果没有成功插入任何数据
        if ($successAddCount === 0) {
            Libs::getInstance()->sendJson(true, 200, "没有任何友链需要迁移");
        }

        file_put_contents(__DIR__ . "/cache/.migrate_links", date("Y-m-d H:i:s"));
        Libs::getInstance()->sendJson(true, 200, "迁移成功，共迁移 $successAddCount 条数据");
    }

    /**
     * 不再提醒迁移
     * 
     * @access private
     * @return void
     */
    private function Neko_migrateDismiss(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('GET');

        // 设置不再提醒
        $options = Libs::getInstance()->getOptions();
        $options["migrateDismiss"] = true;
        $pluginOptions = \Utils\Helper::options()->plugin('NekoLinks');
        $pluginOptionsArray = $pluginOptions->toArray();
        $pluginOptionsArray["options"] = json_encode($options);
        Libs::getInstance()->db->query(Libs::getInstance()->db->update('table.options')->rows(['value' => serialize($pluginOptionsArray)])->where('name = ?', 'plugin:NekoLinks'));

        Libs::getInstance()->sendJson(true, 200, "设置成功，在重新启用插件前将不再提示。");
    }

    /**
     * 修改设置
     * 
     * @access private
     * @return void
     */
    private function Neko_changeSettings(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('POST');

        $api = Libs::getInstance()->getParams("api", false);
        $api = ($api === true || $api === 'true') ? true : false;

        $deleteData = Libs::getInstance()->getParams("deleteData", false);
        $deleteData = ($deleteData === true || $deleteData === 'true') ? true : false;

        $randomSort = Libs::getInstance()->getParams("randomSort", false);
        $randomSort = ($randomSort === true || $randomSort === 'true') ? true : false;

        $outputNumberLimit = Libs::getInstance()->getParams("outputNumberLimit", false);
        $outputNumberLimit = ($outputNumberLimit === true || $outputNumberLimit === 'true') ? true : false;

        $outputNumber = Libs::getInstance()->getParams("outputNumber", 0);
        $outputNumber = intval($outputNumber);

        $pluginOptions = \Utils\Helper::options()->plugin('NekoLinks');
        $options = json_decode($pluginOptions->options, true);
        $options["api"] = $api;
        $options["deleteData"] = $deleteData;
        $options["randomSort"] = $randomSort;
        $options["outputNumberLimit"] = $outputNumberLimit;
        $options["outputNumber"] = $outputNumber;

        // 获取原有插件配置的数组
        $pluginOptionsArray = $pluginOptions->toArray();
        $pluginOptionsArray["options"] = json_encode($options);

        // 更新设置
        Libs::getInstance()->db->query(Libs::getInstance()->db->update('table.options')->rows(['value' => serialize($pluginOptionsArray)])->where('name = ?', 'plugin:NekoLinks'));

        Libs::getInstance()->sendJson(true, 200, "修改成功", $options);
    }

    /**
     * 添加友链
     * 
     * @access private
     * @return void
     */
    private function Neko_addLink(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('POST');

        $name = Libs::getInstance()->getParams("name", "");
        $url = Libs::getInstance()->getParams("url", "");
        $avatar = Libs::getInstance()->getParams("avatar", "");
        $description = Libs::getInstance()->getParams("description", "");
        $mail = Libs::getInstance()->getParams("mail", "");
        $data = Libs::getInstance()->getParams("data", "");
        $sort = Libs::getInstance()->getParams("sort", "default");

        if (empty($name) || empty($url)) {
            $error = [];
            if (empty($name)) $error[] = "名称不能为空";
            if (empty($url)) $error[] = "链接不能为空";
            Libs::getInstance()->sendJson(false, 400, "参数错误", $error);
        }

        // 判断链接格式是否正确
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Libs::getInstance()->sendJson(false, 400, "参数错误", ["链接格式错误"]);
        }

        // 判断是否已经存在
        // 正则匹配 URL 防止重复
        preg_match("/^https?:\/\/([^\/]+)/", $url, $matches);
        $exist = Libs::getInstance()->db->fetchRow(Libs::getInstance()->db->select()->from('table.nekolinks')->where('url LIKE ?', "%" . ($matches[1] ?? $url) . "%"));
        // 如果存在则返回错误
        if (!empty($exist)) {
            Libs::getInstance()->sendJson(false, 400, "参数错误", ["链接已存在"]);
        }

        $message = "";
        $hasSort = false;
        if (!empty($sort)) {
            // 获取所有分类
            $sorts = Libs::getInstance()->getSorts();
            // 判断分类是否存在
            foreach ($sorts as $value) {
                if ($value["id"] === $sort) {
                    $hasSort = true;
                    break;
                }
            }
            if (!$hasSort) {
                $message = "，但分类不存在。";
            }
        }

        // 计算 order
        $order = Libs::getInstance()->db->fetchObject(Libs::getInstance()->db->select(['MAX(order)' => 'maxOrder'])->from('table.nekolinks'))->maxOrder;

        // 插入数据
        $data = [
            "name"        => $name,
            "url"         => $url,
            "avatar"      => !empty($avatar) ? $avatar : null,
            "description" => !empty($description) ? $description : null,
            "mail"        => !empty($mail) ? $mail : null,
            "data"        => !empty($data) ? $data : null,
            "sort"        => !empty($sort) && $hasSort ? $sort : "default",
            "order"       => $order + 1,
        ];
        Libs::getInstance()->db->query(Libs::getInstance()->db->insert('table.nekolinks')->rows($data));

        // 根据 name 和 url 获取 ID
        $data["id"] = Libs::getInstance()->db->fetchObject(Libs::getInstance()->db->select()->from('table.nekolinks')->where('name = ? AND url = ?', $name, $url))->id;

        Libs::getInstance()->sendJson(true, 200, "添加成功" . ($message ?? "") . $message, $data);
    }

    /**
     * 删除单个友链
     * 
     * @access private
     * @return void
     */
    private function Neko_deleteLink(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('DELETE');

        $id = Libs::getInstance()->getParams("id", "");
        if (empty($id)) {
            Libs::getInstance()->sendJson(false, 400, "待删除的友链 ID 不能为空");
        }

        // 判断是否存在
        $exist = Libs::getInstance()->db->fetchRow(Libs::getInstance()->db->select()->from('table.nekolinks')->where('id = ?', $id));
        if (empty($exist)) {
            Libs::getInstance()->sendJson(false, 400, "友链不存在");
        }

        // 删除数据
        Libs::getInstance()->db->query(Libs::getInstance()->db->delete('table.nekolinks')->where('id = ?', $id));

        Libs::getInstance()->sendJson(true, 200, "删除成功", $exist);
    }

    /**
     * 删除多个友链
     * 
     * @access private
     * @return void
     */
    private function Neko_deleteLinks(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('DELETE');

        $ids = Libs::getInstance()->getParams("ids", "");
        if (empty($ids)) {
            Libs::getInstance()->sendJson(false, 400, "待删除的友链 IDs 不能为空");
        }

        $ids = explode(",", $ids);

        // 不存在的 ID 暂存数组
        $notExistIds = [];
        // 存在的 ID 暂存数组
        $existIds = [];

        // 逐个查询并删除
        foreach ($ids as $id) {
            $link = Libs::getInstance()->db->fetchRow(Libs::getInstance()->db->select()->from('table.nekolinks')->where('id = ?', $id));
            if (empty($link)) $notExistIds[] = $id;
            else {
                $existIds[] = $id;
                Libs::getInstance()->db->query(Libs::getInstance()->db->delete('table.nekolinks')->where('id = ?', $id));
            }
        }

        Libs::getInstance()->sendJson(true, 200, "删除成功", ["notExistIds" => $notExistIds, "existIds" => $existIds]);
    }

    /**
     * 编辑友链
     * 
     * @access private
     * @return void
     */
    private function Neko_editLink(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('POST');

        $id = Libs::getInstance()->getParams("id", "");
        $name = Libs::getInstance()->getParams("name", "");
        $url = Libs::getInstance()->getParams("url", "");
        $avatar = Libs::getInstance()->getParams("avatar", "");
        $description = Libs::getInstance()->getParams("description", "");
        $mail = Libs::getInstance()->getParams("mail", "");
        $data = Libs::getInstance()->getParams("data", "");
        $sort = Libs::getInstance()->getParams("sort", "default");

        // 如果 ID 为空则返回错误
        if (empty($id)) {
            Libs::getInstance()->sendJson(false, 400, "ID 不能为空");
        }

        // 根据 ID 获取友链
        $link = Libs::getInstance()->db->fetchRow(Libs::getInstance()->db->select()->from('table.nekolinks')->where('id = ?', $id));
        // 如果不存在则返回错误
        if (empty($link)) {
            Libs::getInstance()->sendJson(false, 404, "友链不存在");
        }

        // 如果传入的 URL 不为空则进行格式检查
        if (!empty($url)) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                Libs::getInstance()->sendJson(false, 400, "链接格式错误");
            }
        }

        // 将数据库中的数据与传入数组都转换为小写，并进行比对，有变动则更新。
        // 如果传入参数为空则不对此字段进行更新
        $data = [
            "name"        => !empty($name) && strtolower($name) !== strtolower($link["name"] ?? "") ? $name : $link["name"],
            "url"         => !empty($url) && strtolower($url) !== strtolower($link["url"] ?? "") ? $url : $link["url"],
            "avatar"      => empty($avatar) ? NULL : $avatar,
            "description" => empty($description) ? NULL : $description,
            "mail"        => empty($mail) ? NULL : $mail,
            "data"        => empty($data) ? NULL : $data,
            "sort"        => !empty($name) && strtolower($sort) !== strtolower($link["sort"] ?? "") ? $sort : $link["sort"],
        ];
        Libs::getInstance()->db->query(Libs::getInstance()->db->update('table.nekolinks')->rows($data)->where('id = ?', $id));

        Libs::getInstance()->sendJson(true, 200, "友链信息更新成功", $data);
    }

    /**
     * 友链排序
     * 
     * @access private
     * @return void
     */
    private function Neko_sortLinks(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('POST');

        $ids = Libs::getInstance()->getParams("ids", "");
        // 如果 ID 为空则返回错误
        if (empty($ids)) {
            Libs::getInstance()->sendJson(false, 400, "IDs 不能为空");
        }

        // 将 ID 转换为数组
        $ids = explode(",", $ids);

        // 友链根据 order 重新排序
        foreach ($ids as $order => $id) {
            Libs::getInstance()->db->query(Libs::getInstance()->db->update('table.nekolinks')->rows(["order" => $order + 1])->where('id = ?', $id));
        }

        // 重新获取友链
        $links = Libs::getInstance()->getLinks(true, true);

        Libs::getInstance()->sendJson(true, 200, "排序成功", $links);
    }

    /**
     * 更新/编辑分类
     * 
     * @access private
     * @return void
     */
    private function Neko_editSortList(): void
    {
        if (!Libs::getInstance()->checkLogin()) {
            Libs::getInstance()->sendJson(false, 403, "Forbidden");
        }
        Libs::getInstance()->lockMethod('POST');

        $sorts = Libs::getInstance()->getParams("sorts", "");
        $deleteList = Libs::getInstance()->getParams("deleteList", "");
        if (empty($sorts)) {
            Libs::getInstance()->sendJson(false, 400, "分类列表不能为空");
        }

        // 将分类转换为数组
        $sortsArr = is_array($sorts) ? $sorts : json_decode($sorts, true);
        if (!is_array($sortsArr) || empty($sortsArr)) {
            Libs::getInstance()->sendJson(false, 400, "分类列表格式错误");
        }

        // 获取分类列表
        $sortsList = Libs::getInstance()->getSorts();

        foreach ($sorts as $sort) {
            $has = false;

            foreach ($sortsList as $originalSortId => $originalSort) {
                if (strtolower($originalSort["id"]) === strtolower($sort["id"])) {
                    $sortsList[$originalSortId] = [
                        "id"   => $sort["id"],
                        "name" => $sort["name"]
                    ];
                    $has = true;
                    break;
                }
            }

            if (!$has) {
                $sortsList[] = [
                    "id"   => $sort["id"],
                    "name" => $sort["name"]
                ];
            }
        }

        $deleteList = is_array($deleteList) ? $deleteList : (!empty($deleteList) ? json_decode($deleteList, true) : "");
        if (!empty($deleteList)) {
            foreach ($deleteList as $deleteId) {
                foreach ($sortsList as $sortKey => $sortItem) {
                    if (strtolower($deleteId) === strtolower($sortItem["id"])) {
                        unset($sortsList[$sortKey]);
                        break;
                    }
                }
            }
        }

        $pluginOptions = \Utils\Helper::options()->plugin('NekoLinks');
        $pluginOptionsArray = $pluginOptions->toArray();
        $pluginOptionsArray["sortList"] = json_encode($sortsList);

        // 更新分类列表
        Libs::getInstance()->db->query(Libs::getInstance()->db->update('table.options')->rows(['value' => serialize($pluginOptionsArray)])->where('name = ?', 'plugin:NekoLinks'));

        Libs::getInstance()->sendJson(true, 200, "分类列表更新成功", $sortsList);
    }
}
