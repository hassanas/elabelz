<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 28.12.11
 * Time: 9:38
 * To change this template use File | Settings | File Templates.
 */
require_once Mage::getBaseDir('app') .
    '/code/community/Infomodus/Dhllabel/controllers/Adminhtml/Dhllabel/DhllabelController.php';

class Infomodus_Dhllabel_Model_Observer
{
    public function initDhllabel($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction
            || $block instanceof Enterprise_SalesArchive_Block_Adminhtml_Sales_Order_Grid_Massaction
        ) {
            $type = '';
            if ($block->getRequest()->getControllerName() == 'sales_order') {
                $type = 'order';
            } elseif ($block->getRequest()->getControllerName() == 'sales_shipment') {
                $type = 'shipment';
            } elseif ($block->getRequest()->getControllerName() == 'sales_creditmemo') {
                $type = 'creditmemo';
            }

            if (!empty($type)) {
                $block->addItem(
                    'dhllabel_pdflabels',
                    array(
                        'label' => Mage::helper('dhllabel')->__('Print DHL Shipping Labels'),
                        'url' => Mage::app()->getStore()->getUrl(
                            'adminhtml/dhllabel_pdflabels',
                            array('type' => $type)
                        ),
                    )
                );
                if ($type == 'order') {
                    $block->addItem(
                        'dhllabel_autocreatelabel',
                        array(
                            'label' => Mage::helper('dhllabel')->__('Create DHL Labels for Orders'),
                            'url' => Mage::app()->getStore()->getUrl(
                                'adminhtml/dhllabel_autocreatelabel',
                                array('type' => $type)
                            ),
                        )
                    );
                }
            }
        }

