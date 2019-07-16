<?php
/*@author: Humera Batool
@ Created at : 16 october 2017
The observer was created so that when an item is removed or rejected it must returm store credit*/

class Progos_Storecredit_Model_Observer_Storecredit extends AW_Storecredit_Model_Observer_Storecredit
{
    public function salesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('aw_storecredit')->isModuleOutputEnabled()) {
            return $this;
        }

        if (!Mage::helper('aw_storecredit/config')->isModuleEnabled()) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();

        if(!$order->getCustomerId()) {
            return $this;
        }

        $quoteStorecreditItem = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
        if (count($quoteStorecreditItem) > 0) {
            foreach ($quoteStorecreditItem as $storecredit) {
                $storecreditModel = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($order->getCustomerId());
                if (!$storecreditModel->getEntityId()) {
                    continue;
                }

                /*
                 * Code added for mobile app
                 */
                $sendEmail = false;
                $mdlRestmob = Mage::getModel('restmob/quote_index');
                $id = $mdlRestmob->getIdByQuoteId($order->getQuoteId());
                if ($id) {
                    $_order = $mdlRestmob->load($id);
                    if($_order->getStoreCredit() == 1) {
                        $sendEmail = true;
                        $storecreditModel
                            ->setOrder($order)
                            ->save();
                    }
                }else{
                    $mdlEco = Mage::getModel('ecoprocessor/quote_index');
                    $id = $mdlEco->getIdByQuoteId($order->getQuoteId());
                    if ($id) {
                        $_order = $mdlEco->load($id);
                        if($_order->getStoreCredit() == 1) {
                            $sendEmail = true;
                            $storecreditModel
                                ->setOrder($order)
                                ->save();
                        }
                    }else {
                        $sendEmail = true;
                        $storecreditModel
                            ->setOrder($order)
                            ->setBalance($storecreditModel->getBalance() - $storecredit->getBaseStorecreditAmount())
                            ->save();
                    }
                }
                if($sendEmail) {
                    $emailTemplate = Mage::getModel('aw_storecredit/email_template');
                    try {
                        $_templateData = array();
                        $_templateData['store_credit_product_bought'] = true;
                        $_templateData['customer_id'] = $order->getCustomerId();
                        $_templateData['credit_spent'] = Mage::helper('core')->currency($storecredit->getBaseStorecreditAmount(), true, false);
                        $_templateData['order_increment_id'] = $order->getIncrementId();
                        $_templateData['order_url'] = Mage::helper('aw_storecredit/url')->getCustomerOrderUrlForEmail(
                            $order->getCustomerId(), $order->getId(), $order->getStoreId()
                        );

                        $store = Mage::app()->getStore($order->getStoreId());
                        $emailTemplate->prepareEmailAndSend($_templateData, $store);
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }
        }
        return $this;
    }
}
?>