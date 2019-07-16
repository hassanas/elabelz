<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Model_Linked_Order_Total_Invoice extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{

    /**
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {

        if ($invoice->getBaseLinkedAmountToBeInvoiced() > 0) {
            $order = $invoice->getOrder();
            $baseLinkedAmount = $invoice->getBaseLinkedAmountToBeInvoiced();
            $linkedAmount = Mage::helper('mageworx_orderssurcharge')->convertBaseToOrderRate($baseLinkedAmount, $order);

            // Decrease invoice GT (to 0)
            $grandTotal = $invoice->getGrandTotal() - $linkedAmount;
            $invoice->setGrandTotal($grandTotal);
            $baseGrandTotal = $invoice->getBaseGrandTotal() - $baseLinkedAmount;
            $invoice->setBaseGrandTotal($baseGrandTotal);

            $order->setLinkedAmountInvoiced($linkedAmount + $order->getLinkedAmountInvoiced());
            $order->setBaseLinkedAmountInvoiced($baseLinkedAmount + $order->getBaseLinkedAmountInvoiced());
        }

        return $this;
    }

}