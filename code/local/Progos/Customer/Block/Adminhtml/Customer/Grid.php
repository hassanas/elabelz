<?php
class Progos_Customer_Block_Adminhtml_Customer_Grid extends Mage_Adminhtml_Block_Customer_Grid {

    protected function _preparePage()
    {
        if (Mage::getStoreConfig('customeruniversalpassword/general/addCustomerStatus')) {
            $this->getCollection()->addAttributeToSelect('customerstatus');
        }
        return parent::_preparePage();
    }
    protected function _prepareColumns()
    {
        if (Mage::getStoreConfig('customeruniversalpassword/general/addCustomerStatus')) {
            $customerStatus = Mage::getSingleton('marketplace/status_status')->getOptionArray();
            $this->addColumnAfter('customerstatus', array(
                'header' => Mage::helper('customer')->__('Customer Status'),
                'index' => 'customerstatus',
                'type' => 'options',
                'options' => $customerStatus,
            ), 'email');
        }
        return parent::_prepareColumns();
    }
}