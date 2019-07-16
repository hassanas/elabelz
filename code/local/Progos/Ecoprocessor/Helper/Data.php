<?php

class Progos_Ecoprocessor_Helper_Data extends Mage_Core_Helper_Abstract {
    /*
     * Function to get shipping charges
     */
    public function getShipmentCharges($shippingCountry, $orderSubtotal)
    {
        switch ($shippingCountry) {
            case "AE":
                $price = 0;
                break;
            default:
            {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='" . $shippingCountry . "'";
                $rows = $connection->fetchAll($sql);
                if(!$rows){
                    $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='0'";
                    $rows = $connection->fetchAll($sql);
                }
                $i = 0;
                if(sizeof($rows) == 1){
                    $price = $rows[0]['price'];
                }else {
                    foreach ($rows as $row) {
                        if ($i == 0) {
                            $minArr[] = $row['condition_value'];
                            $minArr[] = $row['price'];
                        } else {
                            $maxArr[] = $row['condition_value'];
                            $maxArr[] = $row['price'];
                        }
                        $i++;
                    }
                    if ($orderSubtotal > $minArr[0] && $orderSubtotal < $maxArr[0]) {
                        $price = $minArr[1];
                    } else {
                        $price = $maxArr[1];
                    }
                }
            }
        }
        return $price;
    }

    /*
     * Get Msp_cashondelivery charges
     */
    public function getCodCharges($country){
        $storeId = $store = Mage::app()->getStore()->getId();
        $zoneType = $country == Mage::getStoreConfig('shipping/origin/country_id', $storeId) ? 'local' : 'foreign';
        if ($zoneType == 'local')
            $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_local', $storeId);
        else
            $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign', $storeId);

        if ($country == 'SA') {
            $additionalFee = Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_sa');
            $amount = $amount + $additionalFee;
        }

        if (strtolower($country) == "iq") {// this condition should be dynamic for msp charges for specfic stores
            $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_iq');
        }
        return $amount;
    }

    /*
     * Function to add store credit history
     */
    public function addStoreCreditComments($storecreditId,$orderId,$storecreditTotal,$storecreditSpent){
        //add in history
        $info = array(
            'message_type' => AW_Storecredit_Model_Source_Storecredit_History_Action::BY_ORDER_MESSAGE_VALUE,
            'message_data' => array('order_increment_id' => $orderId)
        );
        Mage::getModel('aw_storecredit/history')
            ->setStorecreditId($storecreditId)
            ->setAction(AW_Storecredit_Model_Source_Storecredit_History_Action::USED_VALUE)
            ->setBalanceDelta($storecreditSpent)
            ->setBalanceAmount($storecreditTotal)
            ->setAdditionalInfo($info)
            ->save()
        ;
    }

}