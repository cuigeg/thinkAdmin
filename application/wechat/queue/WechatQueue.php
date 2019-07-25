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

namespace app\wechat\queue;

use app\admin\queue\JobsQueue;
use app\wechat\service\FansService;
use app\wechat\service\WechatService;
use think\Db;

/**
 * Class Jobs
 * @package app\wechat
 */
class WechatQueue extends JobsQueue
{
    /**
     * 当前任务URI
     */
    const URI = self::class;

    /**
     * 执行任务
     * @return boolean
     */
    public function execute()
    {
        try {
            $appid = WechatService::getAppid();
            $wechat = WechatService::WeChatUser();
            $next = ''; // 获取远程粉丝
            $this->output->writeln('Start synchronizing fans from the Wechat server');
            while (is_array($result = $wechat->getUserList($next)) && !empty($result['data']['openid'])) {
                foreach (array_chunk($result['data']['openid'], 100) as $chunk)
                    if (is_array($list = $wechat->getBatchUserInfo($chunk)) && !empty($list['user_info_list']))
                        foreach ($list['user_info_list'] as $user) FansService::set($user, $appid);
                if (in_array($result['next_openid'], $result['data']['openid'])) break;
                $next = $result['next_openid'];
            }
            $next = ''; // 同步粉丝黑名单
            $this->output->writeln('Start synchronizing black from the Wechat server');
            while (is_array($result = $wechat->getBlackList($next)) && !empty($result['data']['openid'])) {
                foreach (array_chunk($result['data']['openid'], 100) as $chunk) {
                    $where = [['is_black', 'eq', '0'], ['openid', 'in', $chunk]];
                    Db::name('WechatFans')->where($where)->update(['is_black' => '1']);
                }
                if (in_array($result['next_openid'], $result['data']['openid'])) break;
                $next = $result['next_openid'];
            }
            // 同步粉丝标签列表
            $this->output->writeln('Start synchronizing tags from the Wechat server');
            if (is_array($list = WechatService::WeChatTags()->getTags()) && !empty($list['tags'])) {
                foreach ($list['tags'] as &$tag) $tag['appid'] = $appid;
                Db::name('WechatFansTags')->where('1=1')->delete();
                Db::name('WechatFansTags')->insertAll($list['tags']);
            }
            return true;
        } catch (\Exception $e) {
            $this->statusDesc = $e->getMessage();
            return false;
        }
    }

}
