<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_View_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Retrieve available surcharge
     *
     * @return MageWorx_OrdersSurcharge_Model_Surcharge
     */
    public function getSurcharge()
    {
        if ($this->hasSurcharge()) {
            return $this->getData('surcharge');
        }
        if (Mage::registry('current_surcharge')) {
            return Mage::registry('current_surcharge');
        }
        if (Mage::registry('surcharge')) {
            return Mage::registry('surcharge');
        }
        Mage::throwException(Mage::helper('mageworx_orderssurcharge')->__('Cannot get the surcharge instance.'));
    }

    public function __construct()
    {
        parent::__construct();
        $this->setId('surcharge_view_tabs');
        $this->setDestElementId('surcharge_view_form');
        $this->setTitle(Mage::helper('mageworx_orderssurcharge')->__('Surcharge View'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('info', array(
            'label'     => Mage::helper('mageworx_orderssurcharge')->__('Surcharge Information'),
            'content'   => $this->getLayout()->createBlock('mageworx_orderssurcharge/adminhtml_surcharge_view_tab_info')->initForm()->toHtml(),
            'active'    => Mage::registry('current_surcharge')->getId() ? false : true
        ));

        $this->_updateActiveTab();
        return parent::_beforeToHtml();
    }

    protected function _updateActiveTab()
    {
        $tabId = $this->getRequest()->getParam('tab');
        if( $tabId ) {
            $tabId = preg_replace("#{$this->getId()}_#", '', $tabId);
            if($tabId) {
                $this->setActiveTab($tabId);
            }
        }
    }
}