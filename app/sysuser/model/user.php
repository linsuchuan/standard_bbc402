<?php

class sysuser_mdl_user extends dbeav_model
{

    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        if( is_array($filter) && $filter['login_account'] )
        {
            $tmpfilter['login_account'] = $filter['login_account'];
            unset($filter['login_account']);
        }

        if( is_array($filter) &&  $filter['email'] )
        {
            $tmpfilter['email'] = $filter['email'];
            unset($filter['email']);
        }

        if( is_array($filter) &&  $filter['mobile'] )
        {
            $tmpfilter['mobile'] = $filter['mobile'];
            unset($filter['mobile']);
        }

        if( is_array($filter) &&  $tmpfilter )
        {
            $aData = app::get('sysuser')->model('account')->getList('user_id',$tmpfilter);
            if($aData)
            {
                foreach($aData as $key=>$val)
                {
                    $user[$key] = $val['user_id'];
                }
                $filter['user_id'] = $user;
            }
            else
            {
                $filter['user_id'] = '-1';
            }
        }
        $filter = parent::_filter($filter);
        return $filter;
    }

    /**
     * 重写搜索的下拉选项方法
     * @param null
     * @return null
     */
    public function searchOptions(){
        $columns = array();
        foreach($this->_columns() as $k=>$v)
        {
            if(isset($v['searchtype']) && $v['searchtype'])
            {
                $columns[$k] = $v['label'];
            }
        }

        $columns = array_merge(array(
            'login_account'=>app::get('sysuser')->_('用户名'),
            'email'=>app::get('sysuser')->_('邮箱'),
            'mobile'=>app::get('sysuser')->_('手机'),
        ),$columns);

        $columns['userTag'] = app::get('sysuser')->_('标签');

        return $columns;
    }


    public function doDelete($userIds)
    {
        $objCheck = kernel::single('sysuser_check');
        $objMdlUser = app::get('sysuser')->model('user');
        $objMdlPamUser = app::get('sysuser')->model('account');
        $objMdlTrustInfo = app::get('sysuser')->model('trustinfo');
        try
        {
            $result = $objCheck->checkDelete($userIds);
            $result = $objMdlUser->delete(array('user_id'=>$userIds));
            if(!$result)
            {
                $msg = "删除会员基本信息失败";
                throw new \LogicException($msg);
            }
            $result = $objMdlPamUser->delete(array('user_id'=>$userIds));
            if(!$result)
            {
                $msg = "删除会员登录信息失败";
                throw new \LogicException($msg);
            }
            $trustInfo = $objMdlTrustInfo->getList('user_id',array('user_id'=>$userIds));
            if($trustInfo)
            {
                $result = $objMdlTrustInfo->delete(array('user_id'=>$userIds));
                if(!$result)
                {
                    $msg = "删除会员登录信息失败";
                    throw new \LogicException($msg);
                }
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            throw new \LogicException($msg);
            return false;
        }
        return true;
    }

    /**
     * 重写getList方法，默认按照注册时间排序
     * @param string $cols 设置要取哪些列的数据
	 * @param array $filter 过滤条件,默认为array()
	 * @param integer $offset 偏移量,从select出的第几条数据开始取
	 * @param integer $limit 取几条数据, 默认值为-1, 取所有select出的数据
	 * @param string/array $orderby 排序方式, 默认按照注册时间排序
	 * @return array 二维数组, 多行数据, 每行数据对应表的以行, 所取列由$cols参数控制
     * */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderBy=null)
    {

        if($orderBy == null)
        {
            $orderBy = 'regtime desc';
        }

        if(isset($filter['userTag']))
        {
            $tag = app::get('sysuser')->model('tag')->getRow('tag_id', ['tag_name'=>$filter['userTag']]);
            if($tag)
            {
                $userIds = kernel::single('sysuser_data_tag_index')->searchByTagId($tag['tag_id'], $offset, $offset+$limit);
                if(empty($userIds))
                    return [];
                return parent::getList($cols, ['user_id|in'=>$userIds], 0, -1, $orderBy);
            }
            else
                return [];
        }
        return parent::getList($cols, $filter, $offset, $limit, $orderBy);
    }

    function count($filter=null)
    {
        if(isset($filter['userTag']))
        {
            $tag = app::get('sysuser')->model('tag')->getRow('tag_id', ['tag_name'=>$filter['userTag']]);
            if($tag)
                return kernel::single('sysuser_data_tag_index')->countByTagId($tag['tag_id']);
            else
                return 0;
        }

        return parent::count($filter);
    }

    function modifier_birthday($birthday){
        foreach ($birthday as $key => $value) {
            if($value){
                $birthday[$key] = date('Y-m-d',$value);
            }
        }
    }
}

