<?php
/**
 * User: Naveed
 * Date: 8/31/17
 */
/**
 * This file run every 10 minutes to place orders from mobile app
 */
require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();
placeOrders();

function placeOrders()
{
    date_default_timezone_set('Asia/Dubai');
    $pageSize = 10;
    if ((int)Mage::getStoreConfig('api/newgroupv2/numberOfOrders') > 0) {
        $pageSize = (int)Mage::getStoreConfig('api/newgroupv2/numberOfOrders');
    }
    $failedOrders = array();
    $savedOrders = Mage::getModel('restmobv2/quote_index')
        ->getCollection()
        ->addFieldToFilter('status', array('eq' => 0))
        ->addFieldToFilter('payment_status', array('eq' => 1))
        ->setCurPage(1)
        ->setPageSize($pageSize);
    if ($savedOrders->count() > 0) {
        foreach ($savedOrders as $savedOrder) {
            $quoteId = $savedOrder->getQid();
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            if($quote->getItemsCount() == 0){
                $mdlRestmob = Mage::getModel('restmobv2/quote_index');
                $mdlRestmob->load($savedOrder->getId())->setStatus(2)->save();
                continue;
            }
            try {
                if ($quote) {
                    $payment_method = $savedOrder->getPayemntMethod();
                    $shipment_method = $savedOrder->getShippingMethod();
                    $payment_status = $savedOrder->getPaymentStatus();
                    $paymentMethod = array(
                        'po_number' => null,
                        'method' => $payment_method,
                        'cc_cid' => $savedOrder->getCcCid(),
                        'cc_owner' => $savedOrder->getCcOwner(),
                        'cc_number' => $savedOrder->getCcNumber(),
                        'cc_type' => $savedOrder->getCcType(),//'VI',//
                        'cc_exp_year' => $savedOrder->getCcExpYear(),
                        'cc_exp_month' => $savedOrder->getCcExpMonth()

                    );
                    $customerShippingMdl = Mage::getSingleton('restmob/cart_shipping_api');
                    $customerShippingMdl->setShippingMethod($quoteId, $shipment_method);// set shipping methods
                    $customerPaymentMdl = Mage::getSingleton('restmob/cart_payment_api');
                    $customerPaymentMdl->setPaymentMethod($quoteId, $paymentMethod);// set payment methods
                    if ((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay'));
                    }
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    $quote->collectTotals()->save();
                    $service = Mage::getModel('sales/service_quote', $quote);
                    $service->submitAll();
                    $order = $service->getOrder();
                    if ($order) {
                        Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                            array('order' => $order, 'quote' => $quote));
                        $order->getIncrementId();
                        $entity_id = $order->getEntityId();
                        $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Elabelz Mobile App');
                        if ($payment_method == 'telrtransparent' && $payment_status == '1') {
                            $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Gateway has authorized the payment. Reference Id = '.$savedOrder->getTelrReferenceId());
                        } elseif ($payment_method == 'telrtransparent' && $payment_status == '0') {
                            $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.');
                        }
                        Mage::dispatchEvent(
                            'checkout_submit_all_after',
                            array('order' => $order, 'quote' => $quote)
                        );
                        $order->save();
                        $order->getSendConfirmation(null);
                        $order->sendNewOrderEmail();
                        $quote->setIsActive(0)->save();
                        Mage::getModel('mageworx_ordersgrid/order_grid')->syncOrderById($entity_id);
                        $mdlRestmob = Mage::getModel('restmobv2/quote_index');
                        $mdlRestmob->load($savedOrder->getId())->setStatus(1)->setUpdatedAt(Varien_Date::now())->save();
                        Mage::log('Order Placed  quoteId= ' . $quoteId . '.. \n', null, 'cron_error.log');
                        if ((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay') > 0) {
                            sleep((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay'));
                        }
                    } else {
                        Mage::log('Error in cron getIncremetId() Error  quoteId= ' . $quoteId . '.. \n', null, 'cron_error.log');
                        continue;
                    }
                }
            } catch (Exception $e) {
                $failedOrders[] = $savedOrder;
                if (method_exists($e, 'getCustomMessage')) {
                    $message = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $message = $e->getMessage();
                }
                Mage::log('Error in cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
                continue;
            }
        }
        if (!empty($failedOrders)) {
            placeFailedOrdersWithRetry($failedOrders);
        }
    }
}

function placeFailedOrdersWithRetry($failedOrders)
{
    foreach ($failedOrders as $failedOrder) {
        for ($i = 0; $i < 6; $i++) {
            try {
                $quoteId = $failedOrder->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                if ($quote) {
                    $payment_method = $failedOrder->getPayemntMethod();
                    $shipment_method = $failedOrder->getShippingMethod();
                    $payment_status = $failedOrder->getPaymentStatus();
                    $paymentMethod = array(
                        'po_number' => null,
                        'method' => $payment_method,
                        'cc_cid' => $failedOrder->getCcCid(),
                        'cc_owner' => $failedOrder->getCcOwner(),
                        'cc_number' => $failedOrder->getCcNumber(),
                        'cc_type' => $failedOrder->getCcType(),//'VI',//
                        'cc_exp_year' => $failedOrder->getCcExpYear(),
                        'cc_exp_month' => $failedOrder->getCcExpMonth()

                    );
                    $customerShippingMdl = Mage::getSingleton('restmob/cart_shipping_api');
                    $customerShippingMdl->setShippingMethod($quoteId, $shipment_method);// set shipping methods
                    $customerPaymentMdl = Mage::getSingleton('restmob/cart_payment_api');
                    $customerPaymentMdl->setPaymentMethod($quoteId, $paymentMethod);// set payment methods
                    if ((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay'));
                    }
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    $quote->collectTotals()->save();
                    $service = Mage::getModel('sales/service_quote', $quote);
                    $service->submitAll();
                    $order = $service->getOrder();
                    if ($order) {
                        Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                            array('order' => $order, 'quote' => $quote));
                        $order->getIncrementId();
                        $entity_id = $order->getEntityId();
                        $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Elabelz Mobile App');
                        if ($payment_method == 'telrtransparent' && $payment_status == '1') {
                            $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Gateway has authorized the payment.');
                        } elseif ($payment_method == 'telrtransparent' && $payment_status == '0') {
                            $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.');
                        }
                        Mage::dispatchEvent(
                            'checkout_submit_all_after',
                            array('order' => $order, 'quote' => $quote)
                        );
                        $order->save();
                        $order->getSendConfirmation(null);
                        $order->sendNewOrderEmail();
                        $quote->setIsActive(0)->save();
                        Mage::getModel('mageworx_ordersgrid/order_grid')->syncOrderById($entity_id);
                        $mdlRestmob = Mage::getModel('restmobv2/quote_index');
                        $mdlRestmob->load($failedOrder->getId())->setStatus(1)->setUpdatedAt(Varien_Date::now())->save();
                        Mage::log('Order Placed  quoteId= ' . $quoteId . '.. \n', null, 'cron_error.log');
                        if ((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay') > 0) {
                            sleep((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay'));
                        }
                        break;
                    } else {
                        Mage::log('Error in cron getIncremetId() Error  quoteId= ' . $quoteId . '.. \n', null, 'cron_error.log');
                        if ((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay') > 0) {
                            sleep((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay'));
                        }
                        continue;
                    }
                }
            } catch (Exception $e) {
                if (method_exists($e, 'getCustomMessage')) {
                    $message = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $message = $e->getMessage();
                }
                Mage::log('Error in cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
                if ((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay') > 0) {
                    sleep((int)Mage::getStoreConfig('api/newgroupv2/checkoutDelay'));
                }
                continue;
            }
        }
    }
}