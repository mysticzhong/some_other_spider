<?php
use Illuminate\Database\Eloquent\Model;

class WeiboKeywords extends Model{

    public $table = "weibospider_keywords";
    protected $guarded = [''];
    protected $appends = [];
    public $timestamps = false;


    /**
     * 自定义字段名
     * 可使用
     * @return array
     */
    public function atributeNames(){
        return [
        ];
    }


    /**
     * 字段规则
     * @return array
     */
    public function rules(){
        return [
        ];
    }

}



