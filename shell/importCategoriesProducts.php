<?php

require_once 'abstract.php';

class Import_Categories_Products extends Mage_Shell_Abstract
{
    public function run()
    {
        error_reporting(E_ALL);
        ini_set('error_reporting', E_ALL);
        ini_set('max_execution_time', 360000);
        set_time_limit(360000);

        $file = $this->_getRootPath() .  $this->getArg('path');
        echo "Csv File path ".$file ."\n";
        $csv = new Varien_File_Csv();
        $data = $csv->getData($file);
        $resource = Mage::getSingleton('core/resource');
        $dbWrite = $resource->getConnection('core_write');
        $categoryProductTable = $resource->getTableName('catalog/category_product');
        $count =1;
        $faildCount = 1;
        foreach ($data as $key => $value) {
            $data = array(
                'category_id' => $value[0],
                'product_id' => $value[1],
                'position' => $value[2]
            );
            try {
                $dbWrite->insertOnDuplicate($categoryProductTable, $data, array('position'));
                echo 'Count: '.$count .  ', category_id: '. $value[0]. ', product_id: '. $value[1]. ' , position: ' . $value[2]."\n";
                Mage::log('Count : '.$count .  ', category_id: '. $value[0]. ', product_id: '. $value[1]. ' , position: ' . $value[2], null,'missing_catalog_category_product.log');
                $count = $count+1;
            } catch (Exception $e) {
                echo $faildCount .' : '.$e->getMessage()."\n";
                $faildCount+=1;
                Mage::log('Faild : '.$faildCount .  ', at category_id: '. $value[0]. ', product_id: '. $value[1]. ' , position: ' . $value[2], null,'missing_catalog_category_product.log');
            }

        }
        echo "\n Imported";
    }



    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f importCategoriesProducts.php -- [options]
                                          
  --path <path>   


USAGE;
    }
}

$shell = new Import_Categories_Products();
$shell->run();
