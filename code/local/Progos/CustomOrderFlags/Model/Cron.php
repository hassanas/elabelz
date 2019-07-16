<?php

/**
 * Progos_CustomOrderFlags.
 *
 * @category Elabelz
 *
 * @Author Saroop Chand <saroop.chand@progos.org>
 * @Date 07-06-2018
 *
 */

class Progos_CustomOrderFlags_Model_Cron
{

    protected $curl = null;
    protected $licensenumber = null;
    protected $username = null;
    protected $password = null;
    protected $url = null;
    protected $orderstatusUpdate = null;
    protected $helper;
    public function __construct(){
        Mage::init();
        $this->curl = new Zend_Http_Client();
        $AccessLicenseNumber = Mage::getStoreConfig('upslabel/credentials/accesslicensenumber');
        $UserId = Mage::getStoreConfig('upslabel/credentials/userid');
        $Password = Mage::getStoreConfig('upslabel/credentials/password');
        $this->licensenumber   =   $AccessLicenseNumber;
        $this->username        =   $UserId;
        $this->password        =   $Password;
        $this->orderstatusUpdate =   $this->getOrderStatus();
        $this->url             =   $this->getUrl();
        $this->helper          = Mage::helper('orderstatuses');
    }

    public function run(){
        try{
            /*Check Module is Enable or not.*/
            if( !$this->getEnable() )
                return "Please Enable Extension.";

            $options = Mage::getModel('customorderflags/source_upsstatus')->toOptionArray(true);

            $records = $this->getRecords();
            foreach( $records as $record ){
                if( empty( $record->getTrackingnumber() ) )
                    continue;
                $orderId        =   $record->getOrderId();
                $trackingNo     =   $record->getTrackingnumber();
                $response       =   $this->upstrack( $trackingNo );

                echo "Order Number: ".$orderId."=== Tracking Number: ".$trackingNo." Message: ".$response['msg']."\n";

                if( $response['status'] == 'fail' && $response['msg'] !=  "No tracking information available"){
                    break;
                }else if( $response['status'] == 'fail' && $response['msg'] ==  "No tracking information available" ){
                    try {
                        $order = Mage::getModel('sales/order')->load($orderId);
                        $order->setUpsstatusFlag(3);
                        $order->setUpdatedAt($order->getUpdatedAt());
                        $order->setTempTime($order->getUpdatedAt());
                        $order->save();
                    }catch(Exception $ord){
                        echo $orderId."Order issue.\n";
                    }
                    try {
                        $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($orderId);
                        $mageworx->setUpsstatusFlag(3);
                        $mageworx->setUpdatedAt($mageworx->getUpdatedAt());
                        $mageworx->save();
                    }catch(Exception $mage){
                        echo $orderId."Mageworx issue.\n";
                    }
                    $record->setUpsstatusFlag(3);
                    $record->save();
                    continue;
                }else if( $response['status'] == 'success' && $response['msg'] ==  "Delivered" ){
                    $mageworxStatus = false;
                    $orderStatus = false;
                    try {
                        $order = Mage::getModel('sales/order')->load($orderId);
                        $order->setUpsstatus(1);
                        $order->setUpsstatusFlag(1);
                        $order->setUpdatedAt($order->getUpdatedAt());
                        $order->setTempTime($order->getUpdatedAt());
                        $order->save();
                        $orderStatus = true;
                    }catch(Exception $ord){
                        echo $orderId."Order issue.\n";
                    }
                    try {
                        $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($orderId);
                        $mageworx->setUpsstatus(1);
                        $mageworx->setUpsstatusFlag(1);
                        $mageworx->setUpdatedAt($mageworx->getUpdatedAt());
                        $mageworx->save();
                        $mageworxStatus = true;
                    }catch(Exception $mage){
                        echo $orderId."Mageworx issue.\n";
                    }

                    $record->setUpsstatus( 1 );
                    $record->setUpsstatusFlag(1);
                    $record->save();
                    if( ( $record->getOrderId() && $this->orderstatusUpdate ) && ( $mageworxStatus &&  $orderStatus ) ){
                        $this->helper->setOrderStatusSuccesfullDeliver($order);
                    }
                }else{
                    continue;
                }

            }
        }catch (Exception $e){
            return $e->getMessage();
        }
        return "Success.";
    }

    public function getOptionKey( $options , $value ){
        if( in_array( strtolower($value) , $options ) ){
            $key = array_search( strtolower($value), $options);
        }else{
            $key = array_search(strtolower('Other'), $options);
        }
        return $key;
    }
    /**
     * @param $token
     * @param $params
     * @return true
     *
     * Send request to Resposys Api Call Subscription api for Responsys.
     */


