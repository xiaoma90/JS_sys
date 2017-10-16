<?php
namespace app\index\controller;


class Index1
{
    /**计算配置
     * @param $i
     * @param
     * @return mixed [v1,v2,v3,v4]
     */
    public function conf($i = false)
    {
        if (!$i) $i = input('id');
        if (is_numeric($i)) {
            //第一轮条件3986
            $x = $i;
            //第二轮条件6000
            $v1 = $i * 3;
            //第三轮条件10000
            $v2 = $i * 9;
            //第四轮条件20000
            $v3 = $i * 27;
            //出局30000
            $v4 = $i * 81;
            return [$x, $v1, $v2, $v3, $v4];
        } else {
            return false;
        }
    }

    /**计算当前单号进度
     * @param bool $id
     * @return array|mixed
     */
    public function process($id = false)
    {
        if (!$id) {
            $id = input('oid');
        }
        $order = db('orders')->where(['id' => $id])->find();
        if (empty($order) || $order['type'] != 0 || !in_array($order['status'], [2, 3, 4])) return json_decode(json_encode(['status' => 0, 'code' => "无效订单"]));
        $data = db('test')->where(['oid' => $id, 'is_add' => 1])->find();
        if (empty($data)) return json_decode(json_encode(['status' => 0, 'code' => "订单未公排"]));
        $l = ['X', 'V1', 'V2', 'V3', 'V4'];
        $Sn = db('test')->whereNotNull('oid')->order('id desc')->value('id');
        $c = $this->conf($data['id']);
        $res = 'X';
        foreach ($c as $k => $v) {
            if ($Sn >= $v) {
                $res = $l[$k];
            }
        }
        $key = array_search($res, $l);
        if ($res != 'V4') {
            $S = $c[$key] - $Sn;
        } else {
            $S = 0;
        }
        if (input('oid')) {
            return $this->package([$res, $S, $data]);
        } else {
            return [$res, $S];
        }

    }

    /**计算当前单号进度
     * @param bool $id
     * @return array|bool
     */
    public function process1($id = false)
    {
        if (!$id) $id = input('id');
        if (db('test')->where(['id' => $id])->find()) {
            $Sn = db('test')->whereNotNull('oid')->order('id desc')->value('id');
            $l = ['X', 'V1', 'V2', 'V3', 'V4'];
            $c = $this->conf($id);
            $res = 'X';
            foreach ($c as $k => $v) {
                if ($Sn >= $v) {
                    $res = $l[$k];
                }
            }
            $key = array_search($res, $l);
            if ($res != 'V4') {
                $S = $c[$key] - $Sn;
            } else {
                $S = 0;
            }
            return [$res, $S];
        } else {
            return false;
        }

    }

    /**
     * @param array $res
     * @return mixed
     */
    private function package($res = array())
    {
        switch ($res[0]) {
            case 'X':#第一平台
                return json_decode(json_encode(['status' => '第一平台', 'code' => $res[1], 'xz' => '1000*1', 'yy' => '370', 'zm' => '300*2', 'ft' => 3986, 'jr' => '6000', 'l' => $res[2]['level'], 'num' => $res[2]['num']]));
                break;
            case 'V1':#第二平台
                return json_decode(json_encode(['status' => '第二平台', 'code' => $res[1], 'xz' => '1000*3', 'yy' => '14', 'zm' => '500*2', 'ft' => '3986', 'jr' => '10000', 'l' => $res[2]['level'], 'num' => $res[2]['num']]));
                break;
            case 'V2':#第三平台
                return json_decode(json_encode(['status' => '第三平台', 'code' => $res[1], 'xz' => '1000*4', 'yy' => '1014', 'zm' => '500*2', 'ft' => '3986', 'jr' => '20000', 'l' => $res[2]['level'], 'num' => $res[2]['num']]));
                break;
            case 'V3':#第四平台
                return json_decode(json_encode(['status' => '第四平台', 'code' => $res[1], 'xz' => '500*6', 'yy' => '5014', 'zm' => '1000*4', 'ft' => '3986', 'jr' => '30000', 'l' => $res[2]['level'], 'num' => $res[2]['num']]));
                break;
            case 'V4':#出局
                return json_decode(json_encode(['status' => '出局', 'code' => $res[1], 'xz' => 0, 'yy' => 0, 'zm' => 0, 'ft' => 0, 'jr' => '0', 'l' => $res[2]['level'], 'num' => $res[2]['num']]));
                break;
        }
    }

