<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
?>
<?php
/**
 * Class Progos_OrdersEdit_Block_Adminhtml_Sales_Order_View
 */
class Progos_OrdersEdit_Block_Adminhtml_Sales_Order_View extends MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_View
{
    /**
     * Progos_OrdersEdit_Block_Adminhtml_Sales_Order_View constructor.
     */
    public function __construct()
    {
        /** Begin: Add Aramex buttons to order view page */
        $itemsCount = 0;
        $totalWeight = 0;
        $_order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));
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

        if ($itemsCount == 0) {
            $this->_addButton('print_aramex_label', array(
                'label' => Mage::helper('Sales')->__('Aramex Print Label'),
                'onclick' => "myObj.printLabel()",
                'class' => 'go'
            ), 10, 200, 'header', 'header');
        }
        /** End: Add Aramex buttons to order view page */

        $this->_helper = Mage::helper('mageworx_ordersedit');
        parent::__construct();
    }
}