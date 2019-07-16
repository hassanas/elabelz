<?php
/**
 * User: Hassan Ali Shahzad
 * Date: 09/02/2018
 * Time: 13:11
 *
 */

class Progos_Telrtransparent_Model_System_Config_Source_Paymentmethodselection
{

    public function toOptionArray()
    {
        return array(
            array('value' => 'telrtransparent', 'label' => Mage::helper('adminhtml')->__('Telr TransParent')),
            array('value' => 'checkoutdotcom', 'label' => Mage::helper('adminhtml')->__('CheckoutDotCom')),
        );
    }
}