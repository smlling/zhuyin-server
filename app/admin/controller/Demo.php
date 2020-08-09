<?php

namespace app\admin\controller;

use think\Controller;

/**
 * Comment: 测试文件，查看接口是否请求成功
 * Author: zzw
 */
class Demo extends Controller {
    /**
     * Comment: 测试接口
     * Author: zzw
     * Date: 2019/12/21 17:02
     * @return string
     */
    public function demo(){

        var_dump(config());
        // echo config('email_account');
        return  '成功请求admin下的测试接口';
    }


}