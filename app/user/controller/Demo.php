<?php

namespace app\user\controller;

use think\Controller;
use app\lib\exception\Status;
use app\lib\exception\ApiException;
use app\api\model\SquareComment;
use app\api\model\SquareActivity;
use app\common\Upload;
use app\api\model\SystemCounter;
use think\Db;
use app\api\model\AttachFile;

class Demo extends Controller {
    protected $middleware = [ 
    	'UserCheck:guest' 
    ];

    public function demo () {

        foreach (SquareActivity::where(true)->select() as $activity) {
            $attach = $activity->attachFileList();
            // $new = [];
            // foreach($attach as $file) {
            //     $file = AttachFile::get($file);
            //     $new[$file->id] = substr($file->path, strrpos($file->path, '/') + 1, 32);
            // }
            // var_dump(($attach));
            // var_dump(array_flip($attach));
            $activity->attach_files = json_encode(array_flip($attach));
            $activity->save();
        }
        
    }
}