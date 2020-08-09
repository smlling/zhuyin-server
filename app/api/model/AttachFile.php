<?php

namespace app\api\model;

use think\Model;

class AttachFile extends Model {

    /**
     * 添加文件记录
     * @author lwtting <smlling@hotmail.com>
     * @param integer $uid      上传者uid
     * @param string $path      文件路径
     * @param string $type      文件类型 image|video
     * @param string $ip        上传者IP地址
     * @return AttachFile
     */
    public function add ($uid, $path, $type, $ip) {
        // 多文件写入的时候防止主键重复
        if (isset($this->id)){
            unset($this->id);
        }
        $this->uid = $uid;
        $this->path = $path;
        $this->file_type = $type;
        $this->ip = $ip;
        $this->upload_time = time();
        $this->isUpdate(false)->save();

        return $this;
    }

    /**
     * 删除文件记录(软)
     * @author lwtting <smlling@hotmail.com>
     * @return void
     */
    public function del () {
        $this->delete_time = time();
        $this->save();
    }
}