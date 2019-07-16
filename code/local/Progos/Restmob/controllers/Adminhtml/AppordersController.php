<?php

/**
 * This controller is created to complete the orders from App
 * working on Eid
 * @category      Progos
 * @package       Progos_Restmob
 * @copyright     Progos TechCopyright (c) 01-09-2017
 * @author        Hassan Ali Shahzad
 */
class Progos_Restmob_Adminhtml_AppordersController extends Mage_Adminhtml_Controller_Action
{
    /*
     * For patch SUPEE-6285 mandatory for custom modules
     *
     * */
    public $counter = 0;
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('marketplace/restmob');
    }
    /*
     * index function
     *
     * */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__('Process App Orders'));
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
            $this->getLayout()->createBlock('restmob/adminhtml_mobileapporder_grid')->toHtml()
        );
    }

    /**
     * This function will process pending orders
     *
     * @access public
     * @return void
     *
     */
    public function runAppOrdersProcessAction()
    {
        $mobileapporderIds = $this->getRequest()->getParam('mobileapporder');
        if (!is_array($mobileapporderIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('restmob')->__('Please select mobile app orders.')
            );
        } else {
            $model = Mage::getModel('emapi/emapi');
            $this->counter = $model->placeOrders( $mobileapporderIds );

            $this->_getSession()->addSuccess(
                $this->__('Total of %d mobile app orders were successfully updated.', $this->counter)
            );
            $this->_redirect('*/*/index');
        }
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper($helper = null)
    {
        return ($helper == null) ? Mage::helper('restmob') : Mage::helper($helper);
    }

}