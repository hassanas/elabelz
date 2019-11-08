<?php
/**
 *
 * Its one time script to update confirmation dates from  0000-00-00 00:00:00 to created_at
 * Created by Hassan Ali Shahzad
 * Date: 15/01/2018
 * Time: 15:17
 *
 */


require_once 'abstract.php';

class UpdateMarketPlaceTableDates extends Mage_Shell_Abstract
{
    public function run()
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $query = "UPDATE marketplace_commission AS mc1
                            INNER JOIN
                        marketplace_commission AS mc2 ON mc1.id = mc2.id
                            AND mc2.is_buyer_confirmation = 'Yes'
                            AND mc2.is_seller_confirmation = 'No'
                            AND mc2.is_buyer_confirmation_date = '0000-00-00 00:00:00' 
                    SET 
                        mc1.is_buyer_confirmation_date = mc2.created_at";
        $writeConnection->query($query);

        $query = "UPDATE marketplace_commission AS mc1
                            INNER JOIN
                        marketplace_commission AS mc2 ON mc1.id = mc2.id
                            AND mc2.is_seller_confirmation = 'Yes'
                            AND mc2.is_seller_confirmation_date = '0000-00-00 00:00:00' 
                    SET 
                        mc1.is_seller_confirmation_date = mc2.created_at";

        $writeConnection->query($query);

    }
}

$shell = new UpdateMarketPlaceTableDates();
$shell->run();
