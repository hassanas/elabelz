<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_System_Config_Source_Orders_Group
{

    protected $ordersGroups = array();

    /**
     * @param bool|true $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = true)
    {
        $options = array();
        $ordersGroups = $this->getOrdersGroups();

        foreach ($ordersGroups as $code => $title) {
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
        return $this->getOrdersGroups();
    }

    /**
     * @return array|mixed
     */
    public function getOrdersGroups()
    {
        if ($this->ordersGroups) {
            return $this->ordersGroups;
        }

        $orderGroups = Mage::getResourceModel('mageworx_ordersgrid/order_group_collection')->load()->toOptionArray();
        $this->ordersGroups = $orderGroups;

        return $orderGroups;
    }
}