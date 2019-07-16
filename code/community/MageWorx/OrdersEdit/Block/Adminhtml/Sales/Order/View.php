<?php
/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_View extends MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_View_Abstract
{
    /** @var MageWorx_OrdersEdit_Helper_Data|null  */
    protected $_helper = null;

    public function __construct()
    {
        $this->_helper = Mage::helper('mageworx_ordersedit');
        parent::__construct();
    }

    public function getPrintUrl(){
        return $this->getUrl('marketplaceadmin/adminhtml_order/saveBuyerConfirmation',array('orderId'=>$this->getOrder()->getId()));
    }

    /**
     * @param $action
     * @return mixed
     */
    protected function _isAllowedAction($action)
    {
        /** Begin: Add Aramex buttons to order view page */
        $itemsCount = 0;
        $totalWeight = 0;
        $_order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));
        $url = $this->getUrl('marketplaceadmin/adminhtml_order/saveBuyerConfirmation',array('orderId'=>$this->getRequest()->getParam('order_id')));

        $itemsv = $_order->getAllVisibleItems();
        foreach ($itemsv as $itemvv) {
            if ($itemvv->getQtyOrdered() > $itemvv->getQtyShipped()) {
                $itemsCount += $itemvv->getQtyOrdered() - $itemvv->getQtyShipped();
            }
            if ($itemvv->getWeight() != 0) {
                $weight = $itemvv->getWeight() * $itemvv->getQtyOrdered();
            } else {
                $weight = 0.5 * $itemvv->getQtyOrdered();
            }
            $totalWeight += $weight;
        }

        $shipments = Mage::getResourceModel('sales/order_shipment_collection')
            ->addAttributeToSelect('*')
            ->addFieldToFilter("order_id", $_order->getId())->join("sales/shipment_comment",
                'main_table.entity_id=parent_id', 'comment')->addFieldToFilter('comment',
                array('like' => "%{$_order->getIncrementId()}%"))->load();

        $aramex_return_button = false;

        if ($shipments->count()) {
            foreach ($shipments as $key => $comment) {
                if (version_compare(PHP_VERSION, '5.3.0') <= 0) {
                    $awbno = substr($comment->getComment(), 0, strpos($comment->getComment(), "- Order No"));
                } else {
                    $awbno = strstr($comment->getComment(), "- Order No", true);
                }
                $awbno = trim($awbno, "AWB No.");
                break;
            }
            if ((int)$awbno) {
                $aramex_return_button = true;
            }
        }

        if ($_order->canShip()) {
            $this->_addButton('create_aramex_shipment', array(
                'label' => Mage::helper('Sales')->__('Prepare Aramex Shipment'),
                'onclick' => 'aramexpop(' . $itemsCount . ')',
                'class' => 'go'
            ), 10, 100, 'header', 'header');

        } elseif (!$_order->canShip() && $aramex_return_button) {
            // print_r("not here");
            $this->_addButton('create_aramex_shipment', array(
                'label' => Mage::helper('Sales')->__('Return Aramex Shipment'),
                'onclick' => 'aramexreturnpop(' . $itemsCount . ')',
                'class' => 'go'
            ), 10, 100, 'header', 'header');
        }

        //SMSA Express Integration Start . To Show SMSA Express Button
        $smsaexpresshelper = Mage::helper('progos_smsaexpress');

        if( $smsaexpresshelper->getStatus() ){
            if ($_order->canShip()) {
                $this->_addButton('create_smsaexpress_shipment', array(
                    'label' => Mage::helper('Sales')->__('Prepare SmsaExpress Shipment'),
                    'onclick' => 'smsaexpresspop(' . $itemsCount . ')',
                    'class' => 'go'
                ), 10, 300, 'header', 'header');

            } /*elseif (!$_order->canShip() && $aramex_return_button) {
                // print_r("not here");
                $this->_addButton('create_smsaexpress_shipment', array(
                    'label' => Mage::helper('Sales')->__('Return SmsaExpress Shipment'),
                    'onclick' => 'smsaexpressreturnpop(' . $itemsCount . ')',
                    'class' => 'go'
                ), 10, 300, 'header', 'header');
            }*/
        }
        //SMSA Express Integration End
        if(Mage::getStoreConfig('speedex/settings/status') && $_order->canShip()) {
        $this->_addButton('create_speedex_shipment', array(
                'label'     => Mage::helper('Sales')->__('Prepare Speedex Shipment'),
                'onclick'   => 'speedexpop('. $itemsCount. ')',
                'class'     => 'go'
            ), 10, 100, 'header', 'header');
        }

        $this->_addButton('accept_all_customer', array(
            'label' => Mage::helper('Sales')->__('Accept All Customer Items'),
            'onclick' => 'setLocation(\''.$url.'\')',
            'class' => 'go'
        ), 10, 500, 'header', 'header');
        
        if ($itemsCount == 0) {
            $this->_addButton('print_aramex_label', array(
                'label' => Mage::helper('Sales')->__('Aramex Print Label'),
                'onclick' => "myObj.printLabel()",
                'class' => 'go'
            ), 10, 200, 'header', 'header');
        }
        
        if( $smsaexpresshelper->getStatus() ){
            if ($itemsCount == 0) {
                $this->_addButton('print_smsaexpress_label', array(
                    'label' => Mage::helper('Sales')->__('SmsaExpress Print Label'),
                    'onclick' => "smsaexpressObj.printLabel()",
                    'class' => 'go'
                ), 10, 400, 'header', 'header');
            }
        }

        /** End: Add Aramex buttons to order view page */

        if ($action == 'emails' && $this->_helper->isEnabled() && $this->_helper->isEnableDeleteOrdersCompletely() && Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersedit/actions/delete_completely')) {
            $message = $this->_helper->__('Are you sure you want to completely delete this order?');
            $this->_addButton('order_delete', array(
                    'label' => $this->_helper->__('Delete'),
                    'onclick' => 'deleteConfirm(\'' . $message . '\', \'' . $this->getUrl('adminhtml/mageworx_ordersedit/massDeleteCompletely') . '\')',
                    'class' => 'delete'
                )
            );
        }
        return parent::_isAllowedAction($action);
    }

    /**
     * @return string
     */
    public function getHeaderText()
    {
        $text = parent::getHeaderText();
        if ($this->_helper->isEnabled() && $this->getOrder()->getIsEdited()) {
            $text .= ' (' . $this->_helper->__('Edited') . ')';
        }
        return $text;
    }

    public function _beforeToHtml()
    {
        parent::_beforeToHtml();
        if ($this->_helper->isHideEditButton() || !$this->_helper->isOrderEditable($this->getOrder())) {
            $this->_removeButton('order_edit');
        }
    }
}