<?php
use models\WeiboKeywords;
use models\WeibospiderContent;
use models\WeibospiderPeople;

/**
 * @desc 微博的爬虫
 * @see  https://blog.csdn.net/macwhirr123/article/details/78258295
 * @see  https://blog.csdn.net/edward_liang_/article/details/50174507
 */
class Weibo{

    /**
     * 入口方法
     */
    public static function start(){

        $wk = WeiboKeywords::where('id','>',0)->whereRaw(' `now_page` < `want_page` ')->first();
        if($wk){
            $url = 'http://sinanews.sina.cn/interface/type_of_search.d.html?callback=&keyword='.$wk->keyword.'&page='.($wk->now_page+1).'&type=siftWb&size=50&newpage=0&chwm=&imei=&token=&did=&from=&oldchwm=';

            $content = self::toHttpSinaWeiBoVideo($url);
            $json = json_decode($content,true);

            if($content && $json && $json['status'] == 0 && $json['msg'] == 'success'){
                $data = $json['data']['feed1'];
                foreach ($data as $done){
                    print_r($done);
                    $c1 = WeibospiderPeople::where('weibo_uid',$done['user']['id'])->first();
                    $done['user']['weibo_uid'] = $done['user']['id'];
                    if(!$c1){
                        $wsp = new WeibospiderPeople();
                        unset($done['user']['id']);
                        $wsp->fill($done['user']);
                        $wsp->save();
                    }

                    $wsc = new WeibospiderContent();
                    $done['weibo_uid'] = $done['user']['weibo_uid'];
                    $done['weibo_name'] = $done['user']['name'];
                    unset($done['user']);
                    // print_r($done);die;
                    $wsc->fill($done);
                    $wsc->save();
                }

                $wk->now_page = $wk->now_page + 1;
                // die;
                $wk->save();
            }
        }
    }


    /**
     * 伪装请求微博服务器的http请求
     */
    private static function toHttpSinaWeiBoVideo($url){
        // 伪装useragent
        $UA = "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36";
        // 伪装请求头
        $header = [
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "Accept-Encoding" => "gzip, deflate, br",
            "Accept-Language" => "zh-CN,zh;q=0.9",
            "Cache-Control" => "max-age=0",
            "Connection" => "keep-alive",
            "Host" => "weibo.com",
            "Upgrade-Insecure-Requests" => 1,
            "User-Agent" => $UA
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回原生的（Raw）输出
        curl_setopt($ch, CURLOPT_HEADER, 0);            // 获取头部信息 debug
        curl_setopt($ch, CURLOPT_USERAGENT, $UA);

        curl_setopt($ch, CURLOPT_URL, $url);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        //返回结果
        if ($content && $httpcode == 200) {
            return $content;
        } else {
            return null;
        }
    }


}



