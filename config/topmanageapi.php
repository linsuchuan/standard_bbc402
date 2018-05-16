<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

return array(
    //  'region'=>'地区',
    //  'logistics'=>'物流',
    //  'theme'=>'模板',
    //  'category'=>'品牌类目',
    //  'user'=>'账户',
    //  'member'=>'会员',
    //  'trade'=>'交易',
    //  'item'=>'商品',
    //  'cart'=>'购物车',
    //  'promotion'=>'促销',
    //  'payment'=>'支付',
    //  'shop'=>'店铺',
    //  'content'=>'内容管理',
    //  'common'=>'通用',
    //  'image'=>'图片',
    //  'message'=>'消息',

    /*
    |--------------------------------------------------------------------------
    | 定义所有topapi api接口路由
    |--------------------------------------------------------------------------
    | v1 表示API版本号
    | theme.modules API调用方法
    | topapi_api_v1_theme_modules API实现类默认调用handle方法
     */
    'routes' => array(
        'v1' => [
            'common.example' => ['uses' => 'topmanageapi_api_v1_example'],
            'common.example2' => ['uses' => 'topmanageapi_api_v1_example2', 'auth'=>true],
            'user.login' => ['uses' => 'topmanageapi_api_v1_account_login'],
            'item.list' => ['uses' => 'topmanageapi_api_v1_item_itemList','auth'=>true],
            'item.save' => ['uses' => 'topmanageapi_api_v1_item_create','auth'=>true],
            'item.detail' => ['uses' => 'topmanageapi_api_v1_item_itemDetail','auth'=>true],
            'item.status' => ['uses' => 'topmanageapi_api_v1_item_itemStatus','auth'=>true],
            'item.delete' => ['uses' => 'topmanageapi_api_v1_item_itemDelete','auth'=>true],
            'item.search' => ['uses' => 'topmanageapi_api_v1_item_itemSearch','auth'=>true],
            'item.create' => ['uses' => 'topmanageapi_api_v1_item_create','auth'=>true],

            'category.platform.get' => ['uses' => 'topmanageapi_api_v1_category_getPlatformCategory','auth'=>true],
            'category.platform.natureprops.get' => ['uses' => 'topmanageapi_api_v1_category_getPlatformCategoryNatureProps','auth'=>true],
            'category.platform.brand.get' => ['uses' => 'topmanageapi_api_v1_category_getPlatformCategoryBrand','auth'=>true],
            'category.platform.props' => ['uses' => 'topmanageapi_api_v1_category_getPlatformCategoryProps','auth'=>true],
            'category.platform.params' => ['uses' => 'topmanageapi_api_v1_category_getPlatformCategoryParams','auth'=>true],
            'category.shop.get' => ['uses' => 'topmanageapi_api_v1_category_getShopCategory','auth'=>true],
            'dlytmpl.shop.list' => ['uses' => 'topmanageapi_api_v1_dlytmpl_list','auth'=>true],
            'image.upload' => ['uses' => 'topmanageapi_api_v1_image_upload','auth'=>true],
        ],
        'v2' => [
            'common.example' => ['uses' => 'topmanageapi_api_v1_example'],
            'common.example2' => ['uses' => 'topmanageapi_api_v1_example'],
        ],
    ),
);