    public function upstrack( $trackingNo ){
        $url        =   $this->url;
        $response = null;
        $headers    =   [ 'Content-Type' => 'application/json' ];
        $requestBody = [ "UPSSecurity" => [ "UsernameToken" => [ "Username" => $this->username,"Password" => $this->password ],
                         "ServiceAccessToken" => [ "AccessLicenseNumber" => $this->licensenumber ] ],
                            "TrackRequest" => [
                                "Request" => [ "RequestOption" => "1", "TransactionReference" => []],
                                "InquiryNumber" => "$trackingNo" ] ];
        $requestBody = json_encode($requestBody);
        try {
            $this->curl->setConfig(array('timeout' => 120));
            $this->curl->setUri($url);

            $this->curl->setRawData($requestBody);
            $this->curl->setHeaders($headers);
            $response = $this->curl->request(Zend_Http_Client::POST);
            $json =  $response->getBody() ;
            $data =  json_decode( $json );
            if( $data ){
                if( !$data->TrackResponse ) {
                    if( $data->Fault ) {
                        if( $msg = $data->Fault->detail->Errors->ErrorDetail->PrimaryErrorCode->Description ){
                            return array('status'=>'fail','msg'=>$msg);
                        }
                        return array('status'=>'fail','msg'=>"Some Error Occured In API Request.");
                    }
                }

                if( $data->TrackResponse->Response->ResponseStatus->Description == 'Success' ){
                    if( $package = $data->TrackResponse->Shipment->Package ){
                        if( $activities = $package->Activity ){
                            foreach( $activities as  $activity ){
                                if( $activity->Status ){
                                    return array('status'=>'success','msg'=>$activity->Status->Description);
                                }
                                break;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            return array('status'=>'success','msg'=>"Some Error Occured In API Request/Magento.");
        }
        return array('status'=>'noresponse','msg'=>"No response found.");
    }

    public function getRecords(){
        $limit = $this->getNoOfOrderProcess();
        $status   =   rtrim(Mage::getStoreConfig('upslabel/upsstatus/status'),',');
        $status   = explode(',',$status);
        $upsstatuscollection = Mage::getModel('upslabel/upslabel')->getCollection()
            ->addFieldToFilter('upsstatus_flag',  array('IN'=> $status ));

        return $upsstatuscollection;

    }

    public function getNoOfOrderProcess(){
        $number =  Mage::getStoreConfig('upslabel/upsstatus/no_of_orders_process');
        if( !empty( $number ) ){
            return $number;
        }else{
            return 20;
        }
    }

    public function getEnable(){
        $status = Mage::getStoreConfig('upslabel/upsstatus/enabled');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function getUrl(){
        $status = Mage::getStoreConfig('upslabel/testmode/testing');
        if( $status == '1' )
            $url = "https://wwwcie.ups.com/rest/Track";
        else
            $url = "https://onlinetools.ups.com/rest/Track";
        return $url;
    }

    public function getOrderStatus(){
        $orderStatus = Mage::getStoreConfig('upslabel/upsstatus/orderstatus');
        if( $orderStatus == '1' )
            return true;
        else
            return false;
    }

    /*
     * This function sync all ups shippments into seperate( upslabel ) table. And we use these records to update ups shipment status
     * */
    public function syncrecord(){
        try {
            $query = 'SELECT * FROM sales_flat_shipment_track 
                      Where order_id NOT IN(select order_id from upslabel ) 
                      AND carrier_code like "%ups%" AND track_number is not null';
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $result = $connection->fetchAll($query);
            Mage::log(print_r($result,true),null,'ups_sync.log');
            foreach ($result as $shipment) {
                $upsCollection = Mage::getModel('upslabel/upslabel')
                    ->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('trackingnumber', array('eq' => $shipment['track_number']));
                Mage::log(print_r($upsCollection->getData(),true),null,'ups_sync.log');
                if (empty($upsCollection->getData())) {
                    $ups = Mage::getModel('upslabel/upslabel')->load();
                    $ups->setTrackingnumber($shipment['track_number']);
                    $ups->setOrderId($shipment['order_id']);
                    $ups->setCreatedTime($shipment['created_at']);
                    $ups->setUpdateTime(date('Y-m-d H:i:s'));
                    $ups->save();
                }
            }
        }catch (Exception $e){
            return $e->getMessage();
        }
        return "All Ups Tracking are synced.";
    }
}