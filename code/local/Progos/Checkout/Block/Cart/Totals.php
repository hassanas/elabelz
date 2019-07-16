<?php
class Progos_Checkout_Block_Cart_Totals extends Mage_Checkout_Block_Cart_Totals
{
    //creating function for checking totals code
    public function checkTotals($area = null, $colspan = 1)
    {
        $code = array("codes");
        foreach($this->getTotals() as $total) {
            array_push($code,$total->getCode());
        }
        return $code;
    }
}