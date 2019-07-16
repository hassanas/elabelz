<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml sales orders grid
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Infomodus_Dhllabel_Block_Adminhtml_Sales_Shipment_Grid extends Mage_Adminhtml_Block_Sales_Shipment_Grid
{

    /**
     * Initialization
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_shipment_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'sales/order_shipment_grid_collection';
    }

    /**
     * Prepare and set collection of grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare and add columns to grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'increment_id', array(
                'header' => Mage::helper('sales')->__('Shipment #'),
                'index' => 'increment_id',
                'type' => 'text',
            )
        );

        $this->addColumn(
            'created_at', array(
                'header' => Mage::helper('sales')->__('Date Shipped'),
                'index' => 'created_at',
                'type' => 'datetime',
            )
        );

        $this->addColumn(
            'order_increment_id', array(
                'header' => Mage::helper('sales')->__('Order #'),
                'index' => 'order_increment_id',
                'type' => 'text',
            )
        );

        $this->addColumn(
            'order_created_at', array(
                'header' => Mage::helper('sales')->__('Order Date'),
                'index' => 'order_created_at',
                'type' => 'datetime',
            )
        );

        $this->addColumn(
            'shipping_name', array(
                'header' => Mage::helper('sales')->__('Ship to Name'),
                'index' => 'shipping_name',
            )
        );

        $this->addColumn(
            'total_qty', array(
                'header' => Mage::helper('sales')->__('Total Qty'),
                'index' => 'total_qty',
                'type' => 'number',
            )
        );

        $this->addColumn(
            'upsprice', array(
                'header' => Mage::helper('adminhtml')->__('Price'),
                'index' => 'upsprice',
                'width' => '100px',
                'frame_callback' => array($this, 'callback_upsprice'),
            )
        );

        $this->addColumn(
            'action',
            array(
                'header' => Mage::helper('sales')->__('Action'),
                'width' => '50px',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('sales')->__('View'),
                        'url' => array('base' => '*/sales_shipment/view'),
                        'field' => 'shipment_id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'is_system' => true
            )
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        return parent::_prepareColumns();
    }

    public function callback_upsprice($value, $row, $column, $isExport)
    {
        $c = '';
        $collections = Mage::getModel('dhllabel/labelprice');
        $items = $collections->getCollection()->addFieldToFilter('shipment_id', $row->getId());

        if (!empty($items)) {
            foreach ($items AS $item) {
                $c .= $item->getPrice() . "<br>";
            }

            return '<div style="background-color: #FF00FF; padding-left: 5px;">' . $c . '</div>';
        } else {
            return "";
        }
    }

}
