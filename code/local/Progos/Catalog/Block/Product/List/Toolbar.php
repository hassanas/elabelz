<?php
/**
 * @author: Humera Batool
 * created_at: 9 april 2018
 * @purpose: Creating a new function for sorting which does not remove filtered filters while sorting them
 */
class Progos_Catalog_Block_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{
    public function getOrderUrlSorting($order, $direction)
    {
        if(Mage::app()->getRequest()->getParam('url')) {
            $currentUrl= Mage::app()->getRequest()->getParam('url');
            $url = Mage::getSingleton('core/url')->parseUrl(Mage::app()->getRequest()->getParam('url'));
        }
        else{
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $url = Mage::getSingleton('core/url')->parseUrl($currentUrl);
        }
        if($order == "created_at"){
            $direction = "desc";
        }
        $path = $url->getPath();
        $query_url = parse_url($currentUrl, PHP_URL_QUERY);
        parse_str($query_url, $params);

        $query['order'] = $order;
        $query['dir'] = $direction;
        if($params['price']){
            $query['price']= $params['price'];
        }
        if($params['dup']){
            $query['dup']= $params['dup'];
        }
        $query_result = http_build_query($query);
        return $path."?".$query_result;
    }

    public function isOrderCurrentBrand($order)
    {
        return ($order == $this->getCurrentOrderBrand());
    }

    public function getCurrentOrderBrand()
    {
        $orders = $this->getAvailableOrders();
        $defaultOrder = "created_at";

        if (!isset($orders[$defaultOrder])) {
            $keys = array_keys($orders);
            $defaultOrder = $keys[0];
        }

        $order = $this->getRequest()->getParam($this->getOrderVarName());
        if ($order && isset($orders[$order])) {
            if ($order == $defaultOrder) {
                Mage::getSingleton('catalog/session')->unsSortOrder();
            } else {
                $this->_memorizeParam('sort_order', $order);
            }
        } else {
            $order = Mage::getSingleton('catalog/session')->getSortOrder();
        }
        // validate session value
        if (!$order || !isset($orders[$order])) {
            $order = $defaultOrder;
        }
        $this->setData('_current_grid_order', $order);
        return $order;
    }
}