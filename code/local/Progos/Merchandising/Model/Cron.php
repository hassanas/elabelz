<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_Merchandising
 */

/**
 * Class containing the logic for the cron runs for saving changes in the merchandising of each category
 */

class Progos_Merchandising_Model_Cron
{
    public function __construct(){
        Mage::init();
    }

    public function savePendingProductPositions(){
        if (Mage::getStoreConfig('progos_merchandising/general/cronmerchandisingstatus')) {
            $maxRecordsToProcess = Mage::getStoreConfig('progos_merchandising/general/recordstoprocess');
            if(!empty($maxRecordsToProcess)){
                $productPositionCollection = Mage::getModel('progos_merchandising/positions')->getCollection()->addFieldToFilter('is_active', '1')->setOrder('position_id', 'asc')->setCurPage(1)
                    ->setPageSize($maxRecordsToProcess);

            }else{
                $productPositionCollection = Mage::getModel('progos_merchandising/positions')->getCollection()->addFieldToFilter('is_active', '1')->setOrder('position_id', 'asc');
            }
            foreach($productPositionCollection->getData() as $each){
                $this->savePositions($each);
            }
        }
    }
    /**
     * Save  product positions
     */
    public function savePositions($record, $manual= '0') {
        $categoryProducts = explode('&',$record['positions']);
        $categoryProductsPlusPositions = array();
        foreach($categoryProducts as $ct){
            $categoryProductsPlusPositions[] = array('id' => explode('=',$ct)[0] , 'pos' => explode('=',$ct)[1]);
        }
        $_model=Mage::getModel('magidev_sort/positions');
        $categoryId = $record['category_id'];
        foreach( $categoryProductsPlusPositions as $each ){
            $_model->updatePosition($categoryId,$each['id'],$each['pos'],0);
        }
        if($manual == '0'){
            Mage::log('Cron Category ID positions updated:',null,'merchandising.log',true);

        }else{
            Mage::log('Manual Category ID positions updated:',null,'merchandising.log',true);
        }
        Mage::log($categoryId,null,'merchandising.log',true);
        $obj = Mage::getModel('progos_merchandising/positions')->load($categoryId, 'category_id');
        $obj->setIsActive('0');
        try {
            $obj->save();
        }catch (Exception $e){
            Mage::log($e->getMessage(),null,'merchandisingissues.log',true) ;
        }


    }

}