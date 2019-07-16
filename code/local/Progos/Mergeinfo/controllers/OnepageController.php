<?php
/*
 * @author     Hassan Ali Shahzad
 * @package    Progos_Mergeinfo
 * Date    26-05-2017
 */
require_once "Phxsolution/Mergeinfo/controllers/OnepageController.php";

class Progos_Mergeinfo_OnepageController extends Phxsolution_Mergeinfo_OnepageController
{

    public function postDispatch()
    {
        parent::postDispatch();
        Mage::dispatchEvent('controller_action_postdispatch_adminhtml', array('controller_action' => $this));
    }

    /**
     * This function is overrided to switch between payment methods
     *
     */
    protected function updateAction()
    {
        $methodCode = $this->getRequest()->getPost('newmethod');
        if (isset($methodCode)) {
            Mage::getModel('progos_mergeinfo/paymentMethodSwitcher')->changePaymentMethodTo($methodCode);
        }
        $result['update_section'] = array(
            'html' => $this->_getReviewHtml2()
        );
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * This function will update the Store credits to the Quote in session
     */
    public function applyStoreCreditsAction(){
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if($quote) {
            if (count(Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quote->getId())) == 0) {
                $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                if ($storeCredit) {
                    $quote->setStorecreditInstance($storeCredit);
                    Mage::helper('aw_storecredit/totals')->addStoreCreditToQuote($storeCredit, $quote);
                }
            }
        }
    }

}