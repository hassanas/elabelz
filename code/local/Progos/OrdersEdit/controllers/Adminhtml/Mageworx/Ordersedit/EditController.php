<?php

/**
 * SOlved the issue of coupon code in edit order 
 */
require_once Mage::getModuleDir('controllers', 'MageWorx_OrdersEdit') . DS . 'Adminhtml'. DS .'Mageworx'. DS .'Ordersedit'. DS .'EditController.php';

class Progos_OrdersEdit_Adminhtml_Mageworx_Ordersedit_EditController extends MageWorx_OrdersEdit_Adminhtml_Mageworx_Ordersedit_EditController
{

    protected function init($applyNewChanges = false)
    {
        // Get base order id and load order and quote
        $orderId = $this->getRequest()->getParam('order_id');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        $this->order = $order;
        $this->origOrder = clone $order;
        Mage::register('ordersedit_order', $order);

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('mageworx_ordersedit/edit')->getQuoteByOrder($order);

        // Get id of the currently edited block
        $blockId = $this->getRequest()->getParam('block_id');
        $editedBlock = $this->getRequest()->getParam('edited_block');
        $this->blockId = $blockId ? $blockId : $editedBlock;
        Mage::register('ordersedit_block_id', $blockId);

        // Get pending changes
        $pendingChanges = $this->getMwEditHelper()->getPendingChanges($orderId);
        if ($applyNewChanges) {
            $data = $this->getRequest()->getPost();
            //Getting the rule ids are applied to order
            $ruleIds = array();
            if ($order->getAppliedRuleIds()):
                foreach (explode(",", $order->getAppliedRuleIds()) as $ruleId) {
                    $rule = Mage::getModel('salesrule/rule')->load($ruleId);
                    if (!$rule->getIsActive()):
                        $rule->setIsActive(1)->save();
                        //$ruleIds[]=$oCoupon->getRuleId();
                    endif;
                } 
            endif;
            //Checking any coupon code are applied to order and activate the coupon if it was inacitve
            $couponCode = $order->getCouponCode();
            if ($couponCode):
                $oCoupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
                $rule = Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());
                if (!$rule->getIsActive()):
                    $rule->setIsActive(1)->save();
                    $ruleIds[] = $oCoupon->getRuleId();
                endif;
            endif;
            if ($ruleIds):
                Mage::getSingleton('core/session')->setCouponOrderedit($ruleIds);
            endif;
            $pendingChanges = $this->getMwEditHelper()->addPendingChanges($orderId, $data);
            $this->order->addData($data);
        }

        $surcharge = $this->getRequest()->getParam('surcharge');
        if ($surcharge) {
            $pendingChanges['surcharge'] = $surcharge;
            $this->surchargeFlag = true;
        }

        $this->pendingChanges = $pendingChanges;
        Mage::register('ordersedit_pending_changes', $pendingChanges);

        // Update quote if pending changes exists
        if (!empty($pendingChanges)) {
            $quote = Mage::getSingleton('mageworx_ordersedit/edit_quote')->applyDataToQuote($quote, $pendingChanges);
        }
        $this->quote = $quote;
        Mage::register('ordersedit_quote', $quote);

