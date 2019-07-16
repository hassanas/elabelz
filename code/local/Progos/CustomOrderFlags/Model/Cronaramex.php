<?php

/**
 * Progos_CustomOrderFlags.
 *
 * @category Elabelz
 *
 * @Author Saroop Chand <saroop.chand@progos.org>
 * @Date 19-06-2018
 *
 */

class Progos_CustomOrderFlags_Model_Cronaramex
{
    /*
     * FLAG = 0 => No status updated
     * FLAG = 1 => Shipment Delivered
     * FLAG = 3 => No Shipment Found
     * */
    protected $orderstatusUpdate = null;
    protected $helperstatus      = null;
    protected $url = null;
    protected $helper;
    protected $clientAramex = null;
    public function __construct(){
        Mage::init();
        $this->orderstatusUpdate =   $this->getOrderStatus();
        $this->helper          = Mage::helper('aramexshipment');
        $this->url             =   $this->helper->getWsdlPath(0).'Tracking.wsdl';
        $this->clientAramex    =   new SoapClient( $this->url );
        $this->helperstatus          = Mage::helper('orderstatuses');
    }

    public function aramextrack( $aramexParams ){
        $data = array('status'=>'unknown','msg'=>'No record Found.');
        $_resAramex = $this->clientAramex->TrackShipments($aramexParams);
        if (is_object($_resAramex) && !$_resAramex->HasErrors) {
            if (!empty($_resAramex->TrackingResults->KeyValueOfstringArrayOfTrackingResultmFAkxlpY->Value->TrackingResult)) {
                $results = $_resAramex->TrackingResults->KeyValueOfstringArrayOfTrackingResultmFAkxlpY->Value->TrackingResult;
                if( count( $results ) > 1 ){
                    $result = $results[0] ;
                    if( !empty($result) && $result->UpdateDescription == 'Delivered' ){
                        $data =  array('status'=>'delivered','msg'=>'Successfully Delivered');
                    }else{
                        $data =  array('status'=>'unknown','msg'=> $result->UpdateDescription );
                    }
                }else{
                    if( !empty($results) && $results->UpdateDescription == 'Delivered' ){
                        $data =  array('status'=>'delivered','msg'=>'Successfully Delivered');
                    }else{
                        $data =  array('status'=>'unknown','msg'=> $results->UpdateDescription );
                    }
                }
            }else{
                $data =  array('status'=>'noexist','msg'=>'Aramex not exist.');
            }
        } else {
            if( $_resAramex->HasErrors ){
                $errorMessage = '';
                foreach ($_resAramex->Notifications as $notification) {
                    $errorMessage .= $notification->Message;
                }
            }
            $data =  array('status'=>'error','msg'=>$errorMessage);
        }
        return $data;
    }

    public function run(){
        echo " Cron Start Work. \n";
        try{
            /*Check Module is Enable or not.*/
            if( !$this->getEnable() )
                return "Please Enable Extension. \n";

            $records = $this->getRecords();
            $aramexParams = $this->_getAuthDetails();
            $aramexParams['Transaction'] = array('Reference1' => '001');

            foreach( $records as $record ){
                if( empty( $record->getTrackingnumber() ) )
                    continue;
                $orderId        =   $record->getOrderId();
                $trackingNo     =   $record->getTrackingnumber();
                $aramexParams['Shipments'] = array($trackingNo);
                $response       =   $this->aramextrack( $aramexParams );
                echo "Order Number: ".$orderId."=== Tracking Number: ".$trackingNo." Message: ".$response['msg']."\n";
                if( $response['status'] == 'delivered' ){
                    $mageworxStatus = false;
                    $orderStatus = false;
                    try {
                        $order = Mage::getModel('sales/order')->load($orderId);
                        $order->setAramexstatus(1);
                        $order->setAramexstatusFlag(1);
                        $order->setUpdatedAt($order->getUpdatedAt());
                        $order->setTempTime($order->getUpdatedAt());
                        $order->save();
                        $orderStatus = true;
                    }catch(Exception $ord){
                        echo $orderId." Order issue.\n";
                    }
                    try {
                        $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($orderId);
                        $mageworx->setAramexstatus(1);
                        $mageworx->setAramexstatusFlag(1);
                        $mageworx->setUpdatedAt($mageworx->getUpdatedAt());
                        $mageworx->save();
                        $mageworxStatus = true;
                    }catch(Exception $mage){
                        echo $orderId." Mageworx issue.\n";
                    }

                    $record->setAramexstatus(1);
                    $record->setAramexstatusFlag(1);
                    $record->save();
                    if( ( $record->getOrderId() && $this->orderstatusUpdate ) && ( $mageworxStatus &&  $orderStatus ) ){
                        $this->helperstatus->setOrderStatusSuccesfullDeliver($order);
                    }
                }else if( $response['status'] == 'noexist' ){
                    try {
                        $order = Mage::getModel('sales/order')->load($orderId);
                        $order->setAramexstatusFlag(3);
                        $order->setUpdatedAt($order->getUpdatedAt());
                        $order->setTempTime($order->getUpdatedAt());
                        $order->save();
                    }catch(Exception $ord){
                        echo $orderId." Order issue.\n";
                    }
                    try {
                        $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($orderId);
                        $mageworx->setAramexstatusFlag(3);
                        $mageworx->setUpdatedAt($mageworx->getUpdatedAt());
                        $mageworx->save();
                    }catch(Exception $mage){
                        echo $orderId." Mageworx issue.\n";
                    }
                    $record->setAramexstatusFlag(3);
                    $record->save();
                    continue;
                }else{
                    continue;
                }
            }
        }catch (Exception $ecore){
            echo $ecore->getMessage()." \n";
        }
        return "All Script Executed. \n";
    }

