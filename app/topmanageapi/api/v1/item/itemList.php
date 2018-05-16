<?php
/**
 * topapi
 *
 * -- item.list
 * -- 商品列表
 *
 * @copyright  Copyright (c) 2005-2016 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topmanageapi_api_v1_item_itemList implements topmanageapi_interface_api{

    /**
     * 接口作用说明
     */
    public $apiDescription = '商品列表';

    /**
     * 定义API传入的应用级参数
     * @desc 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入，并且定义参数的数据类型，参数是否必填，参数的描述
     * @return array 返回传入参数
     */
    public function setParams()
    {
        return [
            //'shop_id'  => ['type'=>'string', 'valid'=>'required', 'example'=>'1',   'desc'=>'商家id', 'msg'=>'shop_id不存在'],
            'page_size'    => ['type'=>'integer', 'valid'=>'', 'example'=>'10', 'desc'=>'每页显示商品数', 'msg'=>''],
            'pages_no'     => ['type'=>'integer', 'valid'=>'','desc'=>'页数'],
            'status'       => ['type'=>'string','valid'=>'in:onsale,instock,oversku','description'=>'商品状态']
            //'shop_cat_id' => ['type'=>'string','valid'=>'','description'=>'商家自定义分类id']
        ];
    }

    /**
     * @return array 商品列表
     */
    public function handle($params)
    {
        //获取商品列表
        $result = kernel::single('topmanageapi_item_itemListinfo')->getItemlist($params);
        return $result;
    }

}