        return $this;
    }


    protected function resetPendingChanges($orderId = null)
    {
        $orderId = $orderId ? $orderId : $this->order->getId();
        //Reset t coupon code to inactive if they are activated thoruhg this proccess
        if ($couponOrderedit = Mage::getSingleton('core/session')->getCouponOrderedit()):
            foreach ($couponOrderedit as $ruleId) {
                $rule = Mage::getModel('salesrule/rule')->load($ruleId);
                $rule->setIsActive(0)->save();
            }

            Mage::getSingleton('core/session')->unsCouponOrderedit();
        endif;
        $this->getMwEditHelper()->resetPendingChanges($orderId);
    }


    //rewriting this function so that coupon code session unset here and must not cal the function more then one time

    /**
     * Save order with changes from the quote
     * Final step
     */
    public function saveOrderAction()
    {
        $this->init();
        /** @var MageWorx_OrdersEdit_Model_Edit $editModel */
        $editModel = Mage::getSingleton('mageworx_ordersedit/edit');
        /** @var MageWorx_OrdersEdit_Model_Edit_Quote $editQuoteModel */
        $editQuoteModel = Mage::getSingleton('mageworx_ordersedit/edit_quote');

        try {

            // We can not save the order with the grand total smaller than 0
            if ($this->quote->getBaseGrandTotal() < 0) {
                throw new Exception('GT < 0');
            }
            // Applies the pending changes to the quote and save the order
            $editModel->setQuote($this->quote);
            $editModel->setOrder($this->order);
            $editModel->setChanges($this->pendingChanges);
            $editModel->saveOrder();

            // Removes "is_temporary" flag from the items
            $editQuoteModel->saveTemporaryItems($this->quote, 0, false);

            Mage::dispatchEvent('mwoe_save_order_after', array(
                'quote' => $this->quote,
                'order' => $this->order,
                'orig_order' => $this->origOrder
            ));

            $orderId = $this->getRequest()->getParam('order_id');
            $orderCommission = Mage::getModel('sales/order')->load($orderId);
            $editModel->saveCommission($orderCommission);

            // Create an invoice or credit memo for the saved order if needed
            $this->afterSaveOrder();

            // Remove pending changes from the session
            $this->resetPendingChanges();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order changes have been saved'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')
                ->addError($this->__('An error occurred while saving the order ' . $e->getMessage()));
        }
        //deducting amount from storecredit
        if($this->quote->getBaseAwStorecreditAmountUsed()) {
            $order_id = Mage::app()->getRequest()->getParam("order_id");
            $order = Mage::getModel("sales/order")->load($order_id);
            $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
            if (count($collection) != 0) {
                foreach($collection as $col) {
                    Mage::helper('aw_storecredit/totals')->saveQuoteStorecreditTotals($col->getLinkId(), $this->quote->getBaseAwStorecreditAmountUsed(), $this->quote->getAwStorecreditAmountUsed());
                }
            }
            $amount = $this->quote->getStorecreditAmountBalance();
            $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($order->getCustomerId());
            if($this->quote->getStoreCreditOperator()){
                if($this->quote->getStoreCreditOperator() == 1){
                    $storeCredit
                        ->setOrder($order)
                        ->setOrderCanceled(true)
                        ->setCustomerId($order->getCustomerId())
                        ->setBalance($storeCredit->getBalance() + $amount);

                    try {
                        $storeCredit->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }

                }
                elseif($this->quote->getStoreCreditOperator() == 2){
                    $storeCredit
                        ->setOrder($order)
                        ->setOrderCanceled(true)
                        ->setCustomerId($order->getCustomerId())
                        ->setBalance($storeCredit->getBalance() - $amount);

                    try {
                        $storeCredit->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }
        }
        if($orderCommission->getDiscountAmount() && ($orderCommission->getDiscountAmount()+ $orderCommission->getSubtotal() == 0)){
            /*
             * if discount amount fully cover order
             */
            $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($orderCommission->getQuoteId());
            //collecting storecredit on the basis of quote if available in aw_storecredit_quote table
            $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($orderCommission->getCustomerId());
            /*
             * customer balance
             */
            if (count($collection) != 0) {
                foreach($collection as $col) {
                    /*
                     * return amount back to storecredit
                     */
                    $storeCredit
                        ->setOrder($orderCommission)
                        ->setOrderCanceled(true)
                        ->setCustomerId($orderCommission->getCustomerId())
                        ->setBalance($storeCredit->getBalance() + $col['base_storecredit_amount']);

                    try {
                        $storeCredit->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }

                    /*
                     * setting storecredit instance to null
                     */
                    $this->quote->setStorecreditInstance(null);
                    /*
                     * removing storecredi from aw_storecredit_quote table
                     */
                    Mage::helper('aw_storecredit/totals')->removeStoreCreditFromQuote($storeCredit->getEntityId(), $this->quote);
                }
            }
        }

        Mage::getSingleton('adminhtml/session')->unsetData('storecredit_'.$this->quote->getId().'');

        $this->_redirectReferer();
    }

    public function cancelChangesAction()
    {
        $this->init();
        $this->removeTempQuoteItems();
        $this->resetPendingChanges();
        Mage::getSingleton('adminhtml/session_quote')->unsetData();
        Mage::getSingleton('adminhtml/session_quote')
            ->setData('base_shipping_custom_price', $this->order->getBaseShippingAmount());
        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order changes have been canceled'));

        $order_id = Mage::app()->getRequest()->getParam("order_id");
        $order = Mage::getModel("sales/order")->load($order_id);
        $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
        $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($order->getCustomerId());
        if (count($collection) != 0) {
            foreach($collection as $col) {
                if($col['base_storecredit_amount'] == 0 || $col['base_storecredit_amount'] == NULL) {
                    $this->quote->setStorecreditInstance(null);
                    Mage::helper('aw_storecredit/totals')->removeStoreCreditFromQuote($storeCredit->getEntityId(), $this->quote);
                }
            }
        }
        Mage::getSingleton('adminhtml/session')->unsetData('storecredit_'.$this->quote->getId().'');
        $this->_redirectReferer();
    }
}