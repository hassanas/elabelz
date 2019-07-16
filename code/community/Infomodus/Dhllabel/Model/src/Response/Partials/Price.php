<?php
/**
 * @author    Danail Kyosev <ddkyosev@gmail.com>
 * @copyright 2014, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Infomodus_Dhllabel_Model_Src_Response_Partials_Price
{
    private $productGlobalCode;
    private $productLocalCode;
    private $productName;
    private $currencyCode;
    private $weightCharge;
    private $weightChargeTax;
    private $totalAmmount;
    private $totalTaxAmmount;
    private $qtdShpExChrg=0;

    public function getProductGlobalCode()
    {
        return $this->productGlobalCode;
    }
    public function getProductLocalCode()
    {
        return $this->productLocalCode;
    }
    public function getProductName()
    {
        return $this->productName;
    }

    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    public function getWeightCharge()
    {
        return $this->weightCharge;
    }

    public function getWeightChargeTax()
    {
        return $this->weightChargeTax;
    }

    public function getTotalAmount()
    {
        return $this->totalAmmount;
    }

    public function getTotalTaxAmount()
    {
        return $this->totalTaxAmmount;
    }

    public function getQtdShpExChrg()
    {
        return $this->qtdShpExChrg;
    }

    public function __construct($data)
    {
        $this->productGlobalCode = (string) $data->GlobalProductCode;
        $this->productLocalCode = (string) $data->LocalProductCode;
        $this->productName = (string) $data->ProductShortName;
        $this->currencyCode = (string) $data->CurrencyCode;
        $this->weightCharge = (string) $data->WeightCharge;
        $this->weightChargeTax = (string) $data->WeightChargeTax;
        $this->totalAmmount = (string) $data->ShippingCharge;
        $this->totalTaxAmmount = (string) $data->TotalTaxAmount;
        if(isset($data->QtdShpExChrg[1])){
            foreach($data->QtdShpExChrg AS $QtdShpExChrg){
                if ($QtdShpExChrg && $QtdShpExChrg->SpecialServiceType == 'OO') {
                    $this->qtdShpExChrg = 1;
                    break;
                }
            }
        }
        else {
            if ($data->QtdShpExChrg && $data->QtdShpExChrg->SpecialServiceType == 'OO') {
                $this->qtdShpExChrg = 1;
            }
        }
    }
}
