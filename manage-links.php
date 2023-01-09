<?php
/**
 * 友链管理页面
 * 
 * @Author: ohmyga
 * @Date: 2022-08-21 23:00:20
 * @LastEditTime: 2023-01-10 06:07:34
 */
use Utils\Helper;
use Widget\User;
use Widget\Menu;
use ReflectionClass;
use Typecho\Widget\Request as widgetRequest;
use Typecho\Widget\Response as widgetResponse;
use TypechoPlugin\NekoLinks\core\Libs;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function canMigrate(): string
{
    $options = Libs::getInstance()->getOptions();
    if ($options["migrateDismiss"] === true) return "false";

    if (file_exists(__DIR__ . "/cache/.migrate_links")) return "false";

    return "true";
}

function getMenu(): array
{
    $class = new ReflectionClass(Menu::class);
    $property = $class->getProperty("menu");
    $property->setAccessible(true);
    $menu = new Menu(
        new widgetRequest(Libs::getInstance()->request),
        new widgetResponse(Libs::getInstance()->request, Libs::getInstance()->response)
    );
    $menu->execute();
    $menuList = $property->getValue($menu);

    $result = [];
    $adminPath = defined('__TYPECHO_ADMIN_DIR__') ? rtrim(__TYPECHO_ADMIN_DIR__, "/") : '/admin';
    foreach ($menuList as $key => $item) {
        if ($key === 0) continue;

        $_menu = [
            "text"      => $item[0],
            "children"  => []
        ];

        foreach ($item[3] as $childKey => $childItem) {
            if (!is_string($childItem[0])) continue;
            if ($childItem[6] === true) continue;

            $_menu["children"][] = [
                "text" => $childItem[0],
                "path" => str_replace(Helper::options()->siteUrl . (ltrim($adminPath, "/")), "", $childItem[2])
            ];
        }

        $result[] = $_menu;
    }

    return $result;
}
?>
<html lang="zh">
<!--
+----------------------------------------------
|  _   _      _         _     _       _        
| | \ | |    | |       | |   (_)     | |       
| |  \| | ___| |_ _   _| |__  _ _ __ | |_ ___
| | . ` |/ _ \ __| | | | '_ \| | '_ \| __/ __|
| | |\  |  __/ |_| |_| | |_) | | | | | |_\__ \
| |_| \_|\___|\__|\__,_|_.__/|_|_| |_|\__|___/
+----------------------------------------------
| Author: ohmyga
| GitHub: https://github.com/bakaomg/NekoLinks
| Build Date: 2023-01-10 05:57:03
+----------------------------------------------
-->
<head>
    <meta charset="UTF-8" />
    <title><?php _e('%s - %s - Powered by Typecho', $menu->title, $options->title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?php echo Helper::options()->pluginUrl ?>/NekoLinks/assets/favicon.ico" />
    <link rel="shortcut icon" href="<?php echo Helper::options()->pluginUrl ?>/NekoLinks/assets/favicon.ico" />
    <style type="text/css">body{margin:0}#NekoLinks-Error,#NekoLinks-Loading{position:fixed;top:0;left:0;width:100%;height:100%;z-index:2000;background-color:#fff;transition:all .3s}#NekoLinks-Error{z-index:2010}#NekoLinks-Error.hidden,#NekoLinks-Loading.loaded{opacity:0;pointer-events:none}.container{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)}#NekoLinks-Error .container{display:flex;justify-content:center;align-items:center;flex-direction:column}.container .close,.container .sugar{width:100px;height:100px;animation:wave 2s linear infinite}.container .close{animation:rotate 5s linear infinite}.container .sugar svg path{fill:#ff4081}.container .close svg path{fill:#f44336}#NekoLinks-Error .container .text{font-size:20px;color:#f44336;margin-top:20px;font-weight:700;text-align:center}@keyframes wave{0%{transform:translate(0,0)}50%{transform:translate(0,10px)}100%{transform:translate(0,0)}}@keyframes rotate{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}</style>
    <script type="module" crossorigin src="<?php echo Helper::options()->pluginUrl ?>/NekoLinks/assets/index-bc35f9fa.js"></script>
    <link rel="stylesheet" href="<?php echo Helper::options()->pluginUrl ?>/NekoLinks/assets/index-841015e6.css">
</head>

<body>
    <script type="text/javascript">
        const NekoLinksData = {
            assetsUrl: "<?php echo Helper::options()->pluginUrl ?>/NekoLinks/assets",
            actionUrl: "<?php echo Helper::options()->siteUrl ?><?php Helper::options()->rewrite ? "index.php/" : "" ?>action/nekolinks",
            adminRootPath: "<?php echo defined('__TYPECHO_ADMIN_DIR__') ? rtrim(__TYPECHO_ADMIN_DIR__, "/") : '/admin' ?>",
            //siteTitle: "<?php echo Helper::options()->title ?>", // 显示网站标题好像怪怪的？
            siteTitle: "NekoLinks",
            footerSiteTitle: "<?php echo Helper::options()->title ?>",
            withCredentials: true,
            canMigrate: <?php echo canMigrate(); ?>,
            navMenu: <?php echo json_encode(getMenu()); ?>,
            profile: {
                nav: [{
                        text: "个人设置",
                        path: "/profile.php"
                    },
                    {
                        text: "退出登录",
                        callback: () => {
                            window.location.href = "<?php Helper::options()->logoutUrl(); ?>";
                        }
                    }
                ],
                avatar: "<?php echo (empty(User::alloc()->mail) ? "" : "https://gravatar.loli.net/avatar/" . md5(User::alloc()->mail) . "?s=100"); ?>",
                nickname: "<?php echo (empty(User::alloc()->screenName) ? User::alloc()->name : User::alloc()->screenName); ?>",
            }
        };
    </script>
    <div id="NekoLinks-Loading"><div class="container"><div class="sugar"><svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" data-v-029747aa=""><path d="m801.728 349.184 4.48 4.48a128 128 0 0 1 0 180.992L534.656 806.144a128 128 0 0 1-181.056 0l-4.48-4.48-19.392 109.696a64 64 0 0 1-108.288 34.176L78.464 802.56a64 64 0 0 1 34.176-108.288l109.76-19.328-4.544-4.544a128 128 0 0 1 0-181.056l271.488-271.488a128 128 0 0 1 181.056 0l4.48 4.48 19.392-109.504a64 64 0 0 1 108.352-34.048l142.592 143.04a64 64 0 0 1-34.24 108.16l-109.248 19.2zm-548.8 198.72h447.168v2.24l60.8-60.8a63.808 63.808 0 0 0 18.752-44.416h-426.88l-89.664 89.728a64.064 64.064 0 0 0-10.24 13.248zm0 64c2.752 4.736 6.144 9.152 10.176 13.248l135.744 135.744a64 64 0 0 0 90.496 0L638.4 611.904H252.928zm490.048-230.976L625.152 263.104a64 64 0 0 0-90.496 0L416.768 380.928h326.208zM123.712 757.312l142.976 142.976 24.32-137.6a25.6 25.6 0 0 0-29.696-29.632l-137.6 24.256zm633.6-633.344-24.32 137.472a25.6 25.6 0 0 0 29.632 29.632l137.28-24.064-142.656-143.04z"></path></svg></div></div></div>
    <div id="NekoLinks-Error" class="hidden"><div class="container"><div class="close"><svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" data-v-029747aa=""><path fill="currentColor" d="M195.2 195.2a64 64 0 0 1 90.496 0L512 421.504 738.304 195.2a64 64 0 0 1 90.496 90.496L602.496 512 828.8 738.304a64 64 0 0 1-90.496 90.496L512 602.496 285.696 828.8a64 64 0 0 1-90.496-90.496L421.504 512 195.2 285.696a64 64 0 0 1 0-90.496z"></path></svg></div><div class="text">请求 API 失败，请尝试<a href="javascript:location.reload()">刷新</a>页面<br>错误：<span id="error-text">(´･ω･`)?</span></div></div></div>
    <div id="NekoLinks"></div>
</body>

</html>
