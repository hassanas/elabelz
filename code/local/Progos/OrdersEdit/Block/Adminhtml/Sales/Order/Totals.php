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
 * Class Progos_OrdersEdit_Block_Adminhtml_Sales_Order_Totals
 */
class Progos_OrdersEdit_Block_Adminhtml_Sales_Order_Totals extends MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Totals
{
    /**
     * Get order totals
     *
     * @return mixed
     */
    public function getTotals()
    {
        $totals = $this->getData('totals');

        //for shipping incl. tax on "New Totals" block
        if ((Mage::helper('tax')->displayShippingPriceIncludingTax() || Mage::helper('tax')->displayShippingBothPrices()) &&
            isset($totals['shipping'])
        ) {
            $totals['shipping']->setValue($this->getSource()->getShippingAddress()->getShippingInclTax());
        }

        /** Begin: Adding MSP CashOnDelivery fee for Grand Total */
        $order = $this->getOrder();

        $cod = 0;
        $codBase = 0;

        // Add COD value, if order has products and COD doesn't have any value
        if ($order->getTotalQtyOrdered() > 0 && !$order->getMspCashondelivery()) {
            $cod = $order->getMspCashondelivery();
            $codBase = $order->getMspBaseCashondelivery();
        }

        foreach ($totals as $code => $total) {
            $address = $total->getAddress();

            if ($code == "msp_cashondelivery") {
                $baseValue = $address->getData('msp_base_cashondelivery');
                $value = $address->getData('msp_cashondelivery');
            } elseif ($code == "shipping") {
                $baseValue = $address->getData('base_shipping_amount');
                $value = $address->getData('shipping_amount');
            } elseif ($code == "discount") {
                $baseValue = $address->getData('base_discount_amount');
                $value = $address->getData('discount_amount');
            } elseif ($code == "grand_total") {
                $baseValue = $address->getData('base_' . $code) + $codBase;
                $value = $address->getData($code) + $cod;
            }
            elseif($code == "aw_storecredit"){
                $baseValue = $address->getData('base_aw_storecredit_amount_used');
                $value = $address->getData('aw_storecredit_amount_used');
            }elseif($code == "tax"){
                $baseValue = $address->getData('base_tax_amount');
                $value = $address->getData('tax_amount');
            }else {
                $baseValue = $address->getData('base_' . $code);
                $value = $address->getData($code);
            }

            $total->setData('base_value', $baseValue);
            $total->setData('value', $value);
        }
        /** End: Adding MSP CashOnDelivery fee for Grand Total */

        return $totals;
    }

    /**
     * Format total value based on order currency
     *
     * @param   Varien_Object $total
     * @return  string
     * hassan ali shahzad: Need to override this funtion because from following class when value and base_value passed they become null
     * Progos_Storecredit_Model_Sales_Order_Totals_Quote::fetch
     * basestorecredit and storecreditindollers passed and placed chek in case of code aw_storecredit
     * use custom parameters for SC in local & foreign currency
     *
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            if($total->getCode()=='aw_storecredit'){
                return $this->helper('adminhtml/sales')->displayPrices(
                    $this->getOrder(),
                    $total->getBasestorecredit(),
                    $total->getStorecreditindollers()
                );
            }
            else{
                return $this->helper('adminhtml/sales')->displayPrices(
                    $this->getOrder(),
                    $total->getBaseValue(),
                    $total->getValue()
                );
            }
        }
        return $total->getValue();
    }
}