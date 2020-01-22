<?php
use Illuminate\Database\Eloquent\Model;

class VmeiGoods extends Model{

    public $table = "yz_vmei_goods";
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
            'vmei_id' => 'vmei_id',
            'name' => 'name',
//            'pictureUrl' => 'pictureUrl',
            'thumb' => 'thumb',
            'sell' => 'sell',
//            'commentCount' => 'commentCount',
//            'score' => 'score',
            'discount' => 'discount',
//            'storageNumber' => 'storageNumber',
            'price' => 'price',
            'marketPrice' => 'marketPrice',
            'primaryPrice' => 'primaryPrice',
//            'comparePrice' => 'comparePrice',
//            'displayPrice' => 'displayPrice',
//            'brand_id' => 'brand_id',
            'first_category_id' => 'first_category_id',
            'second_category_id' => 'second_category_id',
            'vmei_category_id' => 'vmei_category_id',
//            'createdtime' => 'createdtime',
            'brand_name' => 'brand_name',
            'details' => 'details',
            'description' => 'description',
            'size' => 'size',
            'attribute' => 'attribute',
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
     * 搜索名称关键字
     */
    public static function BySearch($keywords){
        $list = self::select('id','name','thumb','sell','marketPrice');
        $where = ' `group_id` = 0 and ';
        foreach($keywords as $keyone){
            // $where .= ' name like "%'.$keyone.'%" or ';
            $where .= ' name like "%'.$keyone.'%" and ';
        }
        // $where = substr($where,0,-4);
        $where = substr($where,0,-5);
        return $list->whereRaw($where)->orderBy('sell','desc')
            ->orderBy('id','desc')
            ->paginate(10)->toArray();
    }


    /**
     * 搜索名称关键字和机构id
     */
    public static function BySearchAndGroupID($keywords,$group_id){
        $list = self::select('id','name','thumb','sell','marketPrice','goods_id');
        $where = ' ( `group_id` = 0 or `group_id` = '.intval($group_id).' ) and ';
        foreach($keywords as $keyone){
            $where .= ' `name` like "%'.$keyone.'%" and ';
        }
        return $list->whereRaw(substr($where,0,-5))->orderBy('sell','desc')
            ->orderBy('id','desc')
            ->paginate(10)->toArray();
    }

    /**
     * 获取单个
     */
    public static function getOne($goods_id,$return_level=1){
        if($return_level == 1){
            return self::select('id','name','thumb','sell','price','marketPrice','goods_id')->where('id',intval($goods_id))->first();
        }elseif($return_level == 2){
            return self::select('id','name','thumb','sell','price','marketPrice','description','goods_id')->where('id',intval($goods_id))->first();
        }else{
            return self::select('*')->where('id',intval($goods_id))->first();
        }
    }



    /**
     * getByAnyFilter
     */
    public static function getByAnyFilter($filter=[]){

        $list =  self::select('id','vmei_id','name','thumb','sell','price','primaryPrice','marketPrice','first_category_id','second_category_id','brand_name');
        // 一级分类
        if(isset($filter['first_category_id']) && $filter['first_category_id'] != 0){
            $list = $list->where('first_category_id',$filter['first_category_id']);
        }
        // 二级分类
        if(isset($filter['second_category_id']) && $filter['second_category_id'] != 0){
            $list = $list->where('second_category_id',$filter['second_category_id']);
        }
//        // 搜索关键字
//        if(isset($filter['keyword']) && $filter['keyword'] != ""){
//            $list = $list->where('name', 'like', '%' . $filter['keyword'] . '%');
//        }
        return $list->orderBy('id','desc')->paginate(20);
    }




}


