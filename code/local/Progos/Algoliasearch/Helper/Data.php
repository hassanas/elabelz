<?php

/**
 * Progos_Algoliasearch.
 * @category Elabelz
 * @Author Hassan Ali Shahzad   <hassan.ali@progos.org>
 * @Date 13-07-2018
 *
 */
class Progos_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract
{


    /**
     * This function will return id and correcponding name like that Array([0] => Array ([id] => 477    [value] => أسود))
     * @param $attributeId integer
     * @param $labels Array if ids
     * @return array
     */
    public function getOptionIds($attributeId, $labels){

        if (is_array($labels)) {
            $label = implode("','", $labels);
        } else {
            $label = $labels;
        }
        $query = "SELECT 
                    eao.option_id as id, eaov.value as name
                FROM
                    eav_attribute_option AS eao
                        LEFT JOIN
                    eav_attribute_option_value AS eaov ON eaov.option_id = eao.option_id
                WHERE
                    (eao.attribute_id = $attributeId)
                        AND (eaov.value IN ('".$label."'))
                GROUP BY eao.option_id";

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        try{
        $result = $connection->fetchAll($query);
        }catch (Exception $e){
            Mage::log("From Progos Helper:---->".$e->getMessage(),null,algolia.log);
        }

        return $result;
    }
}