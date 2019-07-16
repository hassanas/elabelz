<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Touqeer Jalal <touqeer.jalal@progos.org>
 * @Modified    Saroop Chand <saroop.chand@progos.org> 22-02-2018
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
class Progos_CustomOrderFlags_Model_Observer 
{

    // This function is called on core_block_abstract_to_html_after event
    // We will append our block to the html
    public function getSalesOrderViewInfo(Varien_Event_Observer $observer) {
        $block = $observer->getBlock();
        // layout name should be same as used in app/design/adminhtml/default/default/layout/progos_customorderflags.xml
        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('customorderflags.order.info.custom.block'))) {
            $transport = $observer->getTransport();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }

    public function beforeBlockToHtml(Varien_Event_Observer $observer) {
        $grid = $observer->getBlock();

        /**
         * Mage_Adminhtml_Block_Sales_Order_Grid
         */
        //System/Configuration/Mageworx/Order Management/Custom Order Flag
        $isremoveDependencyOnOrderTable = Mage::getStoreConfig('mageworx_ordersmanagement/customorderflag/enabled');
        if ($grid instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            if(  $isremoveDependencyOnOrderTable ) {
                $grid->addColumnAfter('oos_status', array(
                    'header' => Mage::helper('sales')->__('Oos Status'),
                    'index' => 'oos_status',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => Mage::getModel('customorderflags/source_oos')->toOptionArray(true),
                ), 'status');
            }else{
                $grid->addColumnAfter('oos_status', array(
                    'header' => Mage::helper('sales')->__('Oos Status'),
                    'index' => 'oos_status',
                    'type' => 'options',
                    'width' => '70px',
                    'filter_condition_callback' => array($this, '_orderOosStatusFilter'),
                    'options' => Mage::getModel('customorderflags/source_oos')->toOptionArray(true),
                ), 'status');
            }
        }
        if ($grid instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            if(  $isremoveDependencyOnOrderTable ) {
                $grid->addColumnAfter('preffered_courier', array(
                    'header' => Mage::helper('sales')->__('Preffered Courier'),
                    'index' => 'preffered_courier',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => Mage::getModel('customorderflags/source_prefferedCourier')->toOptionArray(true),
                ), 'status');
            }else{
                $grid->addColumnAfter('preffered_courier', array(
                    'header' => Mage::helper('sales')->__('Preffered Courier'),
                    'index' => 'preffered_courier',
                    'type' => 'options',
                    'width' => '70px',
                    'filter_condition_callback' => array($this, '_orderPrefferedCourierFilter'),
                    'options' => Mage::getModel('customorderflags/source_prefferedCourier')->toOptionArray(true),
                ), 'status');
            }
        }
        if ($grid instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            if(  $isremoveDependencyOnOrderTable ) {
                $grid->addColumnAfter('customer_flag', array(
                    'header' => Mage::helper('sales')->__('Customer Flag'),
                    'index' => 'customer_flag',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => Mage::getModel('customorderflags/source_customerFlag')->toOptionArray(true),
                ), 'status');
            }else{
                $grid->addColumnAfter('customer_flag', array(
                    'header' => Mage::helper('sales')->__('Customer Flag'),
                    'index' => 'customer_flag',
                    'type' => 'options',
                    'width' => '70px',
                    'filter_condition_callback' => array($this, '_orderCustomerFlagFilter'),
                    'options' => Mage::getModel('customorderflags/source_customerFlag')->toOptionArray(true),
                ), 'status');
            }

        }

        if ($grid instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            if(  $isremoveDependencyOnOrderTable ) {
                $grid->addColumnAfter('upsstatus', array(
                    'header' => Mage::helper('sales')->__('Ups Status'),
                    'index' => 'upsstatus',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => Mage::getModel('customorderflags/source_upsstatus')->toOptionArray(true),
                ), 'status');
            }else{
                $grid->addColumnAfter('upsstatus', array(
                    'header' => Mage::helper('sales')->__('Ups Status'),
                    'index' => 'upsstatus',
                    'type' => 'options',
                    'width' => '70px',
                    'filter_condition_callback' => array($this, '_orderUpsstatusFlagFilter'),
                    'options' => Mage::getModel('customorderflags/source_upsstatus')->toOptionArray(true),
                ), 'status');
            }

        }

        if ($grid instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            if(  $isremoveDependencyOnOrderTable ) {
                $grid->addColumnAfter('dhlstatus', array(
                    'header' => Mage::helper('sales')->__('Dhl Status'),
                    'index' => 'dhlstatus',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => Mage::getModel('customorderflags/source_dhlstatus')->toOptionArray(true),
                ), 'upsstatus');
            }else{
                $grid->addColumnAfter('dhlstatus', array(
                    'header' => Mage::helper('sales')->__('Dhl Status'),
                    'index' => 'dhlstatus',
                    'type' => 'options',
                    'width' => '70px',
                    'filter_condition_callback' => array($this, '_orderDhlstatusFlagFilter'),
                    'options' => Mage::getModel('customorderflags/source_dhlstatus')->toOptionArray(true),
                ), 'upsstatus');
            }

        }

        if ($grid instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            if(  $isremoveDependencyOnOrderTable ) {
                $grid->addColumnAfter('aramexstatus', array(
                    'header' => Mage::helper('sales')->__('Aramex Status'),
                    'index' => 'aramexstatus',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => Mage::getModel('customorderflags/source_aramexstatus')->toOptionArray(true),
                ), 'dhlstatus');
            }else{
                $grid->addColumnAfter('aramexstatus', array(
                    'header' => Mage::helper('sales')->__('Aramex Status'),
                    'index' => 'aramexstatus',
                    'type' => 'options',
                    'width' => '70px',
                    'filter_condition_callback' => array($this, '_orderAramexstatusFlagFilter'),
                    'options' => Mage::getModel('customorderflags/source_aramexstatus')->toOptionArray(true),
                ), 'dhlstatus');
            }

        }
    }

    public function _orderOosStatusFilter($collection, $column, $id = "oos_status") {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $whereCondition = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
        $searchword = 'order';
        $matchesOrderwhereCondition = array_filter($whereCondition, function($var) use ($searchword) {
            return preg_match("/\b$searchword\b/i", $var);
        });
        if (empty($matchesOrderwhereCondition)) { // if already applied order join condition than no need to again join
            $orderCollection = Mage::getResourceModel('sales/order_collection');
            $orderCollection->getSelect()
                    ->reset(Zend_Db_Select::COLUMNS)
                    ->columns(['order_id' => 'entity_id', 'customer_flag', 'preffered_courier', 'oos_status','upsstatus','dhlstatus','aramexstatus']);

            $collection->getSelect()->distinct(true)
                    ->joinLeft(['order' => $orderCollection->getSelect()], 'order.order_id = main_table.entity_id', [$id]);
        }
        $collection->getSelect()->where(" order." . $id . " = " . $value);

        return $this;
    }

    public function _orderPrefferedCourierFilter($collection, $column, $id = "preffered_courier") {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $whereCondition = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
        $searchword = 'order';
        $matchesOrderwhereCondition = array_filter($whereCondition, function($var) use ($searchword) {
            return preg_match("/\b$searchword\b/i", $var);
        });
        if (empty($matchesOrderwhereCondition)) { // if already applied order join condition than no need to again join
            $orderCollection = Mage::getResourceModel('sales/order_collection');
            $orderCollection->getSelect()
                    ->reset(Zend_Db_Select::COLUMNS)
                    ->columns(['order_id' => 'entity_id', 'customer_flag', 'preffered_courier', 'oos_status','upsstatus','dhlstatus','aramexstatus']);

            $collection->getSelect()->distinct(true)
                    ->joinLeft(['order' => $orderCollection->getSelect()], 'order.order_id = main_table.entity_id', [$id]);
        }
        $collection->getSelect()->where(" order." . $id . " = " . $value);

        return $this;
    }

    public function _orderCustomerFlagFilter($collection, $column, $id = "customer_flag") {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $whereCondition = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
        $searchword = 'order';
        $matchesOrderwhereCondition = array_filter($whereCondition, function($var) use ($searchword) {
            return preg_match("/\b$searchword\b/i", $var);
        });
        if (empty($matchesOrderwhereCondition)) { // if already applied order join condition than no need to again join
            $orderCollection = Mage::getResourceModel('sales/order_collection');
            $orderCollection->getSelect()
                    ->reset(Zend_Db_Select::COLUMNS)
                    ->columns(['order_id' => 'entity_id', 'customer_flag', 'preffered_courier', 'oos_status','upsstatus','dhlstatus','aramexstatus']);

            $collection->getSelect()->distinct(true)
                    ->joinLeft(['order' => $orderCollection->getSelect()], 'order.order_id = main_table.entity_id', [$id]);
        }
        $collection->getSelect()->where(" order." . $id . " = " . $value);

        return $this;
    }

    public function _orderUpsstatusFlagFilter($collection, $column, $id = "upsstatus") {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $whereCondition = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
        $searchword = 'order';
        $matchesOrderwhereCondition = array_filter($whereCondition, function($var) use ($searchword) {
            return preg_match("/\b$searchword\b/i", $var);
        });
        if (empty($matchesOrderwhereCondition)) { // if already applied order join condition than no need to again join
            $orderCollection = Mage::getResourceModel('sales/order_collection');
            $orderCollection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(['order_id' => 'entity_id', 'customer_flag', 'preffered_courier', 'oos_status','upsstatus','dhlstatus','aramexstatus']);

            $collection->getSelect()->distinct(true)
                ->joinLeft(['order' => $orderCollection->getSelect()], 'order.order_id = main_table.entity_id', [$id]);
        }
        $collection->getSelect()->where(" order." . $id . " = " . $value);

        return $this;
    }

    public function _orderDhlstatusFlagFilter($collection, $column, $id = "dhlstatus") {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $whereCondition = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
        $searchword = 'order';
        $matchesOrderwhereCondition = array_filter($whereCondition, function($var) use ($searchword) {
            return preg_match("/\b$searchword\b/i", $var);
        });
        if (empty($matchesOrderwhereCondition)) { // if already applied order join condition than no need to again join
            $orderCollection = Mage::getResourceModel('sales/order_collection');
            $orderCollection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(['order_id' => 'entity_id', 'customer_flag', 'preffered_courier', 'oos_status','upsstatus','dhlstatus','aramexstatus']);

            $collection->getSelect()->distinct(true)
                ->joinLeft(['order' => $orderCollection->getSelect()], 'order.order_id = main_table.entity_id', [$id]);
        }
        $collection->getSelect()->where(" order." . $id . " = " . $value);

        return $this;
    }

    public function _orderAramexstatusFlagFilter($collection, $column, $id = "aramexstatus") {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $whereCondition = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
        $searchword = 'order';
        $matchesOrderwhereCondition = array_filter($whereCondition, function($var) use ($searchword) {
            return preg_match("/\b$searchword\b/i", $var);
        });
        if (empty($matchesOrderwhereCondition)) { // if already applied order join condition than no need to again join
            $orderCollection = Mage::getResourceModel('sales/order_collection');
            $orderCollection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(['order_id' => 'entity_id', 'customer_flag', 'preffered_courier', 'oos_status','upsstatus','dhlstatus','aramexstatus']);

            $collection->getSelect()->distinct(true)
                ->joinLeft(['order' => $orderCollection->getSelect()], 'order.order_id = main_table.entity_id', [$id]);
        }
        $collection->getSelect()->where(" order." . $id . " = " . $value);

        return $this;
    }

    /**
     * Add join of order collection table for custom order flags
     *
     * Event: sales_order_grid_collection_load_before
     * Observer Name: customorderflags_add_custom_columns_select
     *
     * @param $observer
     * @return void
     */
    public function addOrderTable($observer) {
        //System/Configuration/Mageworx/Order Management/Custom Order Flag
        $isremoveDependencyOnOrderTable = Mage::getStoreConfig('mageworx_ordersmanagement/customorderflag/enabled');
        if( $isremoveDependencyOnOrderTable )
            return;

        $orderGridCollection = $observer->getOrderGridCollection();
		$from = $orderGridCollection->getSelect()->getPart('from');
		if(!isset($from['customFlagsOrderTable']))
		{
			$orderCollection = Mage::getResourceModel('sales/order_collection');
			$orderCollection->getSelect()
					->reset(Zend_Db_Select::COLUMNS)
					->columns(['order_id' => 'entity_id', 'oos_status', 'preffered_courier', 'customer_flag','upsstatus','dhlstatus','aramexstatus']);

			$orderGridCollection->getSelect()
					->joinLeft(['customFlagsOrderTable' => $orderCollection->getSelect()], 'customFlagsOrderTable.order_id = main_table.entity_id', ['oos_status', 'preffered_courier', 'customer_flag','upsstatus','dhlstatus','aramexstatus']);

			$this->applyDefaultGroupFilter($orderGridCollection);

			$observer->setOrderGridCollection($orderGridCollection);
		}
        return;
    }

    /**
     * Add default filter to collection
     * Do not show archived/deleted orders
     *
     * @param $collection
     */
    protected function applyDefaultGroupFilter($collection) {
        $setDefaultFilter = true;
        $where = $collection->getSelect()->getPart('where');

        if (!empty($where)) {
            foreach ($where as $part) {
                if (stripos($part, 'order_group_id') !== false) {
                    $setDefaultFilter = false;
                    break;
                }
            }
        }

        if ($setDefaultFilter) {
            /** @var Varien_Db_Select $select */
            $select = $collection->getSelect();
            $where = $select->getPart('where');
            $and = '';
            if (!empty($where)) {
                $and = 'AND ';
            }
            $where[] = $and . "(main_table.order_group_id = '0')";
            $select->setPart('where', $where);
        }
    }

    /**
     * observer method to get track data against
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function aramexShipmentTrackSaveAfter(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $track = $event->getTrack();
        if( $track ){
            if( $track->getCarrierCode() == 'aramex' ){
                $aramexCollection = Mage::getModel('customorderflags/aramexlabel')
                    ->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('trackingnumber', array('eq' => $track->getTrackNumber()));

                if (empty($aramexCollection->getData())) {
                    $aramex = Mage::getModel('customorderflags/aramexlabel')->load();
                    $aramex->setTrackingnumber($track->getTrackNumber());
                    $aramex->setOrderId($track->getOrderId());
                    $aramex->setCreatedTime($track->getCreatedAt());
                    $aramex->setUpdateTime(date('Y-m-d H:i:s'));
                    $aramex->save();
                }
            }
        }
    }
}