<?php
/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 * 
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersSurcharge_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_ENABLED = 'mageworx_ordersmanagement/orderssurcharge/enabled';
    const XML_CMS_BLOCK = 'mageworx_ordersmanagement/orderssurcharge/cms_block';

    /**
     * Check is module enabled in system > configuration
     *
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_ENABLED);
    }

    /**
     * Check is module disabled in system > configuration
     *
     * @return bool
     */
    public function isDisabled()
    {
        return !$this->isEnabled();
    }

    /**
     * Get the static block id for the cart page
     *
     * @return string
     */
    public function getCartStaticBlockIdentifier()
    {
        return Mage::getStoreConfig(self::XML_CMS_BLOCK);
    }

    /**
     * Add "create new surcharge link" button to order view container
     * if available and allowed
     *
     * @param Mage_Adminhtml_Block_Sales_Order_View $block
     * @return bool
     */
    public function addCreateSurchargeLinkButton(Mage_Adminhtml_Block_Sales_Order_View $block)
    {

        // Validate by ACL
        if (!$this->isAllowed('sales/mageworx_orderssurcharge/actions/add_link_button')) {
            return false;
        }

        // Validate by order id
        $order = $block->getOrder();
        if (!$order->getId()) {
            return false;
        }

        // Validate by total due
        $totalDue = $order->getTotalDue();
        if (!$totalDue) {
            return false;
        }

        // Validate by existing invoice
        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoices */
        $invoices = $order->getInvoiceCollection();
        if (!$invoices->count()) {
            return false;
        }

        // Validate by "order for surcharge"
        $surchargeId = $order->getSurchargeId();
        if ($surchargeId) {
            return false;
        }

        // Validate by existing unpaid surcharge
        if ($this->isExistUnpaidSurchargeForOrder($order)) {
            return false;
        }

        $onclickJs = 'OrdersSurcharge.createSurchargeLink(\'' . $this->getCreateSurchargeLinkUrl($order->getId()) . '\');';

        $block->addButton('create_surcharge_link', array(
            'label'    => $this->__('Create Surcharge Link'),
            'onclick'  => $onclickJs,
        ));

        return true;
    }

    /**
     * Check is unpaid surcharge exist for the order
     *
     * @param Mage_Sales_Model_Order|int $order
     * @return bool
     */
    public function isExistUnpaidSurchargeForOrder($order)
    {
        if (is_int($order)) {
            $orderId = $order;
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($orderId);
        }

        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $surcharge */
        $surcharge = Mage::getModel('mageworx_orderssurcharge/surcharge')->loadByParentOrder($order)->getFirstItem();
        if ($surcharge->getBaseTotalDue() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Convert a base order total to a regular total (in order currency, by existing order rate)
     *
     * @todo CHECK CONVERSION
     * @param $newBaseTotalPaid
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    public function convertBaseToOrderRate($newBaseTotalPaid, Mage_Sales_Model_Order $order)
    {
        $baseToOrderRate = $order->getBaseToOrderRate();
        $result = $newBaseTotalPaid * $baseToOrderRate;

        return $result;
    }

    /**
     * Get "double" prices html (block with base and place currency)
     *
     * @param   Varien_Object $dataObject
     * @param   float $basePrice
     * @param   float $price
     * @param   bool $strong
     * @param   string $separator
     * @return  string
     */
    public function displayPrices($dataObject, $basePrice, $price, $strong = false, $separator = '<br/>')
    {
        $order = false;
        if ($dataObject instanceof Mage_Sales_Model_Order) {
            $order = $dataObject;
        } else {
            $order = $dataObject->getOrder();
        }

        if ($order && $order->isCurrencyDifferent()) {
            $res = '<strong>';
            $res.= $order->formatBasePrice($basePrice);
            $res.= '</strong>'.$separator;
            $res.= '['.$order->formatPrice($price).']';
        } elseif ($order) {
            $res = $order->formatPrice($price);
            if ($strong) {
                $res = '<strong>'.$res.'</strong>';
            }
        } else {
            $res = Mage::app()->getStore()->formatPrice($price);
            if ($strong) {
                $res = '<strong>'.$res.'</strong>';
            }
        }
        return $res;
    }

    /**
     * Create add surcharge link for order
     * @param $orderId
     * @return string
     */
    public function getCreateSurchargeLinkUrl($orderId)
    {
        return Mage::getModel('adminhtml/url')->getUrl('adminhtml/mageworx_orderssurcharge_surcharge/add', array('order_id' => $orderId));
    }

    /**
     * Check is action allowed
     * sales/order/action/ path is default
     *
     * @param $action
     * @return bool
     */
    public function isAllowed($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed($action);
    }

    /**
     * @return MageWorx_OrdersBase_Model_Logger
     */
    public function getLogger()
    {
        return Mage::getModel('mageworx_ordersbase/logger');
    }

}