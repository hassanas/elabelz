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

class Magebuddies_Oms_Model_Updateoos
{
    public function __construct(){
        Mage::init();
    }

    public function run(){
        /*Check Module is Enable or not.*/
        if( !$this->getEnable() )
            return "Please Enable Extension.";
        $results = $this->getRecords();
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        if( $results ){
            $data = "Date Time,Product Id,Sku,Quantity \n";
            foreach( $results as $items ){
                $date = date('d-m-Y h:i:s a', time());
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct( $items['id'] );
                $stock->setData('is_in_stock', 0);
                $stock->save();
                $this->addProductReindex( $items['id'] );
                $data .=  $date.",".$items['id'].",".$items['sku'].",".$items['qunatity']." \n";
            }
            if( !file_exists(Mage::getBaseDir('media').DS.'oos') ){
                mkdir( Mage::getBaseDir('media').DS.'oos', 0777, true );
            }

            $mainDirectory = Mage::getBaseDir('media').DS.'oos'.DS.'oos_report.csv';
            file_put_contents($mainDirectory, $data);
            $email = Mage::getStoreConfig('oos_general/updateoos_settings/email');
            if( !empty( $email ) )
                $this->sendEmail( $email );

        }else{
            return "No Data found.";
        }
        return "Success.";
    }

    public function sendEmail( $email ){
        $adminEmailId = Mage::getStoreConfig('oos_general/updateoos_settings/admin_email_id');
        $toName = Mage::getStoreConfig("trans_email/ident_$adminEmailId/name");
        $toMailId = Mage::getStoreConfig("trans_email/ident_$adminEmailId/email");
        $mailTemplate = Mage::getModel('core/email_template');
        $mailTemplate->setSenderName($toName);
        $mailTemplate->setSenderEmail($toMailId);
        $subject = Mage::getStoreConfig('oos_general/updateoos_settings/email_subject');
        $body = Mage::getStoreConfig('oos_general/updateoos_settings/email_content');
        $mailTemplate->setTemplateSubject($subject);
        $mailTemplate->setTemplateText($body);

        $file = Mage::getBaseDir('media').DS.'oos'.DS.'oos_report.csv';
        $attachment = file_get_contents($file);

        $recieverName = Mage::getStoreConfig('oos_general/updateoos_settings/email_name');

        $mailTemplate->getMail()->createAttachment(
            $attachment,
            Zend_Mime::TYPE_OCTETSTREAM,
            Zend_Mime::DISPOSITION_ATTACHMENT,
            Zend_Mime::ENCODING_BASE64,
            'oos_report.csv'
        );

        if ( $mailTemplate->send( $email , $recieverName) )
            echo "Email Sent. ";
        else
            echo "Email Failed. ";
        return;
    }

    public function addProductReindex( $productId ){
        $catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "INSERT INTO {$catalogProductPartialindex} (product_id)
                    SELECT * FROM (SELECT '{$productId}') AS tmp
                    WHERE NOT EXISTS (
                            SELECT product_id FROM {$catalogProductPartialindex} WHERE product_id = '{$productId}'
                    ) LIMIT 1;";
        $write->query($sql);
        return;
    }

    public function getRecords(){
        $query = "SELECT s.qty AS qunatity , e.entity_id AS id , e.type_id AS product_type , 
                    s.is_in_stock AS stock , e.sku AS sku FROM cataloginventory_stock_item AS s
                    INNER JOIN catalog_product_entity as e 
                    ON e.entity_id = s.product_id 
                    WHERE s.is_in_stock = 1 AND s.qty <= 0 AND e.type_id = 'simple' ";

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $result = $connection->fetchAll($query);
        return $result;
    }

    /*
     * Check script is running for dry run or implementation of changes.
     * */
    public function getEnable(){
        $status = Mage::getStoreConfig('oos_general/updateoos_settings/enable');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}