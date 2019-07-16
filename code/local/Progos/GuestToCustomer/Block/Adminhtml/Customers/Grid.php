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
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Progos_GuestToCustomer_Block_Adminhtml_Customers_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('guestToCustomerGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('sales/order')->getCollection();
		$collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('customer_id', array('null' => true));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
   
    protected function _prepareColumns()
    {
        $this->addColumn('real_order_id', array(
            'header'=> Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'increment_id',
        ));
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }
        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));
        $this->addColumn('customer_firstname', array(
            'header' => Mage::helper('sales')->__('First Name'),
            'index' => 'customer_firstname',
        ));
        $this->addColumn('customer_lastname', array(
            'header' => Mage::helper('sales')->__('Last Name'),
            'index' => 'customer_lastname',
        ));
        $this->addColumn('customer_email', array(
            'header' => Mage::helper('sales')->__('Email'),
            'index' => 'customer_email',
        ));
        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                array(
                    'header'    => Mage::helper('sales')->__('Action'),
                    'width'     => '50px',
                    'type'      => 'action',
                    'getter'     => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('sales')->__('View Order'),
                            'url'     => array('base'=>'adminhtml/sales_order/view'),
                            'field'   => 'order_id'
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => 'stores',
                    'is_system' => true,
            ));
        }
        return parent::_prepareColumns();
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);
        $groups = array();

        $customerGroupsCollection = Mage::getModel('customer/group')->getCollection()->load();
        $customerGroupsCollection->removeItemByKey(0);
        foreach ($customerGroupsCollection as $group) {
            $groups[] = array(
                'label' => $group->getCustomerGroupCode(),
                'value' => $group->getId()
            );
        }
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('catalog')->__('Convert to customer'),
             'url'  => $this->getUrl('*/*/massConvert', array('_current'=>true)),
             'additional' => array(
                 'visibility' => array(
                     'name' => 'group_id',
                     'type' => 'select',
                     'class' => 'required-entry',
                     'label' => Mage::helper('catalog')->__('Customer Group'),
                     'values' => $groups
                 )
             )
        ));
    }
    
    /**
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return false;
    }
    public function getGridUrl()
    {
        return $this->getUrl('*/*/index', array('_current'=>true));
    }
}