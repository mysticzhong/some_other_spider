<?php
use phpQuery;
use models\VmeiCategory;
use models\VmeiGoods;


/**
 * @desc Vmei的爬虫
 * @see  http://m.vmei.com/
 */
class Vmei{

    /**
     * @desc 循环数据库并填充产品详情信息
     */
    public function fullWellTheDetail(){
        $vg = VmeiGoods::where('createdtime','<',1561457715)->select('*')->orderBy('id','asc')->offset(0)->limit(100)->get();
        foreach ($vg as $vgone){
            $data = $this->getProDetails($vgone->vmei_id);
            $data['createdtime'] = time();
            $ex1 = VmeiGoods::where('id',$vgone->id)->update($data);
            var_dump($ex1);
        }
    }


    /**
     * @desc 获取产品内页详情并解析html出为有效信息
     */
    private function getProDetails($vid){

        $url = 'http://m.vmei.com/product/'.$vid;
        $html = file_get_contents($url);

        $preg = "/<script[\s\S]*?<\/script>/i";
        $html = preg_replace($preg,"",$html,-1);

        $preg = "/<style[\s\S]*?<\/style>/i";
        $html = preg_replace($preg,"",$html,-1);

        require_once base_path().'/vendor/phpQuery/phpQuery.php';
        $doc = \phpQuery::newDocumentHTML($html,'utf-8');
        \phpQuery::selectDocument($doc);

        // 预定义参数值
        $attribute = [];
        $description = $brand_name = '';
        foreach (pq('.goods-function') as $gf) {
            $text = self::query_convert_encoding($gf->nodeValue);
            $tag = explode("\r",$text);
            $thistag = '';
            foreach($tag as $tagone){
                if(trim($tagone) != ""){
                    if(mb_strlen($tagone,"utf-8") > 50){
                        $attribute[$thistag][] = trim($tagone);
                    }else{
                        $attribute[trim($tagone)] = [];
                        $thistag = trim($tagone);
                    }
                }
            }
            // 品牌名字
            if(isset($attribute['品牌'][0])){ $brand_name = $attribute['品牌'][0]; }
        }

        // 产品的属性
        $attribute = json_encode($attribute);

        // 规格尺寸
        $size = [];
        foreach (pq('.spec-menu-bd .product-sku .item') as $it) {
            $its = self::query_convert_encoding($it->nodeValue);
            $itss = explode("\r",$its);
            foreach($itss as $itsone){
                if(trim($itsone) != ""){
                    $size[] = trim($itsone);
                }
            }
        }
        $size = json_encode($size);

        // 缩小查找范围以快速查找下面的内容
        $start = mb_strpos($html,'<!-- Content put here -->',0,'utf-8');
        $end = mb_strpos($html,'<!-- Content put here - end -->',0,'utf-8');
        $newhtml = mb_substr($html,($start+25),($end-$start-26),'utf-8');
        $doc2 = \phpQuery::newDocumentHTML($newhtml,'utf-8');
        \phpQuery::selectDocument($doc2);

        // 详细文字介绍
        foreach (pq('table') as $tab) {
            $description = self::query_convert_encoding($tab->nodeValue);
            $description = str_replace("\n","",$description);
            $description = str_replace(" ","",$description);
            if($description != ""){
                break;
            }
        }

        // 长图详情
        $details = [];
        foreach (pq('img') as $box) {
            $src = $box->getAttribute('data-original');
            if($src != "http://img01.vmei.com/static/b2c/details.png") {
                $details[] = $src;
            }
        }
        $details = json_encode($details);

        $data = [];
        $data['brand_name'] = $brand_name;
        $data['details'] = $details;
        $data['description'] = $description;
        $data['size'] = $size;
        $data['attribute'] = $attribute;

        return ($data);
    }


    /**
     * @desc 重新整理产品的名字 - 去除特殊符号
     */
    public function resizeName(){

        $vg = VmeiGoods::where('name', 'like', '%【%')->select('id','name')->orderBy('id','asc')->offset(0)->limit(100)->get()->toArray();
        foreach ($vg as $item){
            var_dump($item['name']);
            $s = mb_strpos($item['name'],"【",0,"utf-8");
            $e = mb_strpos($item['name'],"】",0,"utf-8");

            $tag =  mb_substr($item['name'],$s,($e-$s)+1);
            $newname = str_replace($tag," ",$item['name']);
            var_dump($newname);
            $ex3 = VmeiGoods::where('id',$item['id'])->update(['name' => $newname]);
            var_dump($ex3);
        }
    }


