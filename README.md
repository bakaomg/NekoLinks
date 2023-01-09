<!--
 * @Author: ohmyga
 * @Date: 2023-01-10 06:22:58
 * @LastEditTime: 2023-01-10 07:47:51
-->
<p align="center">
   <img src="./assets/banner.png" />
</p>

---

> 一个 Typecho 友情链接插件

**适用于 Typecho 1.2.0+ / PHP8.0+**

## 开始使用

0. Star 本项目 ( •̀ ω •́ )✧
1. 在 [Releases](https://github.com/bakaomg/NekoLinks/releases) 页面下载最新发行包 或直接 Clone 本项目；
2. 将 NekoLinks 上传到 `/usr/plugins` 目录下（并解压），如果插件文件夹名称为 `NekoLinks-master` 则需改名为 `NekoLinks`；
3. 登录至 Typecho 控制台，到插件管理启用本插件，可以直接在插件管理页面点 NekoLinks 的设置或依次点击 `管理 → NekoLinks / 友链管理` 进入友链管理页面。

## FAQ

<details><summary>NekoLinks API</summary><br>

> **开始使用前需前往 NekoLinks 管理页面启用 NekoLinks API 选项**<br/>
> 目前 Public API 处于开发阶段，可能还会继续扩充或更改，**请勿用于生产环境**

---

### GET `/action/nekolinks-api/getall`

获取所有友链<br />

```json
{
    "status": true,
    "code": 200,
    "message": "Success",
    "data": [
        ...
        {
            "name": "NekoLinks",                                          // 友链名称
            "avatar": "https://gravatar.loli.net/avatar/defalut?s=100",   // 头像链接
            "url": "https://github.com/bakaomg/NekoLinks",                // 友链地址
            "description": "一个 Typecho 友情链接插件",                    // 友链介绍 (如果为空则值为 null)
            "sort": "baka",                                               // 友链分类 (默认 default)
            "data": null                                                  // 友链自定义数据 (如果为空则值为 null)
        },
        ...
    ],
}
```

Parameters:

- `order`(可选)：是否根据友链顺序进行排序 / 传入的值：`true`(默认) | `false`
- `sort_by`(可选)：友链排序顺序 / 传入的值：`ASC`(默认) | `DESC`
- `random`(可选)：是否打乱友链输出顺序(如果启用此项，则排序将无效) / 传入的值：`true` | `false`(默认)

---

</details>

<details><summary>从 Links 插件迁移数据</summary><br>

- 如果之前安装过 Links 插件，且数据库中还有 `typecho_links` 表的话，在进入 NekoLinks 管理页面时会提示数据迁移。
- 如果点击 `不再提醒` 按钮，则在重新启用插件前不会再次提醒数据迁移。
- 如果点击 `迁移` 按钮，则会从 Links 插件迁移数据，并且会在 `/usr/plugins/NekoLinks/cache` 文件夹下创建名为 `.migrate_links` 的文件，如果不手动删除，则不会再次提醒数据迁移。

</details>

<details><summary>在其他 <code>插件/主题</code> 中 <code>调用/输出</code> 友链</summary><br>

### 输出受插件设置限制的友链数组
- `\TypechoPlugin\NekoLinks\Plugin::linksArray(bool, bool, bool): array`
```php
<?php
/**
 * @param bool $order                根据排序字段排序 (如果插件设置中的 randomSort 为 true 则无效)
 * @param bool $asc                  升序 (true) 或降序 (false) (如果插件设置中的 randomSort 为 true 则无效)
 * @param bool $returnDefaultAvatar  当没有头像时是否返回默认头像
 * @return array
 */
\TypechoPlugin\NekoLinks\Plugin::linksArray(
    bool $order = false,
    bool $asc = true,
    $returnDefaultAvatar = false
): array;

// 使用例
print_r(\TypechoPlugin\NekoLinks\Plugin::linksArray(true, true, true));
```

---

### 输出不受配置项影响的友链数组
- `TypechoPlugin\NekoLinks\Plugin::rawLinksArray(bool, bool, bool): array`
```php
<?php
/**
 * @param bool $order                根据排序字段排序
 * @param bool $asc                  升序 (true) 或降序 (false)
 * @param bool $returnDefaultAvatar  当没有头像时是否返回默认头像
 * @return array
 */
\TypechoPlugin\NekoLinks\Plugin::rawLinksArray(
    bool $order = false,
    bool $asc = true,
    $returnDefaultAvatar = false
): array;

// 使用例
print_r(\TypechoPlugin\NekoLinks\Plugin::rawLinksArray(true, true, true));
```

---

### 输出 HTML 结构的友链
- `TypechoPlugin\NekoLinks\Plugin::linksOutput(string, bool, bool, bool): string`
```php
<?php
/**
 * @param string $template  模板
 * @param bool $order       根据排序字段排序 (如果插件设置中的 randomSort 为 true 则无效)
 * @param bool $asc         升序 (true) 或降序 (false) (如果插件设置中的 randomSort 为 true 则无效)
 * @param bool $echo        是否直接输出
 * @return string
 */
\TypechoPlugin\NekoLinks\Plugin::linksOutput(
    string $template = '',
    bool $order = false,
    bool $asc = true,
    bool $echo = true
): string;

// 使用例
// 使用默认模板输出
?>
<div id="links">
  <?php \TypechoPlugin\NekoLinks\Plugin::linksOutput(); ?>
</div>
```
自定义模板可用变量
- `{url}`: 友链链接
- `{name}`: 友链名称
- `{description}`: 友链介绍
- `{avatar}`: 友链头像链接
- `{mail}`: 友链邮箱
- `{sort}`: 友链分类
- `{data}`: 友链自定义数据
- `{order}`: 友链排序

</details>

## 其他
- 本插件仅支持 **Typecho 1.2.0+** 和 **PHP8.0+** (不支持 PHP7.x)
- 本插件开发时部分参考了 [Links](https://www.imhan.com/archives/typecho-links/) 插件
- 本插件的管理页面使用 `Vite`+`Vue3`+`Element Plus` 开发，因为打包后还有许多需要修改的地方，所以暂时不一并开源

## 版权信息
Copyright &copy; [ohmyga](https://github.com/bakaomg), under GPL-3.0 License
