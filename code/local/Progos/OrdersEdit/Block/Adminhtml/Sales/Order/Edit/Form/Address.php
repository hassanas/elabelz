<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
class Progos_OrdersEdit_Block_Adminhtml_Sales_Order_Edit_Form_Address extends MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Edit_Form_Address {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('progos/orderedit/edit/address.phtml');
    }

    public function getSyncAddressCollection(){
        
        $shipping = $this->getOrder()->getShippingAddress()->getData();
        $shipping_addr_id = $shipping['entity_id'];
		$billing_addr_id = $this->getOrder()->getBillingAddress()->getId();
        $telephone = $shipping['telephone'];
        $collection = Mage::getSingleton('sales/order_address')->getCollection()
                ->addFieldToFilter(array('email', 'telephone'), // filter either customer email address or telephone match
                        array(
                            array('eq' => $this->getOrder()->getCustomerEmail()),
                            array('eq' => $telephone)
                        )
                )->addFieldToFilter('entity_id', array('nin' => array($shipping_addr_id, $billing_addr_id))) // exclude current billing and shipping address
                ->addFieldToFilter('customer_address_id', array('null' => true)); //  apply filter for those addresses that haven't address id, means not saved in address book.
        $collection->getSelect()->group('street'); // fetch distinct street addresses.
        $collection = $collection->load();
        
        return $collection;
    }

    /**
     * Return Customer Address Collection as array
     *
     * @return array
     */
	 
    public function getAddressCollection() {
        $request = Mage::app()->getRequest()->getParams();

        if (isset($request['sync']) && $request['sync'] != '') { // check is this snycing process or not.
           $customerAddress = $this->getCustomer()->getAddresses();
            $orderAddress = $this->getSyncAddressCollection()->getItems();
            $addressMerged = array_merge($customerAddress, $orderAddress); // merge saved customer address with past order addresses.
            return $addressMerged;
        } else {
            return parent::getAddressCollection();
        }
    }

}