    /**
     * Return array of authenticated information
     *
     * @return array
     */
    protected function _getAuthDetails() {
        $storeId = 0;
        return array(
            'ClientInfo' => array(
                'AccountCountryCode' => Mage::getStoreConfig('aramexsettings/settings/account_country_code', $storeId),
                'AccountEntity' => Mage::getStoreConfig('aramexsettings/settings/account_entity', $storeId),
                'AccountNumber' => Mage::getStoreConfig('aramexsettings/settings/account_number', $storeId),
                'AccountPin' => Mage::getStoreConfig('aramexsettings/settings/account_pin', $storeId),
                'UserName' => Mage::getStoreConfig('aramexsettings/settings/user_name', $storeId),
                'Password' => Mage::getStoreConfig('aramexsettings/settings/password', $storeId),
                'Version' => 'v1.0'
            )
        );
    }


    public function getRecords(){
        $limit = $this->getNoOfOrderProcess();
        $status   =   rtrim(Mage::getStoreConfig('aramexsettings/aramexlabelstatus/status'),',');
        $status   = explode(',',$status);
        $aramexstatuscollection = Mage::getModel('customorderflags/aramexlabel')->getCollection()
            ->addFieldToFilter('aramexstatus_flag',  array('IN'=> $status ));
        
        return $aramexstatuscollection;

    }

    public function getNoOfOrderProcess(){
        $number =  Mage::getStoreConfig('aramexsettings/aramexlabelstatus/no_of_orders_process');
        if( !empty( $number ) ){
            return $number;
        }else{
            return 20;
        }
    }

    public function getEnable(){
        $status = Mage::getStoreConfig('aramexsettings/aramexlabelstatus/enabled');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function getOrderStatus(){
        $orderStatus = Mage::getStoreConfig('aramexsettings/aramexlabelstatus/orderstatus');
        if( $orderStatus == '1' )
            return true;
        else
            return false;
    }

    /*
     * This function sync all aramex shippments into seperate( aramexlabel ) table. And we use these records to update aramex shipment status
     * */

    public function syncrecord(){
        try {
            $query = 'SELECT * FROM sales_flat_shipment_track 
                      Where order_id NOT IN(select order_id from aramexlabel ) 
                      AND carrier_code like "%aramex%" AND track_number is not null';

            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $result = $connection->fetchAll($query);
            Mage::log(print_r($result,true),null,'aramex_sync.log');
            foreach ($result as $shipment) {
                $aramexCollection = Mage::getModel('customorderflags/aramexlabel')
                    ->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('trackingnumber', array('eq' => $shipment['track_number']));
                Mage::log(print_r($aramexCollection->getData(),true),null,'aramex_sync.log');
                if (empty($aramexCollection->getData())) {
                    $aramex = Mage::getModel('customorderflags/aramexlabel')->load();
                    $aramex->setTrackingnumber($shipment['track_number']);
                    $aramex->setOrderId($shipment['order_id']);
                    $aramex->setCreatedTime($shipment['created_at']);
                    $aramex->setUpdateTime(date('Y-m-d H:i:s'));
                    $aramex->save();
                }
            }
        }catch (Exception $e){
            return $e->getMessage();
        }
        return "All Aramex Tracking are synced.";
    }
}