<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
?>
<?php
/**
 * Class Progos_OrdersEdit_Model_Edit_Log
 */
class Progos_OrdersEdit_Model_Edit_Log extends MageWorx_OrdersEdit_Model_Edit_Log
{
    /**
     * Create order comment with the changes that were made
     *
     * @param Mage_Sales_Model_Order $order
     * @return $this
     * @throws Exception
     */
    public function commitOrderChanges(Mage_Sales_Model_Order $order)
    {
        $text = '';
        $labelPrefix = '';
        $helper = $this->getHelper();
        $store = $this->getStore($order);
        $this->setOrder($order);

        $changes = $this->getChanges($order);
        foreach ($this->_getAdditionalSources($order) as $code => $source) {
            if (!($source instanceof Varien_Object)) {
                continue;
            }

            $addChanges = $this->getChanges($source);

            if ($source instanceof Mage_Sales_Model_Order_Address) {
                foreach ($addChanges as $k => $change) {
                    if ($source->getAddressType() == 'shipping') {
                        $addChanges[$k]['label_prefix'] = $helper->__('Shipping') . ' ';
                    } elseif ($source->getAddressType() == 'billing') {
                        $addChanges[$k]['label_prefix'] = $helper->__('Billing') . ' ';
                    }
                }
            }

            $changes = array_merge($changes, $addChanges);
        }

        foreach ($changes as $code => $diff) {
            $label = $labelPrefix . $this->_possibleChanges[$code];
            $labelPrefix = (isset($diff['label_prefix'])) ? $diff['label_prefix'] : '';

            $text .= $labelPrefix;
            $text .= $helper->__("%s has been changed from \"%s\" to \"%s\"", $label, $diff['from'], $diff['to']);
            $text .= $this->eol();
        }

        if (count($this->_itemsChanges)) {
            foreach ($this->_itemsChanges as $item) {

                // Qty changes
                if (empty($item['qty_after'])) {
                    $text .= $helper->__("\"%s\" has been removed from the order", $item['name']) . $this->eol();
                } elseif (empty($item['qty_before'])) {
                    $text .= $helper->__("\"%s\" has been added to the order (Qty: %s)", $item['name'],
                            $item['qty_after']) . $this->eol();
                } elseif ($item['qty_after'] > $item['qty_before']) {
                    $qtyDiff = $item['qty_after'] - $item['qty_before'];
                    $text .= $helper->__("%s item(s) of \"%s\" have been added to the order", $qtyDiff,
                            $item['name']) . $this->eol();
                } elseif ($item['qty_before'] > $item['qty_after']) {
                    $qtyDiff = $item['qty_before'] - $item['qty_after'];
                    $text .= $helper->__("%s item(s) of \"%s\" have been removed from the order", $qtyDiff,
                            $item['name']) . $this->eol();
                }

                // Price changes
                if (isset($item['price_after']) && isset($item['price_before']) && $item['price_after'] != $item['price_before']) {
                    $text .= $helper->__("Price of \"%s\" has been changed from %s to %s",
                            $item['name'],
                            $store->formatPrice($item['price_before'], false),
                            $store->formatPrice($item['price_after'], false))
                        . $this->eol();
                }

                // Discount changes
                if (isset($item['discount'])) {
                    if ($item['discount'] == 1) {
                        $text .= $helper->__("Discount for \"%s\" have been applied", $item['name']) . $this->eol();
                    } elseif ($item['discount'] == -1) {
                        $text .= $helper->__("Discount of \"%s\" have been removed", $item['name']) . $this->eol();
                    }
                }
            }
        }

        if (empty($text)) {
            return $this;
        }

        // 0 - no one; 1 - only admin; 2 - notify all;
        /** @var int $notify */
        $notify = intval($this->getHelper()->isSendUpdateEmail());
        /** @var MageWorx_OrdersBase_Model_Logger $logger */
        $logger = Mage::getModel('mageworx_ordersbase/logger');
        $logger->log($text, $order, $notify);

        /** Begin: Update Grand total during order update from BE */
        if ($notify) {
            $order->sendOrderUpdateEmail($notify > 1, $text);
        }

        $cod = 0;
        $cod_base = 0;

        // Add COD value, if order has products and COD doesn't have any value
        if ($order->getTotalQtyOrdered() > 0 && !$order->getMspCashondelivery()) {
            $cod = $order->getMspCashondelivery();
            $cod_base = $order->getMspBaseCashondelivery();
        }

        $order->setGrandTotal($order->getGrandTotal() + $cod);
        $order->setBaseGrandTotal($order->getBaseGrandTotal() + $cod_base);
        $order->save();
        /** End: Update Grand total during order update from BE */

        return $this;
    }
}