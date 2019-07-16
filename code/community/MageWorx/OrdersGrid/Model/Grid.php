<?php

/**
 * MageWorx
 * Admin Order Grid extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_Grid extends Mage_Core_Model_Abstract
{

    protected $defaultColumns = "increment_id,store_id,created_at,billing_name,shipping_name,base_grand_total,grand_total,status,order_group,action";
    protected $defaultCustomerColumns = "increment_id,created_at,billing_name,shipping_name,grand_total,store_id,action";

    protected $thirdPartyColumns = array();

    /**
     * @return array
     */
    public function getThirdPartyColumns()
    {
        return $this->thirdPartyColumns;
    }

    /**
     * @param array $data
     */
    public function setThirdPartyColumns($data)
    {
        $this->thirdPartyColumns = $data;
    }

    /**
     * Init default columns on grid of certain type
     *
     * @param string $type
     * @return string
     */
    public function initColumns($type)
    {
        $helper = $this->getHelper();
        if ($type == MageWorx_OrdersGrid_Helper_Data::XML_GRID_TYPE_CUSTOMER) {
            $defaultSelectedColumns = $this->defaultCustomerColumns;
        } else {
            $defaultSelectedColumns = $this->defaultColumns;
        }

        $helper->saveColumnsConfig($type, $defaultSelectedColumns);

        return $defaultSelectedColumns;
    }

    /**
     * Add columns to the grid of certain type
     *
     * @param array  $columns
     * @param string $type
     * @return string
     */
    public function addColumn($columns, $type)
    {
        $selectedColumns = $this->getHelper()->getGridColumns($type);
        foreach ($columns as $key => $value) {
            if (!in_array($key, $selectedColumns)) {
                $selectedColumns[] = $key;
            }
        }

        $this->getHelper()->saveColumnsConfig($type, implode(',', $selectedColumns));

        return $selectedColumns;
    }

    /**
     * Init default column sort order on grid of certain type
     *
     * @param string $type
     * @return array
     */
    public function initColumnOrder($type)
    {
        $sortOrder = array();
        $index = 10;
        foreach ($this->getHelper()->getAllGridColumns() as $key) {
            $sortOrder[$key] = $index;
            $index += 10;
        }

        $this->getHelper()->saveSortOrderConfig($type, $sortOrder);

        return $sortOrder;
    }

    /**
     * Add column sort order to the grid of certain type
     *
     * @param array  $columns
     * @param string $type
     * @return string
     */
    public function addColumnOrder($columns, $type)
    {
        $sortOrder = $this->getHelper()->getGridColumnsSortOrder($type);
        foreach ($columns as $key => $value) {
            if (!isset($sortOrder[$key])) {
                $sortOrder[$key] = count($sortOrder) * 10 + 10;
            }
        }

        $this->getHelper()->saveSortOrderConfig($type, $sortOrder);

        return $sortOrder;
    }

    /**
     * @return MageWorx_OrdersGrid_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('mageworx_ordersgrid');
    }
}