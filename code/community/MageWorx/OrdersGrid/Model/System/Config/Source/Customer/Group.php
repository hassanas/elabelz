<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_System_Config_Source_Customer_Group
{

    protected $customerGroups = array();

    /**
     * @param bool|true $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = true)
    {
        $options = array();
        $customerGroups = $this->getCustomerGroups();

        foreach ($customerGroups as $code => $title) {
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
        return $this->getCustomerGroups();
    }

    /**
     * @return array|mixed
     */
    public function getCustomerGroups()
    {
        if ($this->customerGroups) {
            return $this->customerGroups;
        }

        $customerGroups = Mage::getResourceModel('customer/group_collection')->load()->toOptionArray();
        $groups = array();
        foreach ($customerGroups as $data) {
            $groups[$data['value']] = $data['label'];
        }

        $this->customerGroups = $groups;

        return $groups;
    }
}