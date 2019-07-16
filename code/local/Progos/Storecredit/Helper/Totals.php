<?php
/**
* @author Naveed Abbas <naveed.abbas@progos.org>
* @package Progos_Storecredit
* @Date 12/22/2017
* @extends AW_Storecredit_Helper_Totals
*/


class Progos_Storecredit_Helper_Totals extends AW_Storecredit_Helper_Totals
{
    public function saveQuoteStoreCreditTotals($linkId, $baseAmount, $amount)
    {
        $quoteStoreCreditModel = Mage::getModel('aw_storecredit/quote_storecredit')->load($linkId);
        if (null !== $quoteStoreCreditModel) {
            $quoteId = $quoteStoreCreditModel->getQuoteEntityId();
            $mdlEco = Mage::getModel('ecoprocessor/quote_index');
            $mdlRestmob = Mage::getModel('restmob/quote_index');
            if(!$mdlRestmob->getIdByQuoteId($quoteId) && !$mdlEco->getIdByQuoteId($quoteId)) {
                $quoteStoreCreditModel
                    ->setBaseStorecreditAmount($baseAmount)
                    ->setStorecreditAmount($amount)
                    ->save()
                ;
            }
        }
        return $this;
    }
}