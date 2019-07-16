<?php
/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersSurcharge_Model_Surcharge_Total_Invoice extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{

    /**
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice) {

        if ($invoice->getBaseSurchargeAmount() > 0) {
            $grandTotal = $invoice->getGrandTotal() + $invoice->getSurchargeAmount();
            $invoice->setGrandTotal($grandTotal);
            $baseGrandTotal = $invoice->getBaseGrandTotal() + $invoice->getBaseSurchargeAmount();
            $invoice->setBaseGrandTotal($baseGrandTotal);
        }

        return $this;
    }

}