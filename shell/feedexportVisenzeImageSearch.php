<?php

/**
 * This Shell Script will copy Already generated feed file for en_ae store and create feed for visenze image search for App
 *
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           19-04-2018 14:14
 *
 */

require_once 'abstract.php';

class Progos_Shell_FeedExportOptization extends Mage_Shell_Abstract
{

    public $basicFilesNames             = ['en_ae' => 'facebook-all-products_uae_english_v3.csv'];
    public $basicFilesNamesArabic       = 'facebook-all-products_uae_arabic_v3.csv';
    public $newEngFilesNames            = ['en_ae' => 'elabelz_visenze_uae_english.csv'];

    public function run()
    {
        Mage::register('custom_entry_point', true, true);

        ini_set('memory_limit', '1024M'); // issue face on less memory so increased to 1 GB
        error_reporting(E_ALL);
        set_time_limit(3600);
        try {
            $arabicData = $this->getCsvData($this->basicFilesNamesArabic);
            foreach ($this->basicFilesNames as $store => $basicfileName) {
                $data = $this->getCsvData($basicfileName);
                $modifiedData = array();
                $readFiles = array();
                if ($store == 'en_ae') {
                    $readFiles = $this->newEngFilesNames;
                } else {
                    $readFiles = $this->newArabicFilesNames;
                }

                foreach ($readFiles as $flag => $newEngFileName) {
                    foreach ($data as $key => $line) {
                        if ($key == 0) {
                            $modifiedData[] = array('im_name','im_url','title','brand','product_url','desc','category','title_arabic','brand_arabic','sku','price_ae','sale_price_ae','price_kw','sale_price_kw','price_sa','sale_price_sa','price_iq','sale_price_iq','price_qa','sale_price_qa','price_uk','sale_price_uk','price_us','sale_price_us','price_int','sale_price_int','price_bh','sale_price_bh','price_om','sale_price_om');
                            continue;
                        }
                        if (empty($line[8])) continue;

                        $visienzeLine['im_name']            = $line[17]; // entity_id as image name/code for uniqueness
                        $visienzeLine['im_url']             = $line[4];
                        $visienzeLine['title']              = $line[20];
                        $visienzeLine['brand']              = $line[9];
                        $visienzeLine['product_url']        = strtok(str_replace($store, $flag, $line[3]),'?');
                        $visienzeLine['desc']               = $line[1];
                        $visienzeLine['category']           = str_replace('>',',',$line[19]);
                        // Now copy arabic names from arabic file
                        $visienzeLine['title_arabic']       = "";
                        $visienzeLine['brancd_arabic']      = "";
                        foreach ($arabicData as $arkey=>$arline){
                            if ($key == 0 or $arline[17] != $line[17]) {
                                continue;
                            }
                            $visienzeLine['title_arabic']       = $arline[20];
                            $visienzeLine['brancd_arabic']      = $arline[9];
                            break;
                        }

                        $visienzeLine['sku']      = $line[8];
                        // Prices for different stores
                        $visienzeLine['price_ae']           = $line[5];
                        $visienzeLine['sale_price_ae']      = (!empty($line[12]) and $line[12]>0)?$line[12]:0;
                        $visienzeLine['price_kw']           = (!empty($line[5])  and $line[5]>0)?$this->getFormattedPrice($line[5] ,'AED','KWD'):0;
                        $visienzeLine['sale_price_kw']      = (!empty($line[12]) and $line[12]>0)?$this->getFormattedPrice($line[12],'AED','KWD'):0;
                        $visienzeLine['price_sa']           = (!empty($line[5])  and $line[5]>0)?$this->getFormattedPrice($line[5] ,'AED','SAR'):0;
                        $visienzeLine['sale_price_sa']      = (!empty($line[12]) and $line[12]>0)?$this->getFormattedPrice($line[12],'AED','SAR'):0;
                        $visienzeLine['price_iq']           = (!empty($line[5])  and $line[5]>0)?$this->getFormattedPrice($line[5] ,'AED','USD'):0;
                        $visienzeLine['sale_price_iq']      = (!empty($line[12]) and $line[12]>0)?$this->getFormattedPrice($line[12],'AED','USD'):0;
                        $visienzeLine['price_qa']           = (!empty($line[5])  and $line[5]>0)?$this->getFormattedPrice($line[5] ,'AED','EGP'):0;
                        $visienzeLine['sale_price_qa']      = (!empty($line[12]) and $line[12]>0)?$this->getFormattedPrice($line[12],'AED','EGP'):0;
                        $visienzeLine['price_uk']           = (!empty($line[5])  and $line[5]>0)?$this->getFormattedPrice($line[5] ,'AED','GBP'):0;
                        $visienzeLine['sale_price_uk']      = (!empty($line[12]) and $line[12]>0)?$this->getFormattedPrice($line[12],'AED','GBP'):0;
                        // updated dollers from iraq
                        $visienzeLine['price_us']           = $visienzeLine['price_iq'];
                        $visienzeLine['sale_price_us']      = $visienzeLine['sale_price_iq'];
                        $visienzeLine['price_int']          = $visienzeLine['price_iq'];
                        $visienzeLine['sale_price_int']     = $visienzeLine['sale_price_iq'];
                        $visienzeLine['price_bh']           = $visienzeLine['price_iq'];
                        $visienzeLine['sale_price_bh']      = $visienzeLine['sale_price_iq'];
                        $visienzeLine['price_om']           = $visienzeLine['price_iq'];
                        $visienzeLine['sale_price_om']      = $visienzeLine['sale_price_iq'];

                        $modifiedData[] = $visienzeLine;
                    }
                    $this->createStoreCsvFile($modifiedData, $newEngFileName);
                    unset($modifiedData);
                }
                unset($readFiles);
                unset($arabicData);
            }
        } catch (Exception $e) {
            $msg = 'Feed Custom Csv General Error: ' . $e->getMessage();
            Mage::log($msg, Zend_Log::ERR, 'customfeederror.log', true);
            $this->sendEmail($msg);
            return false;
        }
        echo "Process Successfully Completed and Genereated feeds successfully . . . . .";
    }

