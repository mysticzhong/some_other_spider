<?php
use Illuminate\Database\Eloquent\Model;

class VmeiCategory extends Model{

    public $table = "yz_vmei_category";
    public $timestamps = false;
    protected $guarded = [''];

    //默认值
    public $attributes = [
    ];


    /**
     * 自定义字段名
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


    /**
     * 获取二级分类格式化数据
     */
    public static function getSecondCates(){
        return self::select('id','name','thumb')
            ->where('parent_id', '<>', 0)
            ->get()->keyBy('id')->toArray();
    }


    /**
     * 获取一级分类格式化数据
     */
    public static function getFirstCates(){
        return self::select('id','name','thumb')
            ->where('parent_id', 0)
            ->get()->keyBy('id')->toArray();
    }



}


