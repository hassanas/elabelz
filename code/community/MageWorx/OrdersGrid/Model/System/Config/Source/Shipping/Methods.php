<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_System_Config_Source_Shipping_Methods
{

    protected $shippingMethods = array();

    /**
     * @param bool|true $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = true)
    {
        $options = array();
        $methods = $this->getAllShippingMethods();

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
        return $this->getAllShippingMethods();
    }

    /**
     * @return array|mixed
     */
    public function getAllShippingMethods()
    {
        if ($this->shippingMethods) {
            return $this->shippingMethods;
        }

        $carriers = Mage::getSingleton('shipping/config')->getAllCarriers();
        $methods = array();
        foreach ($carriers as $code => $carriersModel) {
            $title = Mage::getStoreConfig('carriers/' . $code . '/title');
            if ($title) {
                $methods[$code . '_' . $code] = $title;
            }
        }
        $this->shippingMethods = $methods;

        return $methods;
    }
}