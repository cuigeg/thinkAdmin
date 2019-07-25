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

namespace app\admin\queue;

use app\admin\service\QueueService;
use think\console\Output;

/**
 * 基础指令公共类
 * Class JobsQueue
 * @package app\admin
 */
class JobsQueue
{
    /**
     * 待处理
     */
    const STATUS_PEND = 1;

    /**
     * 处理中
     */
    const STATUS_PROC = 2;

    /**
     * 处理完成
     */
    const STATUS_COMP = 3;

    /**
     * 处理失败
     */
    const STATUS_FAIL = 4;

    /**
     * 任务ID
     * @var integer
     */
    protected $id;

    /**
     * 任务数据
     * @var array
     */
    protected $data;

    /**
     * 任务名称
     * @var string
     */
    protected $title;

    /**
     * 任务状态
     * @var integer
     */
    protected $status;

    /**
     * @var Output
     */
    protected $output;

    /**
     * 任务状态描述
     * @var string
     */
    protected $statusDesc = '';

    /**
     * 启动任务处理
     * @param \think\queue\Job $job 当前任务对象
     * @param array $data 任务执行对象
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function fire(\think\queue\Job $job, $data = [])
    {
        $this->data = $data;
        $this->output = new Output();
        $this->id = isset($data['_job_id_']) ? $data['_job_id_'] : '';
        $this->title = isset($data['_job_title_']) ? $data['_job_title_'] : '';
        $this->output->newLine();
        $this->output->writeln("       system task {$this->id} execution start");
        $this->output->writeln('---------------------------------------------');
        QueueService::status($this->id, self::STATUS_PROC, $this->statusDesc);
        if ($this->execute()) {
            $this->output->writeln('---------------------------------------------');
            $this->output->info("                successful");
            $this->status = self::STATUS_COMP;
        } else {
            $this->output->writeln('---------------------------------------------');
            $this->output->error("                failure");
            $this->status = self::STATUS_FAIL;
        }
        $job->delete();
        QueueService::status($this->id, $this->status, $this->statusDesc);
        $this->output->writeln('---------------------------------------------');
        $this->output->newLine();
    }

    /**
     * 执行任务
     * @return boolean
     */
    protected function execute()
    {
        return true;
    }

}
