<?php
/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersEdit_Model_Resource_Order_Status_History extends Mage_Core_Model_Mysql4_Abstract {
    
    protected function _construct() 
    {        
        $this->_init('mageworx_ordersedit/order_status_history', 'entity_id');
    }
}