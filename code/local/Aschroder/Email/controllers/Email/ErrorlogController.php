<?php

/**
 * Controller for the Log viewing operations
 *
 *
 * @author Ashley Schroder (aschroder.com)
 * @copyright  Copyright (c) 2013 ASchroder Consulting Ltd
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Aschroder_Email_Email_ErrorlogController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
                ->_setActiveMenu('system/tools')
                ->_title(Mage::helper('aschroder_email')->__('MageSend'))
                ->_title(Mage::helper('aschroder_email')->__('Email Feedback'))
                ->_addBreadcrumb(Mage::helper('aschroder_email')->__('System'), Mage::helper('aschroder_email')->__('System'))
                ->_addBreadcrumb(Mage::helper('aschroder_email')->__('Tools'), Mage::helper('aschroder_email')->__('Tools'))
                ->_addBreadcrumb(Mage::helper('aschroder_email')->__('Email Feedback'), Mage::helper('aschroder_email')->__('Email Feedback'));
        return $this;
    }

    public function indexAction() {

        $this->_initAction()
                ->_addContent($this->getLayout()->createBlock('aschroder_email/errorlog'))
                ->renderLayout();
    }

    /*
    * This action is the initial SNS topic creation/subscription
    *
    */
    public function createAction() {
        $_helper = Mage::helper('aschroder_email');

        if ($_helper->isLogBounceEnabled()) {
            try {
                $_helper->createTopic();
                Mage::getSingleton('adminhtml/session')->addSuccess("SNS setup complete. Run a test to check the connection");
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError("Could not setup SNS notifications: " . $e->getMessage());
            }

        } else {
            Mage::getSingleton('adminhtml/session')->addError("Could not setup SNS notifications. Please enable Bounce Logging first.");
        }

        $this->_redirectReferer();
    }

    /*
     * This action is sends test emails that cause notifications
     *
     */
    public function testAction() {
        $_helper = Mage::helper('aschroder_email');
        if ($_helper->isLogBounceEnabled()) {
            $_helper->sendBounceTest();
            $_helper->sendComplaintTest();
            $_helper->sendOOTOTest();
            $_helper->sendDeliveryTest();
        }
        $_helper->log("Sent Feedback test emails");
        $this->_redirectReferer();
    }

  	/**
  	 * Check is allowed access to action
  	 *
  	 * @return bool
  	 */
  	protected function _isAllowed() {
  		return Mage::getSingleton('admin/session')->isAllowed('admin/system/tools/aschroder_email_error');
  	}

}
