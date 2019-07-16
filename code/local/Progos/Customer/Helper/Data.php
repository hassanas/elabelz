<?php
/**
 * Created by PhpStorm.
 * User: Hassan Ali Shahzad
 * Date: 28/09/2017
 * Time: 17:56
 */

class Progos_Customer_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_CUSTOMER_RECENTORDERS_ACTIVE  = 'customer/recent_orders/active';
    const XML_PATH_CUSTOMER_RECENTORDERS_OPEN_LINK_NEW_TAB  = 'customer/recent_orders/open_link_new_tab';
    //configurable action to activate recent order by default
    public function isActiveTabRecentOrders()
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_CUSTOMER_RECENTORDERS_ACTIVE);
    }
    //configurable action to open order link in new tab
    public function isOpenLinkNewTab()
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_CUSTOMER_RECENTORDERS_OPEN_LINK_NEW_TAB);
    }
}