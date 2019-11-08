<?php

/**
 * This Shell Script will copy Already generated feed file for en_ae store and duplicate it for remaining english stores, similarly for ar_ae for arabic stores
 *
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           19-04-2018 14:14
 *
 */

require_once 'abstract.php';

class Progos_Shell_FeedExportOptization extends Mage_Shell_Abstract
{

    public $basicFilesNames     = ['en_ae' => 'facebook-all-products_uae_english_v3.csv', 'ar_ae' => 'facebook-all-products_uae_arabic_v3.csv'];
    public $newEngFilesNames    = ['en_om' => 'facebook-all-products_oman_english_v3.csv', 'en_kw' => 'facebook-all-products_kuwait_english_v3.csv', 'en_sa' => 'facebook-all-products_ksa_english_v3.csv', 'en_iq' => 'facebook-all-products_iraq_english_v3.csv', 'en_bh' => 'facebook-all-products_bahrain_english_v3.csv', 'en_qa' => 'facebook-all-products_qatar_english_v3.csv', 'en_uk' => 'facebook-all-products_uk_english_v3.csv', 'en_us' => 'facebook-all-products_us_english_v3.csv', 'en_int' => 'facebook-all-products_int_english_v3.csv'];
    public $newArabicFilesNames = ['ar_om' => 'facebook-all-products_oman_arabic_v3.csv',  'ar_kw' => 'facebook-all-products_kuwait_arabic_v3.csv',  'ar_sa' => 'facebook-all-products_ksa_arabic_v3.csv' , 'ar_iq' => 'facebook-all-products_iraq_arabic_v3.csv' , 'ar_bh' => 'facebook-all-products_bahrain_arabic_v3.csv' , 'ar_qa' => 'facebook-all-products_qatar_arabic_v3.csv'];

    public function run()
    {
        Mage::register('custom_entry_point', true, true);

        ini_set('memory_limit', '1024M'); // issue face on less memory so increased to 1 GB
        error_reporting(E_ALL);
        set_time_limit(3600);
        try {
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
                            $modifiedData[] = $line;
                            continue;
                        }
                        if (empty($line)) continue;
                        $line[3] = str_replace($store, $flag, $line[3]);
                        // currency converter
                        switch($flag){
                            case 'en_kw':
                            case 'ar_kw':
                            $line[5]  = $this->getFormattedPrice($line[5],'AED','KWD');
                            $line[12] = $this->getFormattedPrice($line[12],'AED','KWD');
                                break;
                            case 'en_sa':
                            case 'ar_sa':
                            $line[5]  = $this->getFormattedPrice($line[5],'AED','SAR');
                            $line[12] = $this->getFormattedPrice($line[12],'AED','SAR');
                                break;
                            case 'en_qa':
                            case 'ar_qa':
                                $line[5]  = $this->getFormattedPrice($line[5],'AED','EGP');
                                $line[12] = $this->getFormattedPrice($line[12],'AED','EGP');
                                break;
                            case 'en_uk':
                                $line[5]  = $this->getFormattedPrice($line[5],'AED','GBP');
                                $line[12] = $this->getFormattedPrice($line[12],'AED','GBP');
                                break;
                            case 'en_iq':
                            case 'ar_iq':
                            case 'en_bh':
                            case 'ar_bh':
                            case 'en_om':
                            case 'ar_om':
                            case 'en_us':
                            case 'en_int':
                                $line[5]  = $this->getFormattedPrice($line[5],'AED','USD');
                                $line[12] = $this->getFormattedPrice($line[12],'AED','USD');
                                break;

                        }
                        $modifiedData[] = $line;
                    }
                    // create file with $modifiedData for particular store
                    if($newEngFileName == 'facebook-all-products_qatar_english_v3.csv'){
                        Mage::log($modifiedData, Zend_Log::ERR, 'customfeederror.log', true);
                    }
                    $this->createStoreCsvFile($modifiedData, $newEngFileName);
                    unset($modifiedData);
                }
                unset($readFiles);
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