    /**
     * #导入订单表 oid uid
     */
    public function importOrder()
    {
        $data = db('orders')->where(['status' => ['in', [2, 3, 4]], 'type' => 0])->order('id asc')->select();
        foreach ($data as $k => $v) {
            $this->importOne($v['id']);
        }
    }

    /**
     *单独导入订单
     * @param bool $id
     * @return bool
     */
    public function importOne($id = false)
    {
        if (!$id) {
            $id = input('oid');
        }
        $order = db('orders')->where(['id' => $id])->find();
        if (empty($order) || $order['type'] != 0) exit('fail1');
        $test = db('test')->where(['oid' => $id, 'is_add' => 1])->find();
        if (!empty($test)) exit('fail2');
        //目前的坑位
        $n = db('test')->whereNotNull('oid')->order('id desc')->find();
        if (empty($n)) {
            $n['id'] = 0;
        }
        //下一个坑
        $d = db('test')->where(['id' => $n['id'] + 1])->find();
        if (empty($d)) {
            $this->step();
            $this->importOne($id);
            exit;
        } else {
            if ($d['is_add'] == 1) {
                $res = db('test')->where(['id' => $n['id'] + 1])->update(['oid' => $order['id'], 'uid' => $order['user_id']]);
            } else {
                //复投单+1
                $in['tid'] = $n['id'] + 1;
                $in['uid'] = 0;
                $in['created_at'] = date('YmdHis');
                $in['updated_at'] = date('YmdHis');
                $res = db('single')->insertGetId($in);
                //更新test
                $res1 = db('test')->where(['id' => $n['id'] + 1])->update(['oid' => $res, 'uid' => 0]);
                #判断是否有出局单数 并插入记录
                $this->bonus($n['id'] + 1);
            }
        }
        $d2 = db('test')->where(['id' => $n['id'] + 2])->find();
        if (empty($d2)) {
            $this->step();
        }
        if ($d2['is_add'] == 2) {
            //复投单+1
            $in['tid'] = $n['id'] + 2;
            $in['uid'] = 0;
            $in['created_at'] = date('YmdHis');
            $in['updated_at'] = date('YmdHis');
            $res2 = db('single')->insertGetId($in);
            //更新test
            $res3 = db('test')->where(['id' => $n['id'] + 2])->update(['oid' => $res, 'uid' => 0]);
            #判断是否有出局单数 并插入记录
            $this->bonus($n['id'] + 2);
        }
        exit('success');
    }

    #判断是否有出局单数 并插入记录
    public function bonus($id)
    {
        $test = db('test')->where(['id' => $id])->find();
        #是否符合最低标准
        if ($test['oid'] && ($test['level'] >= 4)) {
            if ($id > 80 && ($id % 81 == 0)) {
                $n = $id / 81;
                $oid = db('test')->where(['id' =>$n])->value('oid');
                #符合条件
                $req = db('triangles')->where(['a_id' => $oid])->whereOr(['b_id' => $oid])->whereOr(['c_id' => $oid])->find();
                $triangles = db('bonus')->where(['tr_id' => $req['id']])->find();
                if (empty($triangles)) {
                    $in['created_at'] = date('YmdHis');
                    $in['user_id'] = $req['user_id'];
                    $in['tr_id'] = $req['id'];
                    $in['status'] = $req['status'];
                    $in['amount'] = 14000;
                    return db('bonus')->insertGetId($in) ?: false;
                } else {
                    $in['status'] = $req['status'];
                    return db('bonus')->where(['tr_id' => $req['id']])->update($in) ?: false;
                }
            }
        } else {
            return false;
        }
    }

    #添加坑位
    public function simulator($num)
    {
        if (!($num > 0 && $num % 3 == 0)) {
            dump('请输入3的整数倍！');
            exit;
        }
        #先加12条固定数据  `id`   `is_add`  `oid`  `level`  `created_at`  `updated_at`;
        $insert['created_at'] = date('YmdHis');
        $insert['updated_at'] = date('YmdHis');
        $insert['is_add'] = 1;

        for ($i = 1; $i <= $num; $i++) {
            if ($i % 3 == 0) {
                $insert['is_add'] = 2;
            } else {
                $insert['is_add'] = 1;
            }
            echo db('test')->insertGetId($insert);
        }

    }

