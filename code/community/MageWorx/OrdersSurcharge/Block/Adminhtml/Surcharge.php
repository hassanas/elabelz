<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $helper = $this->getHelper();
        $this->_controller = 'mageworx_orderssurcharge_surcharge';
        $this->_headerText = $helper->__('Surcharges');
        $this->_addButtonLabel = $helper->__('Create New Surcharge');
        parent::__construct();
        if (!$helper->isAllowed('sales/mageworx_orderssurcharge/actions/create')) {
            $this->_removeButton('add');
        }
    }

    public function getCreateUrl()
    {
        return $this->getUrl('*/mageworx_orderssurcharge_surcharge/create');
    }

    /**
     * @return MageWorx_OrdersSurcharge_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('mageworx_orderssurcharge');
    }
}