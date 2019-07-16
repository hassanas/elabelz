<?php
class Progos_ReCancel_Model_Observer {
    public function addViewButton($observer) {
		if($observer->getEvent()->getBlock() instanceof Mage_Adminhtml_Block_Widget_Form_Container) {
			if($observer->getEvent()->getBlock()->getId()=='sales_order_view' && Mage::registry('sales_order')->getState()=='canceled'){
                /*
                * checking if order has storecredit or not
                */
                $order_id = Mage::app()->getRequest()->getParam('order_id');
                $order = Mage::getModel("sales/order")->load($order_id);
                $quoteCollection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
                /*
                 * checking if all items are rejected or not
                 */
                $allItemsRejected = Mage::helper('marketplace/marketplace')->getProductReject($order_id);
                if(count($quoteCollection) == 0) {
                    if ($allItemsRejected !== "true_seller" ||  Mage::registry('sales_order')->getStatus()=='pending_payment') {
                        /*
                         * if order does not have store credit and all items are not rejected only then show un cancel button
                         */
                        $url = Mage::helper('adminhtml')->getUrl('adminhtml/order/recancel', array('id' => Mage::registry('sales_order')->getId()));
                        $observer->getEvent()->getBlock()->addButton('reCancel', array(
                            'label' => Mage::helper('adminhtml')->__('UnCancel'),
                            'onclick' => 'setLocation(\'' . $url . '\')',
                        ), -1);
                    }
                }
			}
		}
    }
}

