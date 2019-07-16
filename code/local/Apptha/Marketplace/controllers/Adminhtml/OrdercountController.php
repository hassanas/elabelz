<?php
/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     0.1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 *
 */

/**
 * Order view management
 * This class has been used to manange the order view in admin section like
 * crdit, mass crdit, transaction actions
 */
class Apptha_Marketplace_Adminhtml_OrdercountController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Index action.
     *
     * @return void
     */
    public function indexAction()
    {
        $postData = $this->getRequest()->getPost();

        if (count($postData) > 0) {
            $post['country_id'] = $postData['country_id'];
            $post['date_from'] = $postData['date_from'];
            $post['date_to'] = $postData['date_to'];
            Mage::register('data', $this->orderCounts($post['country_id'], $post['date_from'], $post['date_to']));
        } else {
            Mage::register('data', $this->orderCounts('', '', ''));
        }

        $this->loadLayout();
        $this->_title($this->__("All Order Count"));

        Mage::register('options', $this->shippingCountriesList($post['country_id']));

        $this->renderLayout();
    }

    /**
     * @param $selectedCountry
     * @return string
     */
    public function shippingCountriesList($selectedCountry)
    {
        $stores = Mage::app()->getStores();
        $country = array();
        $options = '';

        foreach ($stores as $store) {
            $code = $store->getCode();
            $codeArray = explode('en_', $code);
            if (count($codeArray) > 0) {
                $countryList = Mage::getModel('directory/country')->getResourceCollection()->loadByStore()->toOptionArray(true);
                foreach ($countryList as $list) {
                    if ($list['value'] == strtoupper($codeArray[1])) {
                        if (strtoupper($codeArray[1]) != '') {
                            $country[strtoupper($codeArray[1])] = $list['label'];
                        }
                    }
                }
            }
        }

        array_unshift($country, "All Countries");

        foreach ($country as $key => $ca) {
            if ($selectedCountry == $key) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            $options .= "<option value='" . $key . "' " . $selected . ">" . $ca . "</option>";
        }

        return $options;
    }

    /**
     * @param $ordersItems
     * @param $condition
     * @param $country_id
     * @param $dateStart
     * @param $dateEnd
     * @return int
     */
    public function VerifiData($ordersItems, $condition, $country_id, $dateStart, $dateEnd)
    {
        $orderids = array();

        foreach ($ordersItems as $item) {
            if ($condition == 'processing' || $condition == 'shipping') {#OrderInProcessing | AwaitingForShipment
                // here in this again we are checking further any item in the order may not have buyer or seller not confirmed
                $ordersFilter = Mage::getModel('marketplace/commission')->getCollection()
                    ->addFieldToSelect(array('increment_id', 'is_buyer_confirmation', 'is_seller_confirmation'))
                    ->addFieldToFilter(array('is_buyer_confirmation', 'is_seller_confirmation'),
                        array(
                            array('eq' => 'No'),
                            array('eq' => 'No')
                        ))
                    ->addFieldToFilter('increment_id', array('eq' => $item->getIncrementId()));
                if ($ordersFilter->getSize() == '0') { // 0 mean All items in the order dont have No status in(is_buyer_confirmation,is_seller_confirmation) so push in processing
                    array_push($orderids, $item->getIncrementId());
                }
            } else {
                array_push($orderids, $item->getIncrementId());
            }
        }

        $orderids = array_unique($orderids);

        if ($condition == 'shipping') {#AwaitingForShipment check invoice is created or not
            $ordersInvoiced = Mage::getModel("sales/order_invoice")->getCollection()
                ->addFieldToSelect('order_id')
                ->addFieldToFilter('order_id', array('in' => $orderids));
            $orderids = array();
            foreach ($ordersInvoiced as $item) {
                array_push($orderids, $item->getOrderId());
            }
            $orderids = array_unique($orderids);
        }

        if ((isset($dateStart) && $dateStart != '') || (isset($dateEnd) && $dateEnd != '') || (isset($country_id) && $country_id != '' && $country_id != '0')) {

            if (count($orderids) > 0) {
                $orderids = $this->applyFilters($orderids, $country_id, $dateStart, $dateEnd);
            } else {
                $orderids = array();
            }
        }

        return (count($orderids) > 0) ? count($orderids) : 0;
    }

    /**
     * @param $orders
     * @param $country_id
     * @param $dateStart
     * @param $dateEnd
     * @return int
     */
    public function orderCountResults($orders, $country_id, $dateStart, $dateEnd)
    {
        $orderids = array();

        foreach ($orders as $item) {
            array_push($orderids, $item->getIncrementId());
        }

        $orderIdData = array_unique($orderids);

        if ((isset($dateStart) && $dateStart != '') || (isset($dateEnd) && $dateEnd != '') || (isset($country_id) && $country_id != '' && $country_id != '0')) {
            if (count($orderIdData) > 0) {
                $orderIdData = $this->applyFilters($orderIdData, $country_id, $dateStart, $dateEnd);
            } else {
                $orderIdData = array();
            }
        }

        return (count($orderIdData) > 0) ? count($orderIdData) : 0;
    }

    /**
     * @param $orderids
     * @param $country_id
     * @param $dateStart
     * @param $dateEnd
     * @return array
     */
    public function applyFilters($orderids, $country_id, $dateStart, $dateEnd)
    {
        if (count($orderids) > 0) {
            $orderIdsDateFilter = array();
            $orders = Mage::getModel("sales/order")->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('increment_id', array('in' => $orderids));

            if ((isset($dateStart) && $dateStart != '') && (isset($dateEnd) && $dateEnd != '')) {

                $dateStart = Mage::getModel('core/date')->date('Y-m-d', $dateStart);
                $dateEnd = Mage::getModel('core/date')->date('Y-m-d 23:59:59', $dateEnd);

                $orders->addFieldToFilter('main_table.created_at', array('from' => $dateStart, 'to' => $dateEnd, 'date' => true));
            } elseif ((isset($dateStart) && $dateStart != '')) {
                $dateStart = Mage::getModel('core/date')->date('Y-m-d', $dateStart);
                $orders->addFieldToFilter('main_table.created_at', array('from' => $dateStart, 'date' => true));
            }

            foreach ($orders as $key => $value) {
                array_push($orderIdsDateFilter, $value->getEntityId());
            }

            $orderIdsDateFilter = array_unique($orderIdsDateFilter);
            if ((isset($country_id) && $country_id != '' && $country_id != '0') && (count($orderIdsDateFilter) > 0)) {

                $orderIdsDateFilter = implode(',', $orderIdsDateFilter);

                $condition = new Zend_Db_Expr("main_table.entity_id = sales_address.parent_id AND
                    sales_address.address_type =  'shipping' AND 
                    sales_address.country_id ='$country_id'");
                $orders = Mage::getModel("sales/order")->getCollection();
                $orders->getSelect()->join(array('sales_address' => 'sales_flat_order_address'),
                    $condition,
                    array('country_id' => 'sales_address.country_id'))
                    ->where("main_table.entity_id IN (" . $orderIdsDateFilter . ")");
                $orderIdsDateFilter = array();
                foreach ($orders as $key => $value) {
                    array_push($orderIdsDateFilter, $value->getEntityId());
                }
            }
            return $orderIdsDateFilter = array_unique($orderIdsDateFilter);
        } else {
            return $orderIdsDateFilter = array();
        }
    }

    /**
     * @param $country_id
     * @param $dateStart
     * @param $dateEnd
     * @return array
     */
    public function orderCounts($country_id, $dateStart, $dateEnd)
    {
        // on first load we will get last 30 days orders
        if (empty($dateStart) && empty($dateEnd)) {
            $dateStart = date('Y-m-d', strtotime('today - 30 days'));
            $dateEnd = date("Y-m-d");
        } elseif (!empty($dateStart) && empty($dateEnd)) {
            $dateEnd = date("Y-m-d");
        } elseif (empty($dateStart) && !empty($dateEnd)) {
            $dateStart = date('Y-m-d', strtotime($dateEnd . ' -30 days'));
        }

        if (strtotime($dateStart) < strtotime($dateEnd . ' -90 days')) {
            $dateStart = date('Y-m-d', strtotime($dateEnd . ' -90 days'));
            $noticeMessage = "Note:- You Can not Search more then then 90 days difference following results From: " . $dateStart . " To: " . $dateEnd;
            Mage::getSingleton('core/session')->addNotice($noticeMessage);
        }

        $postData = $this->getRequest()->getPost();
        $post['country_id'] = $postData['country_id'];
        $post['date_from'] = $dateStart;
        $post['date_to'] = $dateEnd;
        // set post data here for updated values in form correctly
        Mage::register('postData', $post);
        $data = array();
        #=================================================================
        #===========================Grid A================================
        #=================================================================

        #***********************AwaitingForCustomer#***********************
        $awaitingForCustomer = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect(array('increment_id', 'order_id'))
            ->addFieldToFilter('is_buyer_confirmation', array('eq' => 'No'))
            ->addFieldToFilter('increment_id', array('nin' => array('0', '')))
            ->addFieldToFilter('item_order_status', array('neq' => 'canceled'))
            ->addFieldToFilter('order_status', array('neq' => 'canceled'));
        $awaitingForCustomer->getSelect()->group('increment_id');
        if (count($awaitingForCustomer) > 0) {
            $data['AwaitingForCustomer'] = $this->VerifiData($awaitingForCustomer, '', $country_id, $dateStart, $dateEnd);
        } else {
            $data['AwaitingForCustomer'] = 0;
        }

        #***********************AwaitingForSeller#***********************
        $awaitingForSeller = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect(array('increment_id', 'order_id'))
            ->addFieldToFilter('is_buyer_confirmation', array('eq' => 'Yes'))
            ->addFieldToFilter('is_seller_confirmation', array('eq' => 'No'))
            ->addFieldToFilter('increment_id', array('nin' => array('0', '')))
            ->addFieldToFilter('item_order_status', array('neq' => 'canceled'))
            ->addFieldToFilter('order_status', array('neq' => 'canceled'));
        $awaitingForSeller->getSelect()->group('increment_id');
        if (count($awaitingForSeller) > 0) {
            $data['AwaitingForSeller'] = $this->VerifiData($awaitingForSeller, '', $country_id, $dateStart, $dateEnd);
        } else {
            $data['AwaitingForSeller'] = 0;
        }

        #***********************OrderInProcessing#***********************
        $orderInProcessing = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect(array('increment_id', 'is_buyer_confirmation', 'is_seller_confirmation'))
            ->addFieldToFilter('is_seller_confirmation', array('nin' => array('No', 'Rejected')))
            ->addFieldToFilter('is_buyer_confirmation', array('nin' => array('No', 'Rejected')))
            ->addFieldToFilter('order_status', array('in' => array('pending', 'processing')))
            ->addFieldToFilter('item_order_status', array('neq' => 'canceled'))
            ->addFieldToFilter('increment_id', array('nin' => array('0', '')));
        $orderInProcessing->getSelect()->group('increment_id');
        if (count($orderInProcessing) > 0) {
            $data['OrderInProcessing'] = $this->VerifiData($orderInProcessing, 'processing', $country_id, $dateStart, $dateEnd);
        } else {
            $data['OrderInProcessing'] = 0;
        }

        #***********************AwaitingForShipment#***********************
        $awaitingForShipment = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('is_seller_confirmation', array('nin' => array('No', 'Rejected')))
            ->addFieldToFilter('is_buyer_confirmation', array('nin' => array('No', 'Rejected')))
            ->addFieldToFilter('order_status', array('in' => array('pending', 'processing')))
            ->addFieldToFilter('increment_id', array('nin' => array('0', '')))
            ->addFieldToFilter('item_order_status', array('neq' => 'canceled'));
        $awaitingForShipment->getSelect()->group('increment_id');
        if (count($awaitingForShipment) > 0) {
            $data['AwaitingForShipment'] = $this->VerifiData($awaitingForShipment, 'shipping', $country_id, $dateStart, $dateEnd);
        } else {
            $data['AwaitingForShipment'] = 0;
        }

        #=================================================================
        #===========================Grid B================================
        #=================================================================

        #***********************Total Order Number#***********************
        $totalOrder = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect(array('increment_id', 'order_id'))
            ->addFieldToFilter('increment_id', array('nin' => array('0', '')));
        $totalOrder->getSelect()->group('increment_id');
        $data['TotalOrder'] = $this->orderCountResults($totalOrder, $country_id, $dateStart, $dateEnd);

        #***********************Total Number Canceled order#***********************
        $orderids = array();
        foreach ($totalOrder as $item) {
            array_push($orderids, $item->getIncrementId());
        }
        $orderids = array_unique($orderids);

        $canceledOrder = Mage::getModel('sales/order')->getCollection()
            ->addFieldToSelect(array('increment_id'))
            ->addFieldToFilter('status', array('eq' => 'canceled'))
            ->addFieldToFilter('increment_id', array('in' => $orderids));

        $orderids = array();
        foreach ($canceledOrder as $item) {
            array_push($orderids, $item->getIncrementId());
        }
        $orderIdData = array_unique($orderids);
        #--------------------------apply Filter on data--------------------------
        if ((isset($dateStart) && $dateStart != '') || (isset($dateEnd) && $dateEnd != '') || (isset($country_id) && $country_id != '' && $country_id != '0')) {
            if (count($orderIdData) > 0) {
                $orderIdData = $this->applyFilters($orderIdData, $country_id, $dateStart, $dateEnd);
            } else {
                $orderIdData = array();
            }
        }
        $data['CanceledOrder'] = (count($orderIdData) > 0) ? count($orderIdData) : 0;  //$canceledOrder->getSize();

        #***********************Total Customer Order Rejected 10 (complete order rejected by Customer#***********************
        $totalCustomerRejected = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect(array('increment_id', 'order_id'))
            ->addFieldToFilter('is_buyer_confirmation', array('eq' => 'Rejected'))
            ->addFieldToFilter('increment_id', array('nin' => array('0', '')));
        $totalCustomerRejected->getSelect()->group('increment_id');

        $orderids = array();
        foreach ($totalCustomerRejected as $item) {
            array_push($orderids, $item->getIncrementId());
        }
        $orderids = array_unique($orderids);
        $orderIdData = array();
        foreach ($orderids as $orderid) {
            $totalCustomerRejectedVerifiy = Mage::getModel('marketplace/commission')->getCollection()
                ->addFieldToSelect(array('increment_id', 'order_id'))
                ->addFieldToFilter('is_buyer_confirmation', array('neq' => array('Rejected')))//Condition: All of the items in the order dont have Rejected status
                ->addFieldToFilter('increment_id', array('eq' => $orderid));
            if ($totalCustomerRejectedVerifiy->getSize() == 0) {// if size greater then 0 mean at least one item dont have Rejected status from customer end
                array_push($orderIdData, $orderid);
            }
        }
        $orderIdData = array_unique($orderIdData);
        #--------------------------apply Filter on data--------------------------
        if ((isset($dateStart) && $dateStart != '') || (isset($dateEnd) && $dateEnd != '') || (isset($country_id) && $country_id != '' && $country_id != '0')) {
            if (count($orderIdData) > 0) {
                $orderIdData = $this->applyFilters($orderIdData, $country_id, $dateStart, $dateEnd);
            } else {
                $orderIdData = array();
            }
        }
        $data['TotalCustomerRejected'] = (count($orderIdData) > 0) ? count($orderIdData) : 0;

        #--------------------------Total Seller Item Rejected 9 --------------------------
        $totalSellerItemsRejected = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect(array('increment_id', 'order_id'))
            ->addFieldToFilter('is_seller_confirmation', array('eq' => array('Rejected')))
            ->addFieldToFilter('increment_id', array('nin' => array('0', '')));
        $totalSellerItemsRejected->getSelect()->group('increment_id');
        //echo $totalSellerItemsRejected->getSelect();exit;
        $orderids = array();
        foreach ($totalSellerItemsRejected as $item) {
            array_push($orderids, $item->getIncrementId());
        }
        $orderIdData = array_unique($orderids);
        #--------------------------apply Filter on data--------------------------
        if ((isset($dateStart) && $dateStart != '') || (isset($dateEnd) && $dateEnd != '') || (isset($country_id) && $country_id != '' && $country_id != '0')) {
            if (count($orderIdData) > 0) {
                $orderIdData = $this->applyFilters($orderIdData, $country_id, $dateStart, $dateEnd);
            } else {
                $orderIdData = array();
            }
        }
        $data['TotalSellerItemsRejected'] = (count($orderIdData) > 0) ? count($orderIdData) : 0;
        return $data;
    }

    /**
     * Check current user permission on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('marketplace/overview');
    }
}