    /**
     * @desc 替换产品详情内页图为本地域名图片(可选用)
     */
    public function repProDetails(){
        // 为使用微擎方法 被迫使用全局变量
        global $_W;
        include_once base_path()."/../../framework/function/file.func.php";
        set_time_limit(0);
        @ini_set('memory_limit','256M');
        if(
            !isset($_W['setting']['remote']['type'])
            && isset($_W['setting']['remote'][1]['type'])
        ){
            $_W['setting']['remote'] = $_W['setting']['remote'][1];
            $_W['setting']['remote']['type'] = 3;
        }

        $tmp_path01 = "images/store/goods/details/";
        $path01 = ATTACHMENT_ROOT.$tmp_path01;

        $vg = VmeiGoods::where('detail','')->select('id','details')->orderBy('id','asc')->offset(0)->limit(25)->get()->toArray();
        foreach ($vg as $vgone){
            var_dump($vgone['id']);

            if($vgone['details'] == "[]"){
                $ex1 = VmeiGoods::where('id',$vgone['id'])->update(['detail' => '[]']);
                var_dump($ex1);
                continue;
            }

            $vgone['details'] = json_decode($vgone['details'],true);
            $path = $path01.md5($vgone['id'])."/";
            if (!is_dir($path)) { mkdir($path); }  // 判断文件夹存在

            $newds = [];
            foreach ($vgone['details'] as $done){
                $done = explode("@",$done)[0];
                $filename = self::makeGuid().'.jpg';
                $ex1 = self::remoteCopyToLocal($done,$path.$filename);
                $tmp_path = $tmp_path01.md5($vgone['id'])."/";
                if($ex1){
                    $remotestatus = file_remote_upload($tmp_path.$filename,false);
                    unlink($path.$filename);
                    if ($remotestatus === true) {
                        $newds[] = $tmp_path.$filename;
                    }
                }
            }

            $newds = json_encode($newds);
            $ex3 = VmeiGoods::where('id',$vgone['id'])->update(['detail' => $newds]);
            var_dump($ex3);
            rmdir($path);
        }
    }


    /**
     * @desc 替换产品主图为本地域名图片(可选用)
     */
    public function repProImg(){

        // 为使用微擎方法 被迫使用全局变量
        global $_W;
        include_once base_path()."/../../framework/function/file.func.php";
        set_time_limit(0);
        @ini_set('memory_limit','256M');
        if(
            !isset($_W['setting']['remote']['type'])
            && isset($_W['setting']['remote'][1]['type'])
        ){
            $_W['setting']['remote'] = $_W['setting']['remote'][1];
            $_W['setting']['remote']['type'] = 3;
        }

        $tmp_path = "images/store/goods/";
        $path = ATTACHMENT_ROOT.$tmp_path;

        $vg = VmeiGoods::where('thumb','')->select('id','pictureUrl')->orderBy('id','asc')->offset(0)->limit(250)->get()->toArray();
        foreach ($vg as $vgone){
            $imgu = 'http://img01.vmei.com/'.$vgone['pictureUrl'].'@320w';

            $filename = self::makeGuid().'.jpg';
            $ex1 = self::remoteCopyToLocal($imgu,$path.$filename);
            var_dump($ex1);
            if($ex1){
                $remotestatus = file_remote_upload($tmp_path.$filename,false);
                unlink($path.$filename);
                var_dump($remotestatus);

                if ($remotestatus === true) {
                    $thumb = $tmp_path.$filename;
                    $ex3 = VmeiGoods::where('id',$vgone['id'])->update(['thumb' => $thumb]);
                    var_dump($ex3);
                }

            }
        }

    }


    /**
     * @desc 获取一级和二级分类
     */
    public function getFirstCategory($fid){
        $fcs = VmeiCategory::where('parent_id',$fid)->get()->toArray();
        foreach ($fcs as $fcone){
            for ($i=0;$i<=100;$i++){
                $c1 = $this->toGetProductsList($fid,$fcone['id'],$fcone['vmei_id'],$i);
                var_dump($c1);
                if($c1 === false){
                    break;
                }
            }
        }

    }


    /**
     * @desc 根据分类获取产品列表
     */
    private function toGetProductsList($fid,$sid,$vid,$page){
        $url = 'http://m.vmei.com/products/page?cid='.$vid.'&page='.$page;
        $json = file_get_contents($url);
        var_dump(strlen($json));

        $arr = json_decode($json,true);
        var_dump(count($arr['data']['products']));

        if(count($arr['data']['products']) != 0){
            $pro = $arr['data']['products'];
            foreach ($pro as $one){
                 $cpone = $one;
                 $cpone['vmei_id'] = $cpone['id'];
                 $cpone['createdtime'] = time();
                 $cpone['first_category_id'] = $fid;
                 $cpone['second_category_id'] = $sid;
                 $cpone['vmei_category_id'] = $vid;
                 unset($cpone['id']);
                 unset($cpone['compareCurrency']);
                 unset($cpone['sellCurrency']);
                 unset($cpone['activityType']);
                 unset($cpone['highlightName']);

                 // 根据vmei_id判断表里面是否有记录了
                 $ex1 = VmeiGoods::where('vmei_id',$cpone['vmei_id'])->first();
                 if($ex1){
                     echo "已存在\r\n\r\n";
                     continue;
                 }else{
                     $vg = new VmeiGoods();
                     $vg->fill($cpone);
                     $ex2 = $vg->save();
                     var_dump($ex2);
                 }
            }
            return true;
        }else{
            return false;
        }
    }



    /**
     * @desc 生成随机不重复32位ID
     */
    private static function makeGuid(){
        mt_srand((double)microtime()*10000);
        $charid = md5(uniqid(rand(),true));
        return strtoupper($charid);
    }


    /**
     * @desc 转换字符串格式
     */
    private static function query_convert_encoding($textContent){
        return mb_convert_encoding($textContent,'ISO-8859-1','utf-8');
    }


    /**
     * @desc  简单得从远程连接下载文件
     * @param $url      string 远程url连接
     * @param $filepath string 下载到本地路径
     * @param $timeout  int    超时时间
     * @return boolean
     */
    private static function remoteCopyToLocal($url,$filepath,$timeout=60){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $temp = curl_exec($ch);
        if (@file_put_contents($filepath, $temp) && !curl_error($ch)) {
            curl_close($ch);
            return true;
        } else {
            curl_close($ch);
            return false;
        }
    }


}



