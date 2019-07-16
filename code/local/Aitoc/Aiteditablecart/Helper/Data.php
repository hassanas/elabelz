<?php
/**
 * @copyright  Copyright (c) 2009 AITOC, Inc. 
 */

class Aitoc_Aiteditablecart_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getOptionPrice($oProduct, $sPriceType, $nPriceValue, $flag=false)
    {
        if ($flag && $sPriceType == 'percent') {

            if ($nSpecialPrice = $oProduct->getSpecialPrice())
            {
                $basePrice = $nSpecialPrice;
            }
            else 
            {
                $basePrice = $oProduct->getPrice();
            }

            $price = $basePrice*($nPriceValue/100);
            return $price;
        }

        return $nPriceValue;
    }   
}