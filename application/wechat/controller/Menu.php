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

namespace app\wechat\controller;

use app\wechat\service\WechatService;
use library\Controller;
use think\Db;
use think\exception\HttpResponseException;

/**
 * 微信菜单管理
 * Class Menu
 * @package app\wechat\controller
 */
class Menu extends Controller
{
    /**
     * 微信菜单的类型
     * @var array
     */
    protected $menuType = [
        'click'              => '匹配规则',
        'view'               => '跳转网页',
        'miniprogram'        => '打开小程序',
        // 'customservice'      => '转多客服',
        'scancode_push'      => '扫码推事件',
        'scancode_waitmsg'   => '扫码推事件且弹出“消息接收中”提示框',
        'pic_sysphoto'       => '弹出系统拍照发图',
        'pic_photo_or_album' => '弹出拍照或者相册发图',
        'pic_weixin'         => '弹出微信相册发图器',
        'location_select'    => '弹出地理位置选择器',
    ];

    /**
     * 微信菜单管理
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        if ($this->request->get('output') === 'json') {
            $where = [['keys', 'notin', ['subscribe', 'default']], ['status', 'eq', '1']];
            $keys = Db::name('WechatKeys')->where($where)->order('sort desc,id desc')->select();
            $this->success('获取数据成功!', ['menudata' => sysdata('menudata'), 'keysdata' => $keys]);
        } else {
            $this->title = '微信菜单定制';
            $this->menuTypes = $this->menuType;
            $this->fetch();
        }
    }

    /**
     * 编辑微信菜单
     * @auth true
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('data');
            if (empty($data)) { // 删除菜单
                try {
                    WechatService::WeChatMenu()->delete();
                    sysoplog('微信管理', '删除微信菜单成功');
                    $this->success('删除微信菜单成功！', '');
                } catch (HttpResponseException $exception) {
                    throw $exception;
                } catch (\Exception $e) {
                    sysoplog('微信管理', "删除微信菜单失败:{$e->getMessage()}");
                    $this->error('删除微信菜单失败，请稍候再试！' . $e->getMessage());
                }
            } else {
                try {
                    sysdata('menudata', $this->_buildMenuData($menudata = json_decode($data, true)));
                    WechatService::WeChatMenu()->create(['button' => sysdata('menudata')]);
                    sysoplog('微信管理', '发布微信菜单成功');
                    $this->success('保存发布菜单成功！', '');
                } catch (HttpResponseException $exception) {
                    throw $exception;
                } catch (\Exception $e) {
                    sysoplog('微信管理', "发布微信菜单失败:{$e->getMessage()}");
                    $this->error("微信菜单发布失败，请稍候再试！<br> {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * 菜单数据处理
     * @param array $list
     * @return array
     */
    private function _buildMenuData(array $list)
    {
        foreach ($list as &$vo) {
            unset($vo['active'], $vo['show']);
            if (empty($vo['sub_button'])) {
                $vo = $this->_buildMenuItemData($vo);
            } else {
                $item = ['name' => $vo['name'], 'sub_button' => []];
                foreach ($vo['sub_button'] as &$sub) {
                    unset($sub['active'], $sub['show']);
                    array_push($item['sub_button'], $this->_buildMenuItemData($sub));
                }
                $vo = $item;
            }
        }
        return $list;
    }

    /**
     * 单个微信菜单数据处理
     * @param array $item
     * @return array
     */
    private function _buildMenuItemData(array $item)
    {
        switch (strtolower($item['type'])) {
            case 'pic_weixin':
            case 'pic_sysphoto':
            case 'scancode_push':
            case 'location_select':
            case 'scancode_waitmsg':
            case 'pic_photo_or_album':
                return ['name' => $item['name'], 'type' => $item['type'], 'key' => isset($item['key']) ? $item['key'] : $item['type']];
            case 'click':
                if (empty($item['key'])) $this->error('匹配规则存在空的选项');
                return ['name' => $item['name'], 'type' => $item['type'], 'key' => $item['key']];
            case 'view':
                return ['name' => $item['name'], 'type' => $item['type'], 'url' => $item['url']];
            case 'miniprogram':
                return ['name' => $item['name'], 'type' => $item['type'], 'url' => $item['url'], 'appid' => $item['appid'], 'pagepath' => $item['pagepath']];
        }
    }

    /**
     * 取消微信菜单
     * @auth true
     */
    public function cancel()
    {
        try {
            WechatService::WeChatMenu()->delete();
            $this->success('菜单取消成功，重新关注可立即生效！', '');
        } catch (HttpResponseException $exception) {
            sysoplog('微信管理', '取消微信菜单成功');
            throw $exception;
        } catch (\Exception $e) {
            $this->error("菜单取消失败，请稍候再试！<br> {$e->getMessage()}");
        }
    }

}
