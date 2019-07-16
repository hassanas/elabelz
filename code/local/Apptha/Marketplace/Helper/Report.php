<?php

class Apptha_Marketplace_Helper_Report extends Mage_Core_Helper_Abstract 
{

    /**
     * 
     * @return string
     */
    
    public function getQuery()
    {
        $query =  "select mc.id, seller_detail(mc.seller_id) as seller_detail,
            order_detail(mc.order_id),sms_status(mc.sms_verify_status),mc.increment_id as order_number,
            mc.product_id,
            concat(get_name(mc.product_id),get_size(mc.product_id), get_color(mc.product_id), get_sku(mc.product_id)) as product_details,
            mc.product_qty,
            IF(mc.product_amt > 0 , concat(get_base_currency_code(), ' ',round(mc.product_amt,0)), '') as product_price,
            special_price(mc.product_id, get_base_currency_code()) as special_price,
            concat(if(o.order_currency_code = 'USD', '$', o.order_currency_code), ' ',round(mc.seller_amount,0)) as seller_earn_amount,concat(if(o.order_currency_code = 'USD', '$', o.order_currency_code), ' ',round(mc.commission_fee,0)) as commission_fee,
            IF(mc.order_id IS NOT NULL, IF(o.total_offline_refunded > 0, concat(get_base_currency_code(), ' ',round(o.base_grand_total,0), ',(', get_base_currency_code(), ' ', round(o.total_offline_refunded,0), ' Refunded)'), concat(get_base_currency_code(), ' ', round(o.base_grand_total,0)) ), 'N/A') as order_total,
            order_status(mc.order_status) ,
            IF(o.failed_delivery = 1, 'Failed', IF(o.failed_delivery = 2, 'Success', IF(o.failed_delivery = 3, 'Warning', '') ) ) as failed_delivery,
            item_status(mc.item_order_status),DATE_FORMAT(mc.created_at, '%b %d, %Y %r') as created_date
            from marketplace_commission as mc
            inner join sales_flat_order as o on mc.order_id = o.entity_id";
        $query .= $this->applyFilters();
        return $query;
        
    }
    /**
     * 
     * @return boolean
     */
    public function isApplyFilter()
    {
        $filter = Mage::app()->getRequest()->getParam('filter');
        $requestData = Mage::helper('adminhtml')->prepareFilterString($filter);
        if(empty($requestData) && (Mage::app()->getRequest()->getActionName()=='exportCsv' || Mage::app()->getRequest()->getActionName()=='exportExcel') 
            || (isset($requestData['created_at']['from']) || isset($requestData['created_at']['to']) || isset($requestData['selleremail']))
          ) {  
            return true;
        }
        return false;
    }
    
    /**
     * 
     * @return string
     */
    public function applyFilters()
    {
        $filter = Mage::app()->getRequest()->getParam('filter');
        $requestData = Mage::helper('adminhtml')->prepareFilterString($filter);
        if(empty($requestData) && (Mage::app()->getRequest()->getActionName()=='exportCsv' || Mage::app()->getRequest()->getActionName()=='exportExcel')) {
            $noOfDays = $this->getNoOfDays();
            $today = Mage::getModel('core/date')->date('Y-m-d').' 23:59:59';
            $past = date('Y-m-d', strtotime('-'.$noOfDays.' days')).' 00:00:00';
            return " where mc.created_at  != 'NULL' and mc.created_at >='{$past}'  and mc.created_at <='{$today}' order by mc.id desc";
        }
        
        $query = '';
        if(isset($requestData['created_at']['from']) || isset($requestData['created_at']['to']) || isset($requestData['selleremail']) ) {
            if(isset($requestData['selleremail'])) {
                $query .=" inner join customer_entity as c on mc.seller_id = c.entity_id  "
                       . " where c.email like '%{$requestData['selleremail']}%' ";
            }
            if(!is_null($requestData['created_at']['from'])) {
                $query .= empty($query) ? ' where' : ' AND ';
                $dateFiletrFrom = Varien_Date::formatDate($requestData['created_at']['from'], true);
                $query .=" mc.created_at >='{$dateFiletrFrom}' ";
            }
            if(isset($requestData['created_at']['to'])) {
                $query .= empty($query) ? ' where' : ' AND ';
                $dateFiletrTo = Varien_Date::formatDate($requestData['created_at']['to'], true);
                $query .=" mc.created_at <='{$dateFiletrTo}' ";
            }

        }
        $query .= empty($query) ? " where mc.created_at  != 'NULL' order by mc.id desc" : " AND mc.created_at  != 'NULL' order by mc.id desc";
        return $query;
    }
    /**
     * 
     * @return int
     */
    public function getNoOfDays()
    {
        return (int)Mage::getStoreConfig ('marketplace/marketplace/all_export') > 0 ? (int)Mage::getStoreConfig ('marketplace/marketplace/all_export') : 40;
    }
    /**
     * 
     * @return array
     */
    public function getHeaderColumns()
    {
        $header = array(
            'ID', 'Seller detail', 'Buyer detail', 'Verify by SMS', 'Order #', 'Product ID', 'Product details',
            'Quantity', 'Product Price', 'Special Price', 'Sellers Earned Amount', 'Commission Fee',
            'Order total', 'Order Status', 'Failed Delivery', 'Item Status', 'Order At'
        );
        return $header;
    }
    
    /**
     * 
     * @return array
     */
    public function generateExcelFile()
    {
        $resource = Mage::getSingleton('core/resource');
	$read = $resource->getConnection('core_read');
        $records = $read->fetchAll($this->getQuery());
        $sheetName = '';
        $parser = new Varien_Convert_Parser_Xml_Excel();
        $io     = new Varien_Io_File();

        $path = Mage::getBaseDir('var') . DS . 'export' . DS;
        $name = md5(microtime());
        $file = $path . DS . $name . '.xml';
        
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);
        $io->streamWrite($parser->getHeaderXml($sheetName));
        $io->streamWrite($parser->getRowXml($this->getHeaderColumns()));
        foreach ($records as $value) {
            $data = $parser->getRowXml($value);
            $io->streamWrite($data);
        }
        $io->streamWrite($parser->getFooterXml());
        $io->streamUnlock();
        $io->streamClose();
        
        $report =  array(
            'type'  => 'filename',
            'value' => $file,
            'rm'    => true // can delete file after use
        );
        
        return $report;
        
    }
    
    /**
     * 
     * @return array
     */
    public function generateCsvFile()
    {
        $resource = Mage::getSingleton('core/resource');
	$read = $resource->getConnection('core_read');
        $records = $read->fetchAll($this->getQuery());

        $io = new Varien_Io_File();

        $path = Mage::getBaseDir('var') . DS . 'export' . DS;
        $name = md5(microtime());
        $file = $path . DS . $name . '.csv';

        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);
        $io->streamWriteCsv($this->getHeaderColumns());
        foreach ($records as $value) {
            $io->streamWriteCsv(
                Mage::helper("core")->getEscapedCSVData($value)
            );
        }
        $io->streamUnlock();
        $io->streamClose();

        $report = array(
            'type'  => 'filename',
            'value' => $file,
            'rm'    => true 
        );
        return $report;
    }

} 