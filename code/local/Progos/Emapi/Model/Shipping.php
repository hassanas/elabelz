<?php

class Progos_Emapi_Model_Shipping
{
    private $shippingCountry;
    private $orderSubtotal;
    private $price;

    /*
     * constructor
     */
    public function __construct()
    {
        $this->price = 0;
    }

    /*
     * function to set details for shipping charges
     */
    public function setShippingChargesParams($shippingCountry,$orderSubtotal){
        $this->shippingCountry = $shippingCountry;
        $this->orderSubtotal = $orderSubtotal;
    }


    /*
     * Function to calculate shipping charges based on country and total
     */
    public function getShipmentCharges()
    {
        switch ($this->shippingCountry) {
            case "AE":
                $this->price = 0;
                break;
            default: {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='" . $this->shippingCountry . "'";
                $rows = $connection->fetchAll($sql);
                if (!$rows) {
                    $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='0'";
                    $rows = $connection->fetchAll($sql);
                }
                $i = 0;
                if (sizeof($rows) == 1) {
                    $this->price = $rows[0]['price'];
                } else {
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
                    if ($this->orderSubtotal > $minArr[0] && $this->orderSubtotal < $maxArr[0]) {
                        $this->price = $minArr[1];
                    } else {
                        $this->price = $maxArr[1];
                    }
                }
            }
        }
    }

    /*
     * function to return shipping price
     */
    public function getPrice()
    {
        return $this->price;
    }
}
