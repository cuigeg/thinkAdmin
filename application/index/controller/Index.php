<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\index\controller;

use library\Controller;

/**
 * 应用入口
 * Class Index
 * @package app\index\controller
 */
class Index extends Controller
{
    /**
     * 入口跳转链接
     */
    public function index()
    {
        $this->redirect('@admin/login');
    }

    /**
     * 服务器url验证
     */
    public function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $echostr = $_GET['echostr'];
        $token = 'cuibenlingzuiniubi';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);//将token、timestamp、nonce三个参数由小到大进行排序
        $tmpStr = implode($tmpArr);//将三个参数字符串拼接成一个字符串
        $tmpStr = sha1($tmpStr);//进行sha1加密
        if ($tmpStr == $signature) {
            exit($echostr);//返回echostr参数
        }

    }
}
