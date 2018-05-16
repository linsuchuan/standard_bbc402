<?php
/**
 * topapi
 *
 * -- item.detail
 * -- 商品详情
 *
 * @copyright  Copyright (c) 2005-2016 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topmanageapi_api_v1_item_itemDetail implements topmanageapi_interface_api{

    /**
     * 接口作用说明
     */
    public $apiDescription = '商品详情';

    /**
     * 定义API传入的应用级参数
     * @desc 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入，并且定义参数的数据类型，参数是否必填，参数的描述
     * @return array 返回传入参数
     */
    public function setParams()
    {
        return [
            'item_id'    => ['type'=>'integer', 'valid'=>'required|integer|min:1', 'example'=>'1', 'desc'=>'商品id，必须为正整数', 'msg'=>'']
        ];
    }

    /**
     * @return array 商品详情
     */
    public function handle($params)
    {
        $shop_id = $params['oAuthInfo']['shop_id'];
        $itemparams['item_id'] = intval($params['item_id']);
        $itemparams['shop_id'] = $shop_id;
        $itemparams['fields']  = "*,sku,item_store,item_status,item_count,item_desc,item_nature,spec_index";
        $result['item'] = app::get('topshop')->rpcCall('item.get',$itemparams);
        // 商家分类及此商品关联的分类
        $scparams['shop_id'] = $shop_id;
        $scparams['fields'] = 'cat_id,cat_name,is_leaf,parent_id,level';
        $result['shopCatList'] = app::get('topshop')->rpcCall('shop.cat.get',$scparams);
        $result['dlytmpls'] = kernel::single('topmanageapi_item_itemListinfo')->getDlytlist($shop_id);
        return $result;
    }
}