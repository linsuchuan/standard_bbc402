<?php
/**
 * topapi
 *
 * -- item.search
 * -- 商品搜索
 * @copyright  Copyright (c) 2005-2016 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topmanageapi_api_v1_item_itemSearch implements topmanageapi_interface_api{

    /**
     * 接口作用说明
     */
    public $apiDescription = '商品搜索';

    /**
     * 定义API传入的应用级参数
     * @desc 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入，并且定义参数的数据类型，参数是否必填，参数的描述
     * @return array 返回传入参数
     */
    public function setParams()
    {
        return [
            'use_platform'  => ['type'=>'integer', 'valid'=>'' , 'example'=>'1','desc'=>'发布终端,pc端和wap端(0), pc端(1),wap端(2),不填则为-1'],
            'item_title'    => ['type'=>'string', 'valid'=>'', 'desc'=>'商品名称'],
            'item_no'       => ['type'=>'string','valid'=>'', 'example'=>'','desc'=>'商品货号'],
            'item_cat'      => ['type'=>'integer','valid'=>'','example'=>'','desc'=>'商品分类'],
            'min_price'     => ['type'=>'numeric','valid'=>'','example'=>'','desc'=>'最小价格'],
            'max_price'     => ['type'=>'numeric','valid'=>'','example'=>'','desc'=>'最大价格'],
            'dlytmpl_id'    => ['type'=>'integer','valid'=>'','example'=>'','desc'=>'运费模板id'],
            'page_size'     => ['type'=>'numeric', 'valid'=>'required', 'example'=>'10', 'desc'=>'每页显示商品数', 'msg'=>''],
            'pages_no'      => ['type'=>'numeric', 'valid'=>'required','desc'=>'页数'],
            'status'       => ['type'=>'string','valid'=>'in:onsale,instock,oversku','description'=>'商品状态']
        ];
    }

    /**
     * @return array 商品列表
     */
    public function handle($params)
    {
        if($params['min_price']&&$params['max_price'])
        {
            if($params['min_price']>$params['max_price'])
             {
                $msg = app::get('topshop')->_('最大值不能小于最小值！');
                throw new \LogicException(app::get('topmanageapi')->_($msg));
            }
        }
        //获取商品列表
        $result = kernel::single('topmanageapi_item_itemListinfo')->getItemlist($params);
        return  $result;
    }

}

