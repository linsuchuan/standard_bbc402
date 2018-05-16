<?php
/**
 * @copyright  Copyright (c) 2005-2016 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topmanageapi_item_itemListinfo {

    /**
     * 获取商品
     *
     * @param int $shop_id 店铺Id
     * @param string $page_no 当前页数
     * @param string $page_size 每页显示商品数
     *
     * @return string $itemList
     */
    public function getItemlist($params)
    {
        $shop_id = $params['oAuthInfo']['shop_id'];
        $page_no =  $params['pages_no'] ? $params['pages_no']:'1';
        $page_size =  $params['page_size'] ? $params['page_size']:'10';
        $status = $params['status'] ? $params['status'] : '';
        $filter['shop_id'] = $shop_id;
        $filter['page_no'] = intval($page_no);
        $filter['page_size'] = intval($page_size);
        //适配商品搜索
        if($params['item_title'] >= 0)
        {
            $filter['search_keywords'] = $params['item_title'];
        }
        if($params['min_price'] && $params['max_price'])
        {
            $filter['min_price'] = $params['min_price'];
            $filter['max_price'] = $params['max_price'];
        }
        if($params['use_platform'] >= 0)
        {
            $filter['use_platform'] = $params['use_platform'];
        }
        if($params['item_cat'] && $params['item_cat'] > 0)
        {
            $filter['search_shop_cat_id'] = (int)$params['item_cat'];
        }
        if($params['item_no'])
        {
            $filter['bn'] = $params['item_no'];
        }
        if($params['dlytmpl_id']&&$params['dlytmpl_id']>0)
        {
            $filter['dlytmpl_id'] = $params['dlytmpl_id'];
        }
        //end
        if (!empty($status)) {
            $filter['approve_status'] = $status;
        }
        $filter['fields'] = 'item_id,list_time,modified_time,title,image_default_id,approve_status,price,store,dlytmpl_id,nospec,use_platform,cat_id';
        $filter['orderBy'] = 'modified_time desc';
        //库存报警的商品列表
        if ($status == 'oversku') 
        {
            $params['shop_id'] = $shop_id;
            $params['fields'] = 'policevalue';
            $storePolice = app::get('topshop')->rpcCall('item.store.info',$params);
            $filter['store'] = $storePolice['policevalue']?$storePolice['policevalue']:0;
            $itemsList = app::get('topshop')->rpcCall('item.store.police',$filter);
        }else{
            $itemsList = app::get('topshop')->rpcCall('item.search',$filter);
        }
        //分页
        $result['item_list'] = $itemsList['list'];
        $result['total'] = $itemsList['total_found'];
        $result['totalPage'] = ceil($itemsList['total_found']/$page_size);
        $result['pager_no'] = $page_no;
        return $result;
    }

    /**
     * @AuthorHTL
     *
     * 获取店铺快递模板
     * 
     * @DateTime  2017-08-01T17:39:19+0800
     * @param     int $shop_id 店铺Id
     * @return    string $dlyList 快递模板信息
     */
    public function getDlytlist($shop_id){
        $tmpParams = array(
            'shop_id' => $shop_id,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        return $dlyList = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
    }

    /**
     * @AuthorHTL
     * 
     * 获取店铺分类
     * 
     * @DateTime  2017-08-01T17:42:19+0800
     * @param     int $shop_id 店铺id
     * @return    string $itemCatlist 分类信息
     */
    public function getItemcatList($shop_id){
        $params['shop_id'] = $shop_id;
        return $itemCatlist = app::get('topshop')->rpcCall('shop.cat.get', $params);
    }
}

