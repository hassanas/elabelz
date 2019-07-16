<?php
/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersSurcharge_Model_Observer_Abstract
{
    /**
     * Get module helper
     *
     * @return MageWorx_OrdersSurcharge_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('mageworx_orderssurcharge');
    }
}