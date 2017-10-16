<?php
namespace app\index\controller;

class Index
{
    public function addOrder(){
        
    }
    public function step()
    {
        #1,插入头12的坑位
        //$this->front();
        #1，插入待用位置
        $this->simulator(120);
        #2，更新相应层级
        $this->level();
        #3,更新相应排号
        $this->number();
        #4,更新 pid
        $this->setPid();
    }

    /**
     * #导入订单表 oid uid
     */
    public function importOrder(){
        $data = db('orders')->where(['status'=>['in',[2,3,4]],'type'=>0])->order('id asc')->select();
        foreach($data as $k=>$v){
           $this->importOne($v['id']);
        }
    }

    /**
     *单独导入订单
     * @param bool $id
     * @return bool
     */
    public function importOne($id=false)
    {
        if (!$id) {
            $id = input('oid');
        }

        $order = db('orders')->where(['id' => $id])->find();
        if (empty($order) || $order['type'] != 0)exit('fail1');
        $test = db('test')->where(['oid'=>$id,'is_add'=>1])->find();
        if(!empty($test))exit('fail2');

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
                $this->bonus($n['id']+1);
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
            $this->bonus($n['id']+2);
        }
        exit('success');
    }
    #判断是否有出局单数 并插入记录
    public function bonus($id)
    {
        $test = db('test')->where(['id'=>$id])->find();
        #是否符合最低标准
        if($test['oid'] && ($test['level'] >=4)){
            $num = $id-120;
            if($num>80 && ($num%81 ==0)){
                $n = $num/81;
                $oid = db('test')->where(['id'=>$n])->find()['oid'];
                #符合条件
                $req = db('triangles')->where(['a_id'=>$oid])->whereOr(['b_id'=>$oid])->whereOr(['c_id'=>$oid])->find();
                $triangles = db('bonus')->where(['tr_id'=>$req['id']])->find();
                if(empty($triangles)){
                    $in['created_at']   = date('YmdHis');
                    $in['user_id']      = $req['user_id'];
                    $in['tr_id']        = $req['id'];
                    $in['status']        = $req['status']; 
                    $in['amount']        = 14000; 
                    return db('bonus')->insertGetId($in)?:false;
                }else{
                    $in['status']        = $req['status']; 
                    return db('bonus')->where(['tr_id'=>$req['id']])->update($in)?:false;
                }
            }
        }else{
            return false;
        }
    }

    #添加坑位
    public function simulator($num)
    {
        if (!($num>0 && $num%3 ==0)){
            dump('请输入3的整数倍！');exit;
        }
        #先加12条固定数据  `id`   `is_add`  `oid`  `level`  `created_at`  `updated_at`;
        $insert['created_at'] =  date('YmdHis');
        $insert['updated_at'] =  date('YmdHis');
        $insert['is_add'] = 1;

        for($i=1;$i<=$num;$i++){
            if($i%3 == 0){
                $insert['is_add'] = 2;
            }else{
                $insert['is_add'] = 1;
            }
            echo db('test')->insertGetId($insert);
        }

    }
    private function front(){
        #先加12条固定数据  `id`   `is_add`  `oid`  `level`  `created_at`  `updated_at`;
        $insert['created_at'] =  date('YmdHis');
        $insert['updated_at'] =  date('YmdHis');
        $insert['is_add'] = 1;
        for($i=1;$i<=12;$i++){
            echo db('test')->insertGetId($insert);
        }
    }
    #更新层级
    public function level(){
        $data = db('test')->whereNull('level')->select();
//        dump($data);exit;
        foreach($data as $k=>$v){
           $res =  db('test')->where(['id'=>$v['id']])->update(['level'=>$this->calculate($v['id'])]);
            echo $res;
        }
    }
    #更新层排号
    public function number(){
        for($i=1;$i<20;$i++){
            $data = db('test')->whereNull('num')->select();
            $j=1;
            foreach($data as $k=>$v){
                if($v['level'] == $i){
                    $res =  db('test')->where(['id'=>$v['id']])->update(['num'=>$j++]);
                    echo $res;
                }
            }
        }
    }
    #计算层级
    protected function calculate($id){
        for($i=20;$i>=0;$i--){
            #$id>S(n-1 则返回n
            $Sn = (pow(3,$i)-1)*3/2;
            if($id>$Sn){
                return ($i+1);
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
    #计算当前单号进度
    public  function process($id=false){
        if(!$id){
            $id = input('oid');
        }
        $order = db('orders')->where(['id'=>$id])->find();
        if(empty($order) || $order['type'] !=0 || !in_array($order['status'],[2,3,4]))return json_decode(json_encode(['status'=>0,'code'=>"无效订单"]));
        $data = db('test')->where(['oid'=>$id,'is_add'=>1])->find();
        if(empty($data))return json_decode(json_encode(['status'=>0,'code'=>"订单未公排"]));
        $l = ['X','V1','V2','V3','V4'];
        $Sn = db('test')->whereNotNull('oid')->order('id desc')->find()['id'];
        $c = $this->conf($data['id']);
        $res = 'X';
        foreach($c as $k=>$v){
            if($Sn >= $v){
                $res = $l[$k+1];
            }
        }
        $key = array_search($res,$l);
        if($res != 'V4'){
            $S = $c[$key]-$Sn;
        }else {
            $S = 0;
        }
        if(input('oid')){
            return $this->package([$res,$S,$data]);
        }else{
            return [$res,$S];
        }

    }
    #计算当前单号进度
    public  function process1($id=false){
        if(!$id)$id = input('id');
        $data = db('test')->where(['id'=>$id])->find();
        $Sn = db('test')->whereNotNull('oid')->order('id desc')->value('id');
        $l = ['X','V1','V2','V3','V4'];  
        $c = $this->conf($data['id']);
        $res = 'X';
        foreach($c as $k=>$v){
            if($Sn >= $v){
                $res = $l[$k+1];
            }
        }
        $key = array_search($res,$l);
        if($res != 'V4'){
            $S = $c[$key]-$Sn;
        }else {
            $S = 0;
        }
        return [$res,$S];
    }
    private function package($res=array()){
        switch($res[0]){
            case 'X':
                return json_decode(json_encode(['status'=>'第一轮','code'=>$res[1],'xz'=>'1000*1','yy'=>'370','zm'=>'300*2','ft'=>3986,'jr'=>'6000','l'=>$res[2]['level'],'num'=>$res[2]['num']]));
                break;
            case 'V1':
                return json_decode(json_encode(['status'=>'第二轮','code'=>$res[1],'xz'=>'1000*3','yy'=>'14','zm'=>'500*2','ft'=>'3986','jr'=>'10000','l'=>$res[2]['level'],'num'=>$res[2]['num']]));
                break;
            case 'V2':
                return json_decode(json_encode(['status'=>'第三轮','code'=>$res[1],'xz'=>'1000*4','yy'=>'1014','zm'=>'500*2','ft'=>'3986','jr'=>'20000','l'=>$res[2]['level'],'num'=>$res[2]['num']]));
                break;
            case 'V3':
                return json_decode(json_encode(['status'=>'第四轮','code'=>$res[1],'xz'=>'500*6','yy'=>'5014','zm'=>'1000*4','ft'=>'3986','jr'=>'30000','l'=>$res[2]['level'],'num'=>$res[2]['num']]));
                break;
            case 'V4':
                return json_decode(json_encode(['status'=>'出局','code'=>$res[1],'xz'=>0,'yy'=>0,'zm'=>0,'ft'=>0,'jr'=>'0','l'=>$res[2]['level'],'num'=>$res[2]['num']]));
                break;
        }
    }
    
    private function setPid(){
        $level = db('test')->whereNull('pid')->find()['level'];
        $data = db('test')->where(['level'=>['>=',$level-1]])->select();
        foreach($data as $k=>$v){
            $conf = $this->conf($v['id']);
            $child = db('test')->whereBetween('id',[$conf[0]-2,$conf[0]])->select();
            foreach ($child as $ko =>$vo) {
                if(!$vo['pid']){
                    $res =  db('test')->where(['id'=>$vo['id']])->update(['pid'=>$v['id']]);
                }
            }
        }
    }
    /**计算参数
     * @param $i
     * @param
     * @return mixed [v1,v2,v3,v4]
     */
    public function conf($i=false){
        if(!$i)$i=input('id');
        $test = db('test')->where(['id'=>$i])->find();
        //下一层顶出条件
        // $max1   = db('test')->where(['level'=>$test['level']])->max('num');
//        $V1     = db('test')->where(['level'=>$test['level']+1,'num'=>3*$test['num']])->value('id');
        $V1     = (pow(3,$test['level'])-1)*3/2+3*$test['num'];
        //下二层顶出条件
//        $V2     = db('test')->where(['level'=>$test['level']+2,'num'=>9*$test['num']])->value('id');
        $V2     = (pow(3,$test['level']+1)-1)*3/2+9*$test['num'];
        //下三层顶出条件
//        $V3     = db('test')->where(['level'=>$test['level']+3,'num'=>27*$test['num']])->value('id');
        $V3     = (pow(3,$test['level']+2)-1)*3/2+27*$test['num'];
        //出局
//        $V4     = db('test')->where(['level'=>$test['level']+4])->order('id asc')->find()['id']-1;
        $V4     = (pow(3,$test['level']+3)-1)*3/2+81*$test['num'];
        return [$V1,$V2,$V3,$V4];


//       $Sn = (pow(3,$i)-1)*3/2;
    }
    public function test(){
        $i=input('id');
        dump($this->conf($i));
    }
}
