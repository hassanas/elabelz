<?php

/**
 * Progos_Oos.
 *
 * @category Elabelz
 *
 * @Author Naveed Abbas <naveed.abbas@progos.org> & Saroop Chand <saroop.chand@progos.org>
 * @Date 23-01-2018
 *
 */

class Progos_Oos_Model_Cron
{
    public function __construct(){
        Mage::init();
    }

    public function run(){
        try{
            /*Check Module is Enable or not.*/
            if( !$this->getEnable() )
                return "Please Enable Extension.";
            $results = $this->getRecords();

            if( $results ){
                foreach( $results as $items ){
                    try{
                        if( $items['parent_item_id'] != null && $items['parent_item_id'] != "" ){
                            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($items['quote_id']);
                            $quote->removeItem($items['parent_item_id']);
                            $quote->collectTotals()->save();
                            Mage::log(print_r( $items,true),null,'quote_remove_success.log');
                        }
                    }catch( Exception $innerE ){
                        try{
                            $this->removeRegistory();
                            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($items['quote_id']);
                            $quote->removeItem($items['parent_item_id']);
                            $quote->collectTotals()->save();
                            Mage::log(print_r( $items,true),null,'quote_remove_success.log');
                        }catch( Exception $innerEE ){
                            Mage::log(print_r( $items,true)."---->".$innerE->getMessage()."---->".$innerEE->getMessage(),null,'quote_remove_failure.log');
                        }
                        continue;
                    }
                }
            }else{
                return "No Data found.";
            }
        }catch (Exception $e){
            return $e->getMessage();
        }
        return "Success.";
    }

    public function removeRegistory(){
        Mage::unregister('controller');
        return;
    }

    public function getRecords(){

        $query = "SELECT b.item_id,b.quote_id,b.product_id,b.parent_item_id FROM sales_flat_quote a 
                  JOIN sales_flat_quote_item b ON a.entity_id = b.quote_id
                  WHERE ((a.is_active = 1) OR (a.is_active = 0 AND a.entity_id NOT IN(SELECT quote_id FROM sales_flat_order))) 
                  AND b.product_type = 'simple' AND (b.product_id NOT IN (SELECT product_id FROM cataloginventory_stock_item WHERE qty > 0)) ";

        $quoteIds =  $this->getQuoteIds();
        if( empty( $this->getQuoteIds() ) ){
            $fromTo = $this->getDaysFilter();
            $from = $fromTo['from'];
            $to = $fromTo['to'];
            $query .= " AND a.created_at >= '".$from."' AND a.created_at <= '".$to."' ";
        }else{
            $query .= " AND b.quote_id IN($quoteIds)";
        }
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $result = $connection->fetchAll($query);
        return $result;
    }

    public function getDaysFilter(){
        $result = array();
        $days = $this->getNumberDays();
        if( empty( $days ) ){
            $result['from'] = $this->from();
            $result['to'] = $this->to();
        }else{
            $result['from'] = date('Y-m-d 00:00:00', strtotime('-'.$days.' days'));;
            $result['to'] = date('Y-m-d H:i:s');
        }
        return $result;
    }

    public function from(){
        $from = Mage::getStoreConfig('oos_general/oos_settings/from');
        return date("Y-m-d".' 00:00:00', strtotime($from));
    }

    public function to(){
        $to = Mage::getStoreConfig('oos_general/oos_settings/to');
        return date("Y-m-d".' 23:59:59', strtotime($to));
    }

    public function getNumberDays(){
        return  Mage::getStoreConfig('oos_general/oos_settings/oos_quote_days');
    }
    public function getQuoteIds(){
        return  Mage::getStoreConfig('oos_general/oos_settings/oos_quote_id');
    }
    /*
     * Check script is running for dry run or implementation of changes.
     * */
    public function getEnable(){
        $status = Mage::getStoreConfig('oos_general/oos_settings/oos_enable');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}