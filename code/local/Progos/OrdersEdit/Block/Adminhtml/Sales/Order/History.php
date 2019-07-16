<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
  My MM*/
/*May changes*/
?>
<?php
/**
 * Class Progos_OrdersEdit_Block_Adminhtml_Sales_Order_History
 */
class Progos_OrdersEdit_Block_Adminhtml_Sales_Order_History extends MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_History
{
    /**
     * Prepare global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        /** Begin: Use Progos CallCenter URL instead of MageWorx OrderEdit */
        $order = $this->getOrder();
        $admin = Mage::getSingleton('admin/session')->getUser();

        if (is_null($order)) {
            $order_id = $this->getRequest()->getParam("order_id");
            $order = Mage::getModel("sales/order")->load($order_id);
        }

        if ($order->getAgent() != $admin->getId() && $order->getAgent() > 0) {
            $admin_obj = Mage::getModel('admin/user')->load($order->getAgent());
            $admin_data = $admin_obj->getData();

            if (empty(trim($admin_obj->getName()))) {
                $admin_name = $admin_data["username"];
            } else {
                $admin_name = $admin_obj->getName();
            }

            $errorMsg = "You cannot process this order it is already assigned to " . $admin_name . "!";
            Mage::getSingleton('core/session')->addError($this->__($errorMsg));

            $url = $this->getUrl('callcenter/adminhtml_orders');
            Mage::app()->getFrontController()->getResponse()->setRedirect($url);

            $onclick_callcenter = "submitHistoryAndReload($('order_history_block').parentNode, '" . Mage::helper("callcenter")->getSubmitUrl() . "')";
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('sales')->__('Submit Comment'),
                    'class' => 'save',
                    'onclick' => $onclick_callcenter
                ));
            $this->setChild('submit_button_callcenter', $button);
        } else {
        /** End: Use Progos CallCenter URL instead of MageWorx OrderEdit */
            $onclick = "submitHistoryAndReload($('order_history_block').parentNode, '" . $this->getSubmitUrl() . "')";

            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('sales')->__('Submit Comment'),
                    'class' => 'save',
                    'onclick' => $onclick
                ));
            $this->setChild('submit_button', $button);
        }

        return $this;
    }
}