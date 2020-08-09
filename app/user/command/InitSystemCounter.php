<?php

namespace app\user\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\user\model\SystemCounter;

class InitSystemCounter extends Command {
    
    protected function configure() {
        // 指令配置
        $this->setName('initSystemCounter');
        // 设置参数
        
    }

    protected function execute(Input $input, Output $output) {
        
        foreach (SystemCounter::SYSTEM_COUNTER as $counter_id => $counter_name) {
            $counter = SystemCounter::get($counter_id);
            if (is_null($counter)) {
                $counter = new SystemCounter;
                $counter->id = $counter_id;
                $counter->name = $counter_name;
                $counter->count = 0;
                $counter->isUpdate(false)->save();
            }
        }
        $output->writeln('系统计数器初始化成功');
    }
}
