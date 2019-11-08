<?php
/*
 * Saroop Chand
 * */
require_once 'abstract.php';
class Progos_Shell_Syncproduct extends Mage_Shell_Abstract
{
    /**
     * Run script
     */
    public function run()
    {
        error_reporting(0);
        ini_set('display_errors', '0');
        ini_set('memory_limit', '-1');

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $date = date('m-d-Y h:i:s a', time());
        $obj = new Progos_Syncproduct_Model_Syncproduct();
        $obj->addLog( "Request Sent. " , $date );
        $response  = $obj->getResponse();
        $obj->addLog( "Response Recieved. " , $date );
        if( !empty( $response ) ){
            $result = $obj->createProduct( $response , $date );
            if( !empty( $result ) ){
                $response  = $obj->updateStatusCall( $result );
                if( $response->data && $response->successStatus) {
                    $obj->addLog( " After Completion Status Updated. " , $date );
                }else{
                    $obj->addLog( " Some Error Occured During Status Updated. " , $date );
                }
            }else{
                $obj->addLog( " Empty Array For update complete status. " , $date );
            }
        }else{
            $obj->addLog( " Response Have Some Error. " , $date );
        }

        $endTime = (microtime(true) - $startTime) / 60;
        $endMemory = (memory_get_usage() - $startMemory) / 1000000;
        $obj->addLog( " Request Completed. " , $date );
        $obj->addLog( " -------------------------------------------------------------------. " , $date );
        echo "<br> Time: {$endTime}  minutes\n";
        echo "Memory: {$endMemory} megabytes\n";
    }
}

$shell = new Progos_Shell_Syncproduct();
$shell->run();