    private function front()
    {
        #先加12条固定数据  `id`   `is_add`  `oid`  `level`  `created_at`  `updated_at`;
        $insert['created_at'] = date('YmdHis');
        $insert['updated_at'] = date('YmdHis');
        $insert['is_add'] = 1;
        for ($i = 1; $i <= 12; $i++) {
            echo db('test')->insertGetId($insert);
        }
    }

    #更新层级
    public function level()
    {
        $data = db('test')->whereNull('level')->select();
//        dump($data);exit;
        foreach ($data as $k => $v) {
            $res = db('test')->where(['id' => $v['id']])->update(['level' => $this->calculate($v['id'])]);
            echo $res;
        }
    }

    #更新层排号
    public function number()
    {
        for ($i = 1; $i < 20; $i++) {
            $data = db('test')->whereNull('num')->select();
            $j = 1;
            foreach ($data as $k => $v) {
                if ($v['level'] == $i) {
                    $res = db('test')->where(['id' => $v['id']])->update(['num' => $j++]);
                    echo $res;
                }
            }
        }
    }

    #计算层级
    protected function calculate($id)
    {
        for ($i = 20; $i >= 0; $i--) {
            #$id>S(n-1 则返回n
            $Sn = (pow(3, $i) - 1) * 3 / 2;
            if ($id > $Sn) {
                return ($i + 1);
            }
        }
    }


    #返回树形数据
    /**
     * @param bool $id
     * @return mixed
     */
    public function tree($id=false)
    {
        if (!$id) {
            $id = input('oid');
        }
        $data = db('test')->where(['oid'=>$id,'is_add'=>1])->field('id,pid,oid,is_add')->find();
        if(empty($data))return json_decode(json_encode(['status'=>0,'code'=>"订单未公排"]));
        $res = $this->process($id);   //进度
        $dd = [];
        $ini = ['X'=>1,'V1'=>2,'V2'=>3,'V3'=>4,'V4'=>4];
        switch($res[0]){
            case 'X':
                $x[0] = $data;
                $dd = $this->trees($x);
                break;
            case 'V1':
                $x[0] = $data;
                $dd = $this->trees($x,2);
                break;
            case 'V2':
                $x[0] = $data;
                $dd = $this->trees($x,3);
                break;
            case 'V3':
                $x[0] = $data;
                $dd = $this->trees($x,4);
                break;
            case 'V4':
                $x[0] = $data;
                $dd = $this->trees($x,4);
                break;
        }
//        return json_decode(json_encode(['status'=>1,'data'=>$dd]));
        return json_decode(json_encode($dd));

    }
    private function trees($arr,$i=1){
        if(!is_array($arr))return false;
        if($i>0){
            $i--;
            foreach($arr as $k=>$v){
                if($v['is_add'] == 1){
                    $arr[$k]['order_num'] = db('orders')->where(['id'=>$v['oid']])->value('order_num');
                }else{
                    $arr[$k]['order_num'] = "复投单";
                }
                $da = db('test')->where(['pid'=>$v['id']])->whereNotNull('oid')->field('id,pid,oid,is_add')->select();
                if(!empty($da)){
                    if($i>0){
                        $arr[$k]['child'] = $this->trees($da,$i);
                    }else{
                        foreach($da as $k1=>$v1){
                            if($v1['is_add'] == 1){
                                $da[$k1]['order_num'] = db('orders')->where(['id'=>$v1['oid']])->value('order_num');
                            }else{
                                $da[$k1]['order_num'] = "复投单";
                            }
                        }
                        $arr[$k]['child'] = $da;
                    }
                }
            }
        }
        return $arr;
    }
    public function orderInfo($id=false){
        return db('orders')->where(['id'=>$id])->find()?:null;
    }
    public function step()
    {
        #1,插入头12的坑位
        //$this->front();
        #1，插入待用位置
        $this->simulator(81);
        #2，更新相应层级
        $this->level();
        #3,更新相应排号
        $this->number();
        #4,更新 pid
        $this->setPid();
    }
    public function test(){
        $i=input('id');
        dump($this->conf($i));
    }

}