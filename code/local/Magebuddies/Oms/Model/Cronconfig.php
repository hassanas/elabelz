<?php

/**
 * Magebuddies_Oms.
 *
 * @category Fiverr
 *
 * @Naveed Abbas <naveedabbas2007@gmail.com>
 * @Date 29-03-2019
 *
 */

class Magebuddies_Oms_Model_Cronconfig
{
    public function __construct(){
        Mage::init();
    }

    public function run(){
        try{
            ini_set('memory_limit', '-1');
            /*Check Module is Enable or not.*/
            if( !$this->getEnable() )
                return "Please Enable Extension.";
            $results = $this->getRecords();

            $dryrun = $this->getDryrun();
            $data = "Status,User Email,Customer Id,Store Id, Created At,Updated At,Product Id,Product Sku, Product Name,Product Type,Item Id,Parent Item Id,Quote ID \n";
            if( $dryrun ) {
                if (!file_exists(Mage::getBaseDir().DS.'media'.DS.'oos')) {
                    mkdir(Mage::getBaseDir().DS.'media'.DS.'oos', 0777, true);
                }
                $mainDirectory = Mage::getBaseDir().DS.'media'.DS.'oos'.DS.'configprodremove_dry.csv';
                if (!file_exists($mainDirectory)) {
                    chmod($mainDirectory, 0777);
                } else {
                    unlink($mainDirectory);
                    chmod($mainDirectory, 0777);
                }
            }else{
                if (!file_exists(Mage::getBaseDir().DS.'media'.DS.'oos')) {
                    mkdir(Mage::getBaseDir().DS.'media'.DS.'oos', 0777, true);
                }
                $mainDirectory = Mage::getBaseDir().DS.'media'.DS.'oos'.DS. 'configprodremove.csv';
                if (!file_exists($mainDirectory)) {
                    chmod($mainDirectory, 0777);
                } else {
                    unlink($mainDirectory);
                    chmod($mainDirectory, 0777);
                }
            }
            file_put_contents($mainDirectory, $data, FILE_APPEND);
            if( $results ){
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                foreach( $results as $items ){
                    $data = $items['status'].",";
                    $data .= $items['email'].",";
                    $data .= $items['customerid'].",";
                    $data .= $items['store'].",";
                    $data .= $items['created_at'].",";
                    $data .= $items['updated_at'].",";
                    $data .= $items['productid'].",";
                    $data .= $items['sku'].",";
                    $data .= $items['cname'].",";
                    $data .= $items['producttype'].",";
                    $data .= $items['item_id'].",";
                    $data .= $items['parent_item'].",";
                    $data .= $items['quote_id']." \n";
                    file_put_contents($mainDirectory, $data, FILE_APPEND);
                    if(  $dryrun == false ){
                        $sql2 = 'DELETE FROM sales_flat_quote_item WHERE item_id ='.$items['item_id'];
                        $connection->query($sql2);
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
        $fromTo =   $this->getDaysFilter();
        $from   =   $fromTo['from'];
        $to     =   $fromTo['to'];
        $query  =   "SELECT q.is_active as status,q.customer_email AS email , q.customer_id AS customerid , q.store_id AS store , 
                    a.created_at as created_at,a.updated_at as updated_at,a.product_id as productid,a.sku as sku,
                    a.name as cname ,a.product_type as producttype  ,a.item_id as item_id , a.parent_item_id as parent_item
                    ,a.quote_id as quote_id
                    FROM sales_flat_quote_item AS a 
                    INNER JOIN sales_flat_quote AS q ON q.entity_id = a.quote_id
                    WHERE 
                    a.item_id NOT IN ( 
                    SELECT i.parent_item_id FROM sales_flat_quote_item AS i WHERE i.parent_item_id IS NOT NULL 
                    AND i.created_at >= '".$from."' AND i.created_at <= '".$to."'
                    ) 
                    AND a.created_at >= '".$from."' AND a.created_at <= '".$to."'
                    AND a.parent_item_id IS NULL AND 
                    ((q.is_active = 1) OR (q.is_active = 0 AND q.entity_id NOT IN(SELECT quote_id FROM sales_flat_order))) ";
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
        $from = Mage::getStoreConfig('oos_general/config_settings/from');
        return date("Y-m-d".' 00:00:00', strtotime($from));
    }

    public function to(){
        $to = Mage::getStoreConfig('oos_general/config_settings/to');
        return date("Y-m-d".' 23:59:59', strtotime($to));
    }

    public function getNumberDays(){
        return  Mage::getStoreConfig('oos_general/config_settings/config_quote_days');
    }
    /*
     * Check script is running for dry run or implementation of changes.
     * */
    public function getEnable(){
        $status = Mage::getStoreConfig('oos_general/config_settings/config_enable');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function getDryrun(){
        $status = Mage::getStoreConfig('oos_general/config_settings/config_dryrun');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}