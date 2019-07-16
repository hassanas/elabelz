<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersGrid_Model_System_Config_Source_Invoice_Capturecase
{
    /**
     * @return array
     * @internal param bool|true $isMultiselect
     */
    public function toOptionArray() {

        $model = Mage::getModel('sales/order_invoice');
        $helper = Mage::helper('mageworx_ordersgrid');

        $online = $model::CAPTURE_ONLINE;
        $offline = $model::CAPTURE_OFFLINE;
        $notCapture = $model::NOT_CAPTURE;

        $options = array(
            array('value'=>$online, 'label'=>$helper->__('Online')),
            array('value'=>$offline, 'label'=>$helper->__('Offline')),
            array('value'=>$notCapture, 'label'=>$helper->__('Not Capture'))
        );

        return $options;
    }
}