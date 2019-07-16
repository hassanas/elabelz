<?php

/**
 * @Author Hassan Ali Shahzad
 * @Date 20-06-2017
 *
 */
class Progos_Skuvault_Adminhtml_BrandController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $helper = Mage::helper('progos_skuvault');
        //$response = $helper->runAdminBrandSync(); // disabled this call right now No need for brands update on skuvault
        $response = $helper->runAdminBrandSyncWithSupplier();
        if ($response) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('shopbybrand')->__('All brands are synchronized with SkuVault Suppliers')
            );
        } else {
            Mage::getSingleton('adminhtml/session')->addNotice(
                Mage::helper('shopbybrand')->__('Some brands could not be synchronized. Please check log file')
            );
        }
        $this->renderLayout();
        $this->_redirect('*/shopbybrand_brand');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }


}