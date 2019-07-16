<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


/**
 * Блок для вывода Goodrelations на странице продукта
 */
class Mirasvit_Seo_Block_Goodrelations extends Mage_Core_Block_Template
{
    public function getProduct() {
        return Mage::registry('current_product');
    }

    /**
     * Возвращает минимальную цену для группового продукта
     * @return int
     */
    public function getGroupedMinimalPrice() {
        $product = Mage::getModel('catalog/product')->getCollection()
                        ->addMinimalPrice()
                        ->addFieldToFilter('entity_id',$this->getProduct()->getId())
                        ->getFirstItem();
        return Mage::helper('tax')->getPrice($product, $product->getMinimalPrice(), $includingTax = true);
    }


    public function getCurrentCurrencyCode()
    {
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Возвращает доступные платежные методы магазина
     * @return array
     */
    public function getActivePaymentMethods()
    {
//        $codes = array(
//        '' => 'ByBankTransferInAdvance',
//        '' => 'ByInvoice',
//        '' => 'Cash',
//        '' => 'CheckInAdvance',
//        '' => 'COD',
//        '' => 'DirectDebit',
//        '' => 'PayPal',
//        '' => 'PaySwarm',
//        '' => 'AmericanExpress',
//        '' => 'DinersClub',
//        '' => 'Discover',
//        '' => 'MasterCard',
//        '' => 'VISA',
//        '' => 'JCB',
//        '' => 'GoogleCheckout',
//        );

       $payments = Mage::getSingleton('payment/config')->getActiveMethods();
       $methods = array();
       foreach ($payments as $paymentCode=>$paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $code = false;
            if (strpos($paymentCode, 'paypal') !== false) {
                $methods[] = 'PayPal';
            } elseif (strpos($paymentCode, 'googlecheckout') !== false) {
                $methods[] = 'GoogleCheckout';
            } elseif ($paymentCode == 'ccsave') {
                $methods[] = 'MasterCard';
                $methods[] = 'AmericanExpress';
                $methods[] = 'VISA';
                $methods[] = 'JCB';
                $methods[] = 'Discover';
            }
        }
        return array_unique($methods);
    }

}
