<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_OrderCustomStatuses
{
    public function toOptionArray($isMultiSelect = false)
    {
        $orderStatusCollection = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
        $status = array(
           array('value' => "", 'label' => '--Please Select--')
        );

        $deprecatedStatuses = array('canceled' => 1, 'closed' => 1, 'complete' => 1,
            'fraud' => 1, 'processing' => 1, 'pending' => 1);
        foreach ($orderStatusCollection as $orderStatus) {
            if (!isset($deprecatedStatuses[$orderStatus['status']])) {
                $status[] = array(
                    'value' => $orderStatus['status'], 'label' => $orderStatus['label']
                );
            }
        }

        return $status;
    }
}