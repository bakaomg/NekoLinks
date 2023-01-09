<?php
/**
 * 插件常用类
 * 
 * @Author: ohmyga
 * @Date: 2022-08-23 15:24:15
 * @LastEditTime: 2023-01-10 07:58:35
 */

namespace TypechoPlugin\NekoLinks\core;

use Typecho\Db;
use Utils\Helper;
use Typecho\Request;
use Typecho\Response;
use Widget\User;

use function md5;
use function defined;
use function strtolower;
use function in_array, array_merge;
use function json_encode, json_decode;
use function file_get_contents;

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
     * @access public
     * @var DB
     */
    public DB $db;

    /**
     * Response
     * 
     * @access public
     * @var Response
     */
    public Response $response;

    /**
     * Request
     * 
     * @access public
     * @var Request
     */
    public Request $request;

    /**
     * 用户实例
     * 
     * @access public
     * @var User
     */
    public User $user;

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
        $this->response = Response::getInstance();
        $this->request = Request::getInstance();
        $this->user = User::alloc();
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

    /**
     * 判断是否登录且有权限
     * 
     * @access public
     * @param string|null $permission   权限
     * @return bool
     */
    public function checkLogin(?string $permission = null): bool
    {
        return $this->user->hasLogin() && $this->user->pass($permission ?? 'administrator', true);
    }

    /**
     * 锁定请求方式
     * 
     * @access public
     * @param string|array $method    请求方式
     * @return void
     */
    public function lockMethod(string|array $method): void
    {
        $message = function () {
            $this->sendJson(false, 405, 'Method not allowed');
            exit;
        };

        // 如果是数组
        if (is_array($method)) {
            $_methods = [];
            foreach ($method as $val) $_methods[] = strtolower($val);

            if (!in_array(strtolower($this->request->getServer('REQUEST_METHOD')), $_methods)) $message();
        } else {
            // 如果是字符串
            if (strtolower($this->request->getServer('REQUEST_METHOD')) != strtolower($method)) $message();
        }
    }

    /**
     * 获取所有 GET 参数并用 & 组合
     * 
     * @access public
     * @return string
     */
    public function getQueryParams(): string
    {
        $params = '';
        if (empty($_GET)) return $params;
        foreach ($_GET as $key => $val) {
            $params .= '&' . $key . '=' . $val;
        }

        return strtolower(trim($params, '&'));
    }

    /**
     * 获取请求参数
     *
     * @access public
     * @param string $key              参数的键名
     * @param string|null $default     默认值
     * @return mixed
     */
    public function getParams(string $key, ?string $default = null): mixed
    {
        // 如果请求方式为 GET
        if ($this->request->isGet()) {
            return $this->request->get($key, $default);
        }

        // 如果请求方式为 POST
        if ($this->request->isPost() || $this->request->isPut() || 'DELETE' == $this->request->getServer('REQUEST_METHOD')) {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data[$key])) return $data[$key];
            if (isset($_GET[$key])) return $_GET[$key];
            if (!empty($_POST[$key])) return $_POST[$key];
            return $default;
        } else if (!empty($_POST[$key])) {
            return $_POST[$key];
        }

        if (!empty($_GET)) {
            return (isset($_GET[$key])) ? $_GET[$key] : $default;
        }

        // 如果啥都没有就输出默认值
        return $default;
    }

    /**
     * 以 JSON 格式输出数据
     * 
     * @access public
     * @param bool $status      当前状态
     * @param int $code         返回的 HTTP 状态码
     * @param string $message   返回的消息
     * @param array $data       返回的数据
     * @param array $more       更多数据
     * @return void
     */
    public function sendJson(bool $status, int $code, string $message, array $data = [], array $more = []): void
    {
        // 设置 HTTP 状态码
        $this->response->setStatus($code);

        // 设置返回内容类型
        $this->response->setContentType('application/json');

        // 设置返回字符集
        $this->response->setCharset('UTF-8');

        // 预定义返回数据
        $result = [
            'status'     => $status,
            'code'       => $code,
            'message'    => $message,
            'data'       => $data,
        ];

        // 判断是否有更多数据
        // 如果有则合并
        if (!empty($more)) {
            $result = array_merge($result, $more);
        }

        // 打印输出 JSON 格式数据
        echo json_encode($result, JSON_UNESCAPED_UNICODE);

        // 终止后续内容输出
        exit;
    }

    /**
     * 获取所有友链
     * 
     * @param bool $order                根据排序字段排序
     * @param bool $asc                  升序 (true) 或降序 (false)
     * @param bool $returnDefaultAvatar  是否返回默认头像
     * @access public
     * @return array
     */
    public function getLinks(bool $order = false, bool $asc = true, $returnDefaultAvatar = false): array
    {
        $query = $this->db->select()->from('table.nekolinks');
        if ($order) {
            $query->order('table.nekolinks.order', $asc ? Db::SORT_ASC : Db::SORT_DESC);
        }
        $result = $this->db->fetchAll($query);

        $links = [];

        $gravatar = defined('__TYPECHO_GRAVATAR_PREFIX__') ? \__TYPECHO_GRAVATAR_PREFIX__ : 'https://gravatar.loli.net/avatar/';
        foreach ($result as $row) {
            $links[] = [
                "id"          => $row["id"],
                "name"        => $row["name"],
                "url"         => $row["url"],
                "avatar"      => $row["avatar"] ?? ($returnDefaultAvatar ? (
                    !empty($row["mail"]) ? $gravatar . md5($row["mail"]) . "?s=100" : Helper::options()->pluginUrl . "/NekoLinks/assets/no_avatar.jpg"
                ) : NULL),
                "description" => $row["description"],
                "mail"        => $row["mail"],
                "sort"        => $row["sort"],
                "data"        => $row["data"],
                "order"       => $row["order"],
            ];
        }

        return $links;
    }

    /**
     * 获取所有分类
     * 
     * @access public
     * @return array
     */
    public function getSorts(): array
    {
        $options = Helper::options()->plugin('NekoLinks');

        if (empty($options->sortList) || empty(json_decode($options->sortList, true))) {
            return [];
        }

        return json_decode($options->sortList, true);
    }

    /**
     * 获取插件配置
     * 
     * @access public
     * @return array
     */
    public function getOptions(): array
    {
        $options = Helper::options()->plugin('NekoLinks');

        if (empty($options->options) || empty(json_decode($options->options, true))) {
            return [
                "api"                 => false,
                "deleteData"          => false,
                "randomSort"          => false,
                "outputNumberLimit"   => false,
                "outputNumber"        => 0,
                "migrateDismiss"      => false,
            ];
        }

        return json_decode($options->options, true);
    }
}
