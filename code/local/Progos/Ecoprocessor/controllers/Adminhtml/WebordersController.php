<?php

/**
 * This controller is created to complete the orders from Web
 * @category      Progos
 * @package       Progos_Ecoprocessor
 * @copyright     Progos TechCopyright (c) 13-02-2018
 * @author        Saroop Chand
 */
class Progos_Ecoprocessor_Adminhtml_WebordersController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return true;
    }
    /*
     * index function
     *
     * */
    public function indexAction()
    {

        $this->loadLayout();
        $this->_title($this->__('Process Web Orders'));
        $this->renderLayout();
    }
    /*
     * responsible to render grid layout
     *
     * */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('ecoprocessor/adminhtml_weborder_grid')->toHtml()
        );
    }

    /**
     * This function will process pending orders
     *
     * @access public
     * @return void
     *
     */
    public function runwebOrdersProcessAction()
    {
        $weborderIds = $this->getRequest()->getParam('weborder');
        if (!is_array($weborderIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('ecoprocessor')->__('Please select Web orders.')
            );
            $this->_redirect('*/*/index');
        } else {
            $model = Mage::getModel('ecoprocessor/ecoprocessor');
            $count = $model->placeOrders( $weborderIds );

            $this->_getSession()->addSuccess(
                $this->__('Total of %d web orders were successfully updated.', $count)
            );
            $this->_redirect('*/*/index');
        }
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper($helper = null)
    {
        return ($helper == null) ? Mage::helper('ecoprocessor') : Mage::helper($helper);
    }
}