        return $this;
    }

    public function saveShipment(Varien_Event_Observer $event)
    {
        if (Mage::app()->getRequest()->getControllerName() == 'sales_order_shipment'
            && Mage::app()->getRequest()->getActionName() == 'save'
        ) {
            $paramShipment = Mage::app()->getRequest()->getParam('shipment', null);
            if ($paramShipment !== null && isset($paramShipment['dhllabel_create'])
                && $paramShipment['dhllabel_create'] == 1
            ) {
                Mage::app()->getResponse()->setRedirect(
                    Mage::getUrl(
                        'adminhtml/dhllabel_dhllabel/intermediate',
                        array('order_id' => Mage::registry('dhllabel_order_id'),
                            'shipment_id' => Mage::registry('dhllabel_shipment_id'), 'type' => 'shipment'
                        )
                    )
                );
                Mage::app()->getResponse()->sendResponse();
                return;
            }
        } elseif (Mage::app()->getRequest()->getControllerName() == 'sales_order_creditmemo'
            && Mage::app()->getRequest()->getActionName() == 'save'
        ) {
            $paramShipment = Mage::app()->getRequest()->getParam('creditmemo', null);
            if ($paramShipment !== NULL && isset($paramShipment['dhllabel_create'])
                && $paramShipment['dhllabel_create'] == 1
            ) {
                Mage::app()->getResponse()->setRedirect(
                    Mage::getUrl(
                        'adminhtml/dhllabel_dhllabel/intermediate',
                        array(
                            'order_id' => Mage::registry('dhllabel_order_id'),
                            'shipment_id' => Mage::registry('dhllabel_shipment_id'),
                            'type' => 'refund'
                        )
                    )
                );

                Mage::app()->getResponse()->sendResponse();
                return;
            }
        }
    }

    public function beforeSaveShipment(Varien_Event_Observer $event)
    {
        $request = Mage::app()->getRequest();
        if ($request->getControllerName() == 'sales_order_shipment'
            && $request->getActionName() == 'save'
        ) {
            $paramShipment = $request->getParam('shipment', null);
            if ($paramShipment !== null && isset($paramShipment['dhllabel_create'])
                && $paramShipment['dhllabel_create'] == 1
            ) {
                $shipment = $event->getEvent()->getShipment();
                if ($shipment) {
                    $shipment->setEmailSent(true);
                }
            }
        } elseif ($request->getControllerName() == 'sales_order_creditmemo'
            && $request->getActionName() == 'save'
        ) {
            $paramShipment = $request->getParam('creditmemo', null);
            if ($paramShipment !== NULL && isset($paramShipment['dhllabel_create'])
                && $paramShipment['dhllabel_create'] == 1
            ) {
                $shipment = $event->getEvent()->getCreditmemo();
                if ($shipment) {
                    $shipment->setEmailSent(true);
                }
            }
        }

        return $this;
    }

    public function beforeShipment(Varien_Event_Observer $event)
    {
        $shipment = $event->getEvent()->getShipment();
        if (Mage::registry('dhllabel_order_id') === NULL) {
            Mage::register('dhllabel_order_id', $shipment->getOrderId());
            Mage::register('dhllabel_shipment_id', $shipment->getId());
        }

        return $this;
    }

    public function beforeCreditmemo(Varien_Event_Observer $event)
    {
        $shipment = $event->getEvent()->getCreditmemo();
        if (Mage::registry('dhllabel_order_id') === NULL) {
            Mage::register('dhllabel_order_id', $shipment->getOrderId());
            Mage::register('dhllabel_shipment_id', $shipment->getId());
        }

        return $this;
    }

    public function addbutton($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View) {
            $block->removeButton('order_label_dhl');
            $shipmentId = $block->getShipment()->getId();
            if ($shipmentId) {
                $orderId = $block->getShipment()->getOrderId();
                if ($orderId) {
                    $collections = Mage::getModel('dhllabel/dhllabel');
                    $collection = $collections->getCollection()->addFieldToFilter('shipment_id', $shipmentId)
                        ->addFieldToFilter('type', 'shipment')->addFieldToFilter('status', 0)
                        ->getFirstItem();
                    if ($collection->getShipmentId() == $shipmentId) {
                        $block->addButton(
                            'order_label_dhl',
                            array(
                                'label' => Mage::helper('dhllabel')->__('DHL Label'),
                                'onclick' => 'setLocation(\'' .
                                    $block->getUrl(
                                        'adminhtml/dhllabel_dhllabel/showlabel/order_id/' .
                                        $orderId . '/shipment_id/' . $shipmentId . '/type/shipment'
                                    ) . '\')',
                                'class' => 'go'
                            )
                        );
                    } else {
                        $block->addButton(
                            'order_label_dhl',
                            array(
                                'label' => Mage::helper('dhllabel')->__('DHL Label'),
                                'onclick' => 'setLocation(\'' .
                                    $block->getUrl(
                                        'adminhtml/dhllabel_dhllabel/intermediate/order_id/' .
                                        $orderId . '/shipment_id/' . $shipmentId . '/type/shipment'
                                    ) . '\')',
                                'class' => 'go'
                            )
                        );
                    }
                }
            }
        }

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Creditmemo_View) {
            $block->removeButton('cancel_dhl');
            $shipmentId = $block->getCreditmemo()->getId();
            $orderId = $block->getCreditmemo()->getOrderId();
            if ($shipmentId) {
                $collections = Mage::getModel('dhllabel/dhllabel');
                $collection = $collections->getCollection()->addFieldToFilter('shipment_id', $shipmentId)
                    ->addFieldToFilter('type', 'refund')->addFieldToFilter('status', 0)->getFirstItem();
                if ($collection->getShipmentId() != $shipmentId) {
                    $block->addButton('cancel_dhl', array(
                            'label' => Mage::helper('dhllabel')->__('DHL label'),
                            'class' => 'save',
                            'onclick' => 'setLocation(\'' . $block->getUrl('adminhtml/dhllabel_dhllabel/intermediate/order_id/' . $orderId . '/shipment_id/' . $shipmentId . '/type/refund') . '\')'
                        )
                    );
                } else {
                    $block->addButton('cancel_dhl', array(
                            'label' => Mage::helper('dhllabel')->__('DHL label'),
                            'class' => 'save',
                            'onclick' => 'setLocation(\'' . $block->getUrl('adminhtml/dhllabel_dhllabel/showlabel/order_id/' . $orderId . '/shipment_id/' . $shipmentId . '/type/refund') . '\')'
                        )
                    );
                }
            }
        }

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid && Mage::getStoreConfig('dhllabel/additional_settings/order_grid_column_enable') == 1) {
            $block->addColumnAfter(
                'statuslabeldhl',
                array(
                    'header' => Mage::helper('dhllabel')->__('DHL label status'),
                    'index' => 'statuslabeldhl',
                    'type' => 'options',
                    'width' => '120px',
                    'sortable' => false,
                    'frame_callback' => array($this, 'callback_dhlstatus'),
                    'filter_condition_callback' => array($this, '_orderDhlStatusFilter'),
                    'options' => Mage::getModel('dhllabel/config_statuslabels')->getStatus(),
                ),
                'status'
            );
        }
    }

    public function callback_dhlstatus($value, $row, $column, $isExport)
    {
        $collections = Mage::getModel('dhllabel/dhllabel');
        $item = $collections->getCollection()->addFieldToFilter('order_id', $row->getId())->addFieldToFilter('type', 'shipment')->getFirstItem();
        if ($item->getStatustext()) {
            $priceHtml = '';
            $price = Mage::getModel('dhllabel/labelprice')->getCollection()->addFieldToFilter('order_id', $row->getId())->getFirstItem();
            if ($price->getPrice()) {
                $priceHtml = Mage::helper('adminhtml')->__('Price') . ': ' . $price->getPrice() . '<br>';
            }

            return $priceHtml . '' . $item->getStatustext();
        } else {
            $order = Mage::getModel('sales/order')->load($row->getId());
            /*multistore*/
            $storeId = NULL;
            $storeId = $order->getStoreId();
            $this->storeId = $storeId;
            /*multistore*/
            $shippingActiveMethods = trim(Mage::getStoreConfig('dhllabel/frontend_autocreate_label/apply_to', $storeId), " ,");
            $shippingActiveMethods = strlen($shippingActiveMethods) > 0 ? explode(",", $shippingActiveMethods) : array();
            $currentShippingMethod = explode("_", $order->getShippingMethod());
            if ((isset($shippingActiveMethods) && count($shippingActiveMethods) == 0 && in_array($currentShippingMethod[0], $shippingActiveMethods)) || strpos($order->getShippingMethod(), "dhlint_") === 0) {
                return Mage::helper('adminhtml')->__('DHL Pending');
            }
        }
    }

    public function _orderDhlStatusFilter($collection, $column, $type = "shipment", $id = "order_id")
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $status = 0;
        $isNeedFilter = true;
        switch ($value) {
            case "success":
                $statustext = '="Success"';
                $status = 0;
                break;
            case "error":
                $statustext = ' LIKE "%Error%"';
                $status = 1;
                break;
            case "notcreated":
                $isNeedFilter = false;
                break;
            case "pending":
                $isNeedFilter = false;
                break;
        }

        if ($isNeedFilter == true) {
            $collection->getSelect()->distinct(true)->join(
                array(
                    "t123dhl" => Mage::getConfig()->getTablePrefix() . 'dhllabel'), 'main_table.entity_id=t123dhl.' . $id . ' AND t123dhl.type="' . $type . '" AND t123dhl.status="' . $status . '" AND t123dhl.statustext' . $statustext, NULL);
            $collection->getSelect()->setPart('where', str_replace(array("(status ", " status "), array('(main_table.status ', ' main_table.status '), $collection->getSelect()->getPart('where')));
            /*$query = $collection->getSelect();
            echo $query; echo $value; exit;*/
        } else {
            if ($value == "pending") {
                /*multistore*/
                $cselect = $collection->getSelect()->getPart('where');
                $storeId = NULL;
                if (isset($cselect['store_id'])) {
                    $storeId = $cselect['store_id'];
                }

                /*multistore*/
                $shippingActiveMethods = trim(Mage::getStoreConfig('dhllabel/frontend_autocreate_label/apply_to', $storeId), " ,");
                $shippingActiveMethods = strlen($shippingActiveMethods) > 0 ? explode(",", $shippingActiveMethods) : array();
                $like = array();
                $like[] = "t1orderdhl.shipping_method LIKE \"dhlint_%\"";
                if (count($shippingActiveMethods) > 0) {
                    foreach ($shippingActiveMethods AS $item) {
                        $like[] = "t1orderdhl.shipping_method LIKE \"" . $item . "\"";
                    }

                    $like = "(" . implode(" OR ", $like) . ")";
                } else {
                    $like = "t1orderdhl.shipping_method LIKE \"dhlint_%\"";
                }

                $entityId = "entity_id";
                if ($id != "order_id") {
                    $entityId = "order_id";
                }

                $collection->getSelect()->distinct(true)->join(array("t1orderdhl" => Mage::getConfig()->getTablePrefix() . 'sales_flat_order'), 'main_table.' . $entityId . ' = t1orderdhl.entity_id AND ' . $like, NULL)->joinLeft(array("t123dhl" => Mage::getConfig()->getTablePrefix() . 'dhllabel'), 'main_table.entity_id = t123dhl.' . $id . ' AND t123dhl.type="' . $type . '"', NULL);
            } else {
                $collection->getSelect()->distinct(true)->joinLeft(array("t123dhl" => Mage::getConfig()->getTablePrefix() . 'dhllabel'), 'main_table.entity_id = t123dhl.' . $id . ' AND t123dhl.type="' . $type . '"', NULL);
            }

            $collection->getSelect()->where("t123dhl." . $id . " IS NULL");
            $collection->getSelect()->setPart('where', str_replace(array("(status ", " status "), array('(main_table.status ', ' main_table.status '), $collection->getSelect()->getPart('where')));
            /* $query = $collection->getSelect();
            echo $query; exit;*/
        }

        return $this;
    }

    public function frontorderplace(Varien_Event_Observer $event)
    {
        if (Mage::registry('isCreateLabelNow') == 2) {
            return true;
        }

        $order = $event->getEvent()->getOrder();
        /*if (Mage::registry("dhlCanShip")==1) {
            return $this;
        }*/
        if (Mage::getStoreConfig('dhllabel/frontend_autocreate_label/frontend_order_autocreate_label_enable' /*multistore*/, $order->getStoreId() /*multistore*/) == 1) {
            /*multistore*/
            $storeId = null;
            $storeId = $order->getStoreId();
            /*multistore*/
            $shippingActiveMethods = trim(Mage::getStoreConfig('dhllabel/frontend_autocreate_label/apply_to', $storeId), " ,");
            $shippingActiveMethods = strlen($shippingActiveMethods) > 0 ? explode(",", $shippingActiveMethods) : array();
            $orderStatuses = explode(",", trim(Mage::getStoreConfig('dhllabel/frontend_autocreate_label/orderstatus', $storeId), " ,"));
            if (
                (
                    (
                        !empty($shippingActiveMethods)
                        && in_array($order->getShippingMethod(), $shippingActiveMethods)
                    )
                    || strpos($order->getShippingMethod(), "dhlint_") === 0
                )
                && (
                    !empty($orderStatuses)
                    && in_array($order->getStatus(), $orderStatuses)
                )
            ) {
                $order_id = $order->getId();
                $type = 'shipment';
                $collections = Mage::getModel('dhllabel/dhllabel');
                $colls = $collections->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('type', $type);
                if (count($colls) == 0) {
                    $controller = new Infomodus_Dhllabel_Adminhtml_Dhllabel_DhllabelController();
                    $controller->intermediatehandy($order_id, $type);

                    $lbl = Mage::getModel('dhllabel/dhl');
                    $lbl = $controller->setParams($lbl, $controller->defConfParams, $controller->defParams /*multistore*/, $storeId /*multistore*/, $order);
                    $upsl = $lbl->getShip( /*multistore*/
                        $storeId /*multistore*/);
                    $upsl2 = null;
                    if ($controller->defConfParams['default_return'] == 1) {
                        $lbl->serviceCode = array_key_exists('default_return_servicecode', $controller->defConfParams) ? $controller->defConfParams['default_return_servicecode'] : '';
                        $lbl->serviceGlobalCode = array_key_exists('default_return_servicecode', $controller->defConfParams) ? $controller->defConfParams['default_return_servicecode'] : '';
                        $upsl2 = $lbl->getShipFrom( /*multistore*/
                            $storeId /*multistore*/);
                    }
                    if (!Mage::registry('isCreateLabelNow')) {
                        Mage::register('isCreateLabelNow', 2);
                    }
                    $controller->saveDB($upsl, $upsl2, $controller->defConfParams, $order_id, 0, $type, $lbl);
                }
            }
        }

        return $this;
    }
}

