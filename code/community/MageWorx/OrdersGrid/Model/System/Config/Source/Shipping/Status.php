<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_System_Config_Source_Shipping_Status
{

    const STATUS_NOT_SHIPPED = '0';
    const STATUS_SHIPPED = '1';
    const STATUS_PARTIALLY_SHIPPED = '2';

    /**
     * @param bool|true $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = true)
    {
        $helper = Mage::helper('mageworx_ordersgrid');
        $adminHelper = Mage::helper('adminhtml');

        $options = array(
            array(
                'value' => self::STATUS_NOT_SHIPPED,
                'label' => $adminHelper->__('No')
            ),
            array(
                'value' => self::STATUS_SHIPPED,
                'label' => $adminHelper->__('Yes')
            ),
            array(
                'value' => self::STATUS_PARTIALLY_SHIPPED,
                'label' => $helper->__('Partially')
            ),
        );

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $helper = Mage::helper('mageworx_ordersgrid');
        $adminHelper = Mage::helper('adminhtml');

        $options = array(
            self::STATUS_NOT_SHIPPED => $adminHelper->__('No'),
            self::STATUS_SHIPPED => $adminHelper->__('Yes'),
            self::STATUS_PARTIALLY_SHIPPED => $helper->__('Partially')
        );

        return $options;
    }
}