    /**
     * This function will generate the file for remaing stores
     * @param $data
     * @param $fileName
     */
    public function createStoreCsvFile($data, $fileName)
    {
        $csvObject = new Varien_File_Csv();
        try {
            $csvObject->saveData(Mage::getBaseDir() . DS . 'media/feed' . DS . $fileName, $data);
            unset($data);
        } catch (Exception $e) {
            $msg = 'Feed Custom Csv Generate: ' . $fileName . ' - saveData() error - ' . $e->getMessage();
            Mage::log($msg, Zend_Log::ERR, 'customfeederror.log', true);
            $this->sendEmail($msg);
            return false;
        }
    }

    /**
     * This function will read the system generated files
     * @param $file
     * @return array|bool
     */
    public function getCsvData($file)
    {
        $csvObject = new Varien_File_Csv();
        try {
            return $csvObject->getData(Mage::getBaseDir() . DS . 'media/feed' . DS . $file);
        } catch (Exception $e) {
            $msg = 'Feed Custom Csv Read: ' . $file . ' - getCsvData() error - ' . $e->getMessage();
            Mage::log($msg, Zend_Log::ERR, 'customfeederror.log', true);
            $this->sendEmail($msg);
            return false;
        }

    }

    /**
     * This function will send email to feed concerns if found any exception
     *
     * @param $logMessage
     */
    public function sendEmail($logMessage="No Error"){

        // create html body for message
        $message = '<html>
                        <head>
                          <title>Feed Exported for Facebook</title>
                        </head>
                        <body>
                          <p>Dear Concerns!</p>
                          <p>Following Error occured in your Feed generation via your custom feed optimization script</p>
                          ';
        $message .= '<p>'.$logMessage.'</p>';
        $message .= '</body></html>';
        $headers = "MIME-Version: 1.0" . PHP_EOL;
        $headers .= "Content-type:text/html;charset=UTF-8" . PHP_EOL;
        try {
            $mailList = trim(Mage::getStoreConfig('sales_email/order/copy_to'));// getting email list from Sales emails orders
            mail($mailList,'Optimized Feed Export Error Alert!', $message, $headers);
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }
    public function getFormattedPrice($price,$from,$to){
        $convertedPrice = Mage::helper('directory')->currencyConvert($price, $from, $to);
        $formattedPrice = (string) Mage::getModel('directory/currency')->format(
            $convertedPrice,
            array('display'=>Zend_Currency::NO_SYMBOL),
            false
        );
        return $formattedPrice;
    }
}

$shell = new Progos_Shell_FeedExportOptization();
$shell->run();
