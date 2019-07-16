<?php

/**
 * Progos_Changeprice.
 *
 * @category Elabelz
 *
 * @Author Saroop Chand (saroop.chand@progos.org)
 * @Date 04-01-2018
 *
 */

class Progos_Changeprice_Model_Cron
{
    public function __construct(){
        Mage::init();
    }

    public function createLog($log , $type){
        if( $type == "dry" )
            $file = 'change_product_price.csv';
        else if($type == "failure")
            $file = 'change_product_price_failure.csv';
        else
            $file = 'change_product_price_success.csv';


        if( !file_exists( Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import') ){
            mkdir(Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import' ,0777, true);
        }
        $mainDirectory = Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import'.DS.$file;

        if( !file_exists( $mainDirectory ) ){
            $log = "Date , Product Id , Sku , Price , Update Price , Updated Price , Special Price , Update Special Price , Updated Special Price \n".$log;
            chmod($mainDirectory, 0777);
        }

        file_put_contents($mainDirectory, $log , FILE_APPEND );
        return;
    }

    public function getUpdateProductCollection(){
        /*Increase the memory limit to avoid exaust memory issue*/
        if( !$this->getEnable() )
            return "Please Enable Extension.";

        ini_set('memory_limit', '-1');
        $isDryRun = $this->getStatusIsDryrun();
        $amount = $this->getAmountToUpdate();
        $collection =  $this->getCollection();
        $decreasePriceStatus = $this->decreasePrice();
        $date = date('d-m-Y h:i:s a');
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        if( $amount != "" || $amount != 0 ){
            foreach( $collection as $product ){
                $param = array('special_price'=>"","price" => "");
                try{
                    $changePrice = $changePriceUp = $changeSpecialPrice = $changeSpecialPriceUp = "";
                    if( $decreasePriceStatus ){
                        $changePrice = $product->getPrice() / $amount;
                        $changePriceUp = round($changePrice);
                    }else{
                        $changePrice = $product->getPrice() * $amount;
                        $changePriceUp = ceil($changePrice);
                    }

                    /*If Special Price not Set then no need to calcalute */
                    if( $product->getSpecialPrice() ){
                        if( $decreasePriceStatus ){
                            $changeSpecialPrice = $product->getSpecialPrice() / $amount;
                            $changeSpecialPriceUp = round($changeSpecialPrice);
                        }else{
                            $changeSpecialPrice = $product->getSpecialPrice() * $amount;
                            $changeSpecialPriceUp = ceil($changeSpecialPrice);
                        }
                        $param['special_price'] = $changeSpecialPriceUp;
                    }

                    $param['price'] = $changePriceUp;
                    if( $decreasePriceStatus ){
                        $log = "productId =>".$product->getId()." , sku => ".$product->getSku()." , Price => ".$product->getPrice();
                        $log .= " , update Price => ".$changePrice." , update Price ro => ".$changePriceUp;
                        $log .=" , Special Price => ".$product->getSpecialPrice()." , Update Sp => ".$changeSpecialPrice." , Update Sp ro => ".$changeSpecialPriceUp;

                        $row = $date.",".$product->getId().",".$product->getSku().",".$product->getPrice().",".$changePrice.",".$changePriceUp.",";
                        $row .= $product->getSpecialPrice().",".$changeSpecialPrice.",".$changeSpecialPriceUp." \n";
                    }else{
                        $log = "productId =>".$product->getId()." , sku => ".$product->getSku()." , Price => ".$product->getPrice();
                        $log .= " , update Price => ".$changePrice." , update Price up => ".$changePriceUp;
                        $log .=" , Special Price => ".$product->getSpecialPrice()." , Update Sp => ".$changeSpecialPrice." , Update Sp up => ".$changeSpecialPriceUp;
                        $row = $date.",".$product->getId().",".$product->getSku().",".$product->getPrice().",".$changePrice.",".$changePriceUp.",";
                        $row .= $product->getSpecialPrice().",".$changeSpecialPrice.",".$changeSpecialPriceUp." \n";
                    }

                    $this->updateProductPrice( $product , $param , $log , $isDryRun , $row );
                }catch( Exception $col ){
                    Mage::log( $log." -->productCollectionIssues" , null, 'change_product_price_failure.log' );
                    $this->createLog($row , 'failure');
                    continue;
                }
            }
        }else{
            return "Please Add Amount To Update.";
        }
        return "Script Successfully Executed.";
    }

    /*
     * Update Price of the Product
     * */
    public function updateProductPrice(  $prod , $param = array(), $log = "", $isDryRun = true , $row = " \n" ){
        try{
            if( $isDryRun == true ){
                Mage::log( $log , null, 'change_product_price.log' );
                $this->createLog($row , 'dry');
                return true;
            }
            $prod->setPrice($param['price']);
            if( $param['special_price'] )
                $prod->setSpecialPrice($param['special_price']);
            $prod->setIsPriceUpdate(1);
            $prod->save();
            Mage::log( $log , null, 'change_product_price_success.log' );
            $this->createLog($row , 'success');
        }catch ( Exception $prod){
            Mage::log( $log." -->ProductSaveIssue" , null, 'change_product_price_failure.log' );
            $this->createLog($row , 'failure');
        }
        return true;
    }

    /*
     * Return Collection of the Config Product
     * */
    public function getCollection(){

        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id','configurable')
            ->addAttributeToFilter('is_price_update', 0);
        $conditionAttribute = $this->getCondition();
        if( !empty($conditionAttribute) ){
            $collection->addAttributeToFilter('condition_price_update',$conditionAttribute);
        }
        return $collection;
    }

    /*
     * Get the amount for multiplication with original Price
     * */
    public function getCondition(){
        return Mage::getStoreConfig('change_price_general/change_price_settings/change_price_based_condition');
    }

    /*
     * Get the amount for multiplication with original Price
     * */
    public function getAmountToUpdate(){
        return Mage::getStoreConfig('change_price_general/change_price_settings/change_price_amount');
    }

    /*
     * Check script is running for dry run or implementation of changes.
     * */
    public function getStatusIsDryrun(){
        $status = Mage::getStoreConfig('change_price_general/change_price_settings/change_price_amount_dryrun');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    /*
     * Check script is running for dry run or implementation of changes.
     * */
    public function decreasePrice(){
        $status = Mage::getStoreConfig('change_price_general/change_price_settings/change_price_decrease_price');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    /*
     * Check script is running for dry run or implementation of changes.
     * */
    public function getEnable(){
        $status = Mage::getStoreConfig('change_price_general/change_price_settings/change_price_enable');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}