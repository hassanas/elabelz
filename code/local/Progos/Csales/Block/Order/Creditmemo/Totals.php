<?php
/**
 * @author : humaira batool (humaira.abtool@progos.org)
 * Rewrite block for the function so that we can get full storecredit amount of a customer
 */
class Progos_Csales_Block_Order_Creditmemo_Totals extends Mage_Sales_Block_Order_Creditmemo_Totals
{

    public function getTotalCreditAvailble()
    {
        $customer_id = $this->getOrder()->getCustomerId();
        $storeCreditModel = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($customer_id);
        return $storeCreditModel->getBalance();

    }


}
