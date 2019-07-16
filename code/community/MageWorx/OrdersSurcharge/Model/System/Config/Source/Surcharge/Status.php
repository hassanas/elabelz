<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Model_System_Config_Source_Surcharge_Status
{
    /**
     * @param bool|true $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = true)
    {
        $options = array(
            array('value' => 0, 'label' => 'Deleted'),
            array('value' => 1, 'label' => 'Pending'),
            array('value' => 2, 'label' => 'Processing'),
            array('value' => 3, 'label' => 'Paid'),
            array('value' => 4, 'label' => 'Complete')
        );

        return $options;
    }

    public function toArray()
    {
        $options = array(
            0 => 'Deleted',
            1 => 'Pending',
            2 => 'Processing',
            3 => 'Paid',
            4 => 'Complete'
        );

        return $options;
    }
}