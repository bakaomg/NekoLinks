<?php

namespace TypechoPlugin\NekoLinks;

use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Action
 * 
 * @Author: ohmyga
 * @Date: 2022-08-23 16:26:11
 * @LastEditTime: 2022-08-23 16:32:32
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
    }

    /**
     * Action
     * 
     * @return void
     */
    public function action(): void
    {
    }
}
