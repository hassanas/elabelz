<?php
//@author RT
//multiple statues can be mapped with with one magento state
//below status will be mapped with canceled state
$data = ['status' => 'canceled_automatic', 'label' => 'Canceled-Automatic'];
//check if order status already exists
$statusItem = Mage::getModel('sales/order_status')->getCollection()
	->addStatusFilter($data['status'])
	->getFirstItem()
	->getData();
//if status item not exists, create and assign to state
if (empty($statusItem)) {
	//create new status
	$status = Mage::getModel('sales/order_status')
		->setData($data)
		->save();
	//assign new status magento state "Cancel"
	$status->assignState(Mage_Sales_Model_Order::STATE_CANCELED);
}