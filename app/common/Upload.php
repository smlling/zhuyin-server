<?php

namespace app\common;

use app\common\Utils;
use app\lib\exception\Status;
use app\lib\exception\ApiException as Error;

class Upload {

    /**
     * 上传用户头像
     * @author lwtting <smlling@hotmail.com>
     * @return string   头像文件路径
     */
    public function uploadAvatar () {
        // 上传的临时文件
        $upload_file = request()->file('avatar');
        if (!$upload_file) {
            throw new Error(Status::NO_FILE_UPLOADED);
        }

        // 最终转码保存使用的格式
        $file_postfix = '.jpg';

        // 检查文件合法性
        if (!$upload_file->validate(config('settings.upload.avatar_rule'))->check()) {
            throw new Error(Status::INVALID_FILE, $upload_file->getError());
        }

        // 存储目录
        $save_folder = date('Y-m-d') . '/';
        if (!file_exists(config('settings.upload.avatar_path') . $save_folder)){
            mkdir(config('settings.upload.avatar_path') . $save_folder,0666,true);
        }
        // 重命名文件
        $file_name = Utils::generateUniqueKey();
        $full_path = config('settings.upload.avatar_path') .  $save_folder . $file_name . $file_postfix;
        $thumb_path = $full_path . '.thumb';

        // 使用图像工具重绘图片 防止他人恶意在图片中隐藏恶意代码
        // 使用JPG格式保存防止用户利用PNG可以保存透明像素的特性来上传‘透明头像’，影响前端页面统一性
        try {
            $image = \think\Image::open($upload_file);
        } catch (\Exception $e) {
            throw new Error(Status::INVALID_FILE, '图像文件被破坏');
        }

        // 重绘后的原图
        $image->save($full_path, 'jpg', 100);
        
        // 缩略图
        $image->thumb(100,100,\think\Image::THUMB_CENTER)->save($thumb_path, 'jpg', 100);
        
        return $save_folder . $file_name . $file_postfix;
    }


    /**
     * 上传组件
     * @author lwtting <smlling@hotmail.com>
     * @param string $username  用户名 用于加水印
     * @return array            文件列表
     */
    public function uploadSquareFile ($username) {
        // 上传的临时文件
        $upload_images = request()->file('images') ?: [];
        $upload_videos = request()->file('videos') ?: [];
        if (!is_array($upload_images) || !is_array($upload_videos)) {
            throw new Error(Status::FORM_PARAM_ERROR, '参数错误: 请以images[]数组形式上传图片,以videos[]数组形式上传视频');
        }

        // 单贴上传文件数量限制
        $upload_limit = config('settings.upload.upload_limit');

        // 最终转码保存使用的格式
        $image_postfix = '.jpg';
        $video_postfix = '.mp4';

        // 文件列表
        $file_list = [];

        // 遍历处理图像文件
        foreach ($upload_images as $upload_file) {

            // 达到数量限制时直接返回 多传的不要了
            if (!$upload_limit) {
                return $file_list;
            }

            // 检查文件合法性
            if (!$upload_file->validate(config('settings.upload.square_image_rule'))->check()) {
                throw new Error(Status::INVALID_FILE, $upload_file->getError());
            }

            // 存储目录
            $save_folder = date('Y-m-d') . '/';
            if (!file_exists(config('settings.upload.square_path') . $save_folder)){
                mkdir(config('settings.upload.square_path') . $save_folder,0666,true);
            }
            // 重命名文件
            $file_name = Utils::generateUniqueKey();
            $full_path = config('settings.upload.square_path') .  $save_folder . $file_name . $image_postfix;
            $thumb_path = $full_path . '.thumb';

            // 使用图像工具重绘图片 防止他人恶意在图片中隐藏恶意代码
            // 使用JPG格式保存防止用户利用PNG可以保存透明像素的特性来上传‘透明头像’，影响前端页面统一性
            try {
                $image = \think\Image::open($upload_file);
            } catch (\Exception $e) {
                throw new Error(Status::INVALID_FILE, '图像文件被破坏');
            }

            // 重绘并添加水印后的原图
            $font_size = ($image->width() + $image->height()) / 100;
            $image->text('@主音 ' . $username,realpath('Alibaba-PuHuiTi-Regular.otf'),$font_size,'#ffffff',\think\Image::WATER_SOUTH)
                ->save($full_path, 'jpg', 100);
            
            // 缩略图 原尺寸1/10
            $thumb_width = ceil($image->width() / 10);
            $thumb_height = ceil($image->height() / 10);
            $image->thumb($thumb_width, $thumb_height, \think\Image::THUMB_CENTER)->save($thumb_path, 'jpg', 100);

            $upload_limit -= 1;
            $file_list[] = [
                'filename'  => $file_name,
                'path' => $full_path,
                'type' => 'image'
            ];
        }

        // 遍历处理视频文件
        foreach ($upload_videos as $upload_file) {

            // 达到数量限制时直接返回 多传的不要了
            if (!$upload_limit) {
                return $file_list;
            }

            // 检查文件合法性
            if (!$upload_file->validate(config('settings.upload.square_video_rule'))->check()) {
                return $this->error(Status::INVALID_FILE, $upload_file->getError());
            }

            // 存储目录
            $save_folder = date('Y-m-d') . '/';
            if (!file_exists(config('settings.upload.square_path') . $save_folder)){
                mkdir(config('settings.upload.square_path') . $save_folder,0666,true);
            }
            // 重命名文件
            $file_name = Utils::generateUniqueKey();
            $full_path = config('settings.upload.square_path') .  $save_folder . $file_name . $video_postfix;
            $thumb_path = $full_path . '.thumb';

            // 实例化FFMPEG
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => 'E:/DevEnvironment/ffmpeg-20200131-62d92a8-win64-static/bin/ffmpeg.exe',
                'ffprobe.binaries' => 'E:/DevEnvironment/ffmpeg-20200131-62d92a8-win64-static/bin/ffprobe.exe'
            ]);

            // 生成视频第5秒的预览图
            $video = $ffmpeg->open($upload_file->getPathname());
            $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(5))
                ->save($thumb_path);

            // 将视频预览图转为原尺寸1/10的缩略图
            $image = \think\Image::open($thumb_path);
            $thumb_width = ceil($image->width() / 10);
            $thumb_height = ceil($image->height() / 10);
            $image->thumb($thumb_width, $thumb_height, \think\Image::THUMB_CENTER)
                ->save($thumb_path, 'jpg', 100);
            
            // 移动原文件文件
            // $upload_file->move($full_path);
            move_uploaded_file($upload_file->getPathname(), $full_path);

            $upload_limit -= 1;
            $file_list[] = [
                'filename'  => $file_name,
                'path'      => $full_path,
                'type'      => 'video'
            ];
        }
        return $file_list;
    }

}
