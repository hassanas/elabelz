<?php

require_once(Mage::getModuleDir('controllers', 'Aramex_Adminhtml') . DS . 'Sales' . DS . 'OrderController.php');

class Rewrite_CSaleOrder_Adminhtml_Sales_OrderController extends Aramex_Adminhtml_Sales_OrderController
{
    public function editAction()
    {
        $this->_title($this->__('Sales'))->_title($this->__('Orders'));
        $order = $this->_initOrder();

        if ($order) {
            $isActionsNotPermitted = $order->getActionFlag(
                Mage_Sales_Model_Order::ACTION_FLAG_PRODUCTS_PERMISSION_DENIED
            );

            if ($isActionsNotPermitted) {
                $this->_getSession()->addError($this->__('You don\'t have permissions to manage this order because of one or more products are not permitted for your website.'));
            }

            $this->_initAction();
            $this->_title(sprintf("#%s", $order->getRealOrderId()));
            $this->renderLayout();
        }
    }
}