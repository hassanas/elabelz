<?php
/**
 * This Module will Add Tax Rates & Rules
 *
 * @category       Progos
 * @package        Progos_UpdateVat
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           14-12-2017 16:39
 *
 */
class Progos_UpdateVat_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Simplely delete all previously added Rates
     */
    public function deleteAllTaxRules(){
        $taxCalculation = Mage::getModel('tax/calculation');
        foreach (Mage::getModel('tax/calculation_rule')->getCollection() as $rule) {
            $taxCalculation->deleteByRuleId($rule->getId());
            $rule->delete();
        }
    }

    /**
     * Simplely delete all previously added Rates
     */
    public function deleteAllTaxRates(){
        foreach (Mage::getModel('tax/calculation_rate')->getcollection() as $rate) {
            $rate->delete();
        }
    }

    /**
     * This Function will create the Tax Rate
     *
     * @param $taxCode DataType: string
     * @param $taxCountryId DataType: string
     * @param int $taxRegionId DataType: string
     * @param int $zipIsRange DataType: string
     * @param string $taxPostcode DataType: string
     * @param $rate DataType: string
     * @return Integer Tax Rate ID
     *
     */
    public function addNewTaxRate($taxCode, $taxCountryId, $taxRegionId=0, $zipIsRange=0, $taxPostcode='*', $rate){

        return Mage::getModel('tax/calculation_rate')
            ->setData(array(
                "code"                  => $taxCode,
                "tax_country_id"        => $taxCountryId,
                "tax_region_id"         => $taxRegionId,
                "zip_is_range"          => $zipIsRange,
                "tax_postcode"          => $taxPostcode,
                "rate"                  => $rate,
            ))->save();
    }

    /**
     * This Function will create the Tax Rule
     * @param $taxCode DataType: string
     * @param $taxCustomerClass DataType: array
     * @param $taxProductClass DataType: array
     * @param $taxRate DataType: array
     * @param int $priority DataType: string
     * @param int $position DataType: string
     */
    public function addNewTaxRule($taxCode, $taxCustomerClass, $taxProductClass, $taxRate, $priority=0, $position=0){
            Mage::getModel('tax/calculation_rule')
                    ->setData(array(
                        "code"                  => $taxCode,
                        "tax_customer_class"    => $taxCustomerClass,
                        "tax_product_class"     => $taxProductClass,
                        "tax_rate"              => $taxRate,
                        "priority"              => $priority,
                        "position"              => $position,
                    ))->save();
    }

}
	 