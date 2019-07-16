<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_System_Config_Source_Payment_Methods
{

    protected $paymentMethods = array();

    /**
     * @param bool|true $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = true)
    {
        $options = array();
        $methods = $this->getAllPaymentMethods();

        foreach ($methods as $code => $title) {
            $options[] = array(
                'value' => $code,
                'label' => $title
            );
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAllPaymentMethods();
    }

    /**
     * @return array|mixed
     */
    public function getAllPaymentMethods()
    {
        if ($this->paymentMethods) {
            return $this->paymentMethods;
        }

        $payments = Mage::getSingleton('payment/config')->getAllMethods();
        $methods = array();
        foreach ($payments as $paymentCode => $paymentModel) {
            $methods[$paymentCode] = Mage::getStoreConfig('payment/' . $paymentCode . '/title');
        }

        $this->paymentMethods = $methods;

        return $methods;
    }
}