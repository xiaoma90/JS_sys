<?php
namespace app\admin\controller;
use think\Controller;

class File extends Controller
{
    public function upload()
    {
        $file = request()->file('files');
        // var_dump($file);die;
        if(isset($file)){
            // 获取表单上传文件 例如上传了001.jpg
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public/uploads');
            // $info = array($info);
            // var_dump($info) ;die;

            if($info){
                      // 成功上传后 获取上传信息
                $a      =$info->getSaveName();
                $imgp   = str_replace("\\","/",$a);
                $imgpath='/uploads/'.$imgp;

                $image = \think\Image::open('.'.$imgpath);
                // 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
                $image -> thumb(600, 600) -> save('.'.$imgpath);//直接把缩略图覆盖原图

                return json(['code' => 1, 'data' => 'http://18ico.ewtouch.com'.$imgpath, 'msg' =>'上传成功']);
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
}
