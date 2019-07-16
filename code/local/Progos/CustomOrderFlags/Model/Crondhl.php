<?php

/**
 * Progos_CustomOrderFlags.
 *
 * @category Elabelz
 *
 * @Author Saroop Chand <saroop.chand@progos.org>
 * @Date 14-06-2018
 *
 */

class Progos_CustomOrderFlags_Model_Crondhl
{
    /*
     * FLAG = 0 => No status updated
     * FLAG = 1 => Shipment Delivered
     * FLAG = 3 => No Shipment Found
     * */
    protected $curl = null;
    protected $userid = null;
    protected $password = null;
    protected $url = null;
    protected $orderstatusUpdate = null;
    protected $helper;
    public function __construct(){
        Mage::init();
        $this->curl = new Zend_Http_Client();
        $UserId = Mage::getStoreConfig('dhllabel/credentials/userid');
        $Password = Mage::getStoreConfig('dhllabel/credentials/password');
        $this->userid        =   $UserId;
        $this->password        =   $Password;
        $this->orderstatusUpdate =   $this->getOrderStatus();
        $this->url             =   $this->getUrl();
        $this->helper          = Mage::helper('orderstatuses');
    }

    public function run(){
        echo " Cron Start Work. \n";
        try{
            /*Check Module is Enable or not.*/
            if( !$this->getEnable() )
                return "Please Enable Extension.";

            $options = Mage::getModel('customorderflags/source_dhlstatus')->toOptionArray(true);

            $records = $this->getRecords();
            foreach( $records as $record ){
                if( empty( $record->getTrackingnumber() ) )
                    continue;
                $orderId        =   $record->getOrderId();
                $trackingNo     =   $record->getTrackingnumber();
                $response       =   $this->dhltrack( $trackingNo );
                echo "Order Number: ".$orderId."=== Tracking Number: ".$trackingNo." Message: ".$response['msg']."\n";
                if( $response['status'] == 'fail' ){
                    break;
                }
                else if( $response['status'] == 'notfound' ){
                    try {
                        $order = Mage::getModel('sales/order')->load($orderId);
                        $order->setDhlstatusFlag(3);
                        $order->setUpdatedAt($order->getUpdatedAt());
                        $order->setTempTime($order->getUpdatedAt());
                        $order->save();
                    }catch(Exception $ord){
                        echo $orderId." Order issue.\n";
                    }
                    try {
                        $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($orderId);
                        $mageworx->setDhlstatusFlag(3);
                        $mageworx->setUpdatedAt($mageworx->getUpdatedAt());
                        $mageworx->save();
                    }catch(Exception $mage){
                        echo $orderId." Mageworx issue.\n";
                    }
                    $record->setDhlstatusFlag(3);
                    $record->save();
                    continue;
                }else if( $response['status'] == 'Delivered' ){
                    $mageworxStatus = false;
                    $orderStatus = false;
                    try {
                        $order = Mage::getModel('sales/order')->load($orderId);
                        $order->setDhlstatus(1);
                        $order->setDhlstatusFlag(1);
                        $order->setUpdatedAt($order->getUpdatedAt());
                        $order->setTempTime($order->getUpdatedAt());
                        $order->save();
                        $orderStatus = true;
                    }catch(Exception $ord){
                        echo $orderId." Order issue.\n";
                    }
                    try {
                        $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($orderId);
                        $mageworx->setDhlstatus(1);
                        $mageworx->setDhlstatusFlag(1);
                        $mageworx->setUpdatedAt($mageworx->getUpdatedAt());
                        $mageworx->save();
                        $mageworxStatus = true;
                    }catch(Exception $mage){
                        echo $orderId."Mageworx issue.\n";
                    }

                    $record->setDhlstatus(1);
                    $record->setDhlstatusFlag(1);
                    $record->save();
                    if( ( $record->getOrderId() && $this->orderstatusUpdate ) && ( $mageworxStatus &&  $orderStatus ) ){
                        $this->helper->setOrderStatusSuccesfullDeliver($order);
                    }
                }else{
                    continue;
                }
            }
        }catch (Exception $e){
            echo $e->getMessage();
        }
        return "All Script Executed. \n";
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

    public function dhltrack( $trackingNo ){
        try {
            $data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                <req:KnownTrackingRequest xmlns:req=\"http://www.dhl.com\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" 
                xsi:schemaLocation=\"http://www.dhl.com TrackingRequestKnown.xsd\">
                <Request xmlns=\"\">
                    <ServiceHeader xmlns=\"\">
                        <SiteID>".$this->userid."</SiteID>
                        <Password>".$this->password."</Password>
                    </ServiceHeader>
                </Request>
                <LanguageCode xmlns=\"\">EN</LanguageCode>
                <AWBNumber xmlns=\"\">".$trackingNo."</AWBNumber>
                <LevelOfDetails xmlns=\"\">LAST_CHECK_POINT_ONLY</LevelOfDetails></req:KnownTrackingRequest>";
            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $result = curl_exec($ch);
            $data = strstr($result, '<?');
            $xml_parser = xml_parser_create();
            xml_parse_into_struct($xml_parser, $data, $vals, $index);
            xml_parser_free($xml_parser);

            $actionStatusIndex = array_search('ACTIONSTATUS', array_column($vals, 'tag'));
            $actionResult = $vals[$actionStatusIndex];
            if ( !empty($actionResult) ) {
                if( $actionResult['value'] == 'Failure' ){
                    return array('status'=>'fail','msg'=>'Credentials or Anyother information is wrong.');
                }else if( $actionResult['value'] == 'success' ){
                    $matches = array();
                    $pattern = "/Delivered/i";
                    foreach( array_reverse($vals) as $key=> $value ){
                        foreach($value as $key2=>$value2){
                            if( empty($value2) || is_array($value2))
                                continue;
                            if(preg_match( $pattern, $value2 )){
                                $matches[$key]=$value;
                                break;
                            }
                        }
                    }
                    if( !empty( $matches ) ){
                        return array('status'=>'Delivered','msg'=>'Delivered');
                    }else{
                        return array('status'=>'processing','msg'=>'Shipment In Process Or May be having some issue.');
                    }
                }else if( $actionResult['value'] == 'No Shipments Found' ){
                    return array('status'=>'notfound','msg'=>'No Shipments Found.');
                }else{
                    return array('status'=>'processing','msg'=>'No Any Response Coming.');
                }
            }
        }catch( Exception $e ){
            return array('status'=>'processing','msg'=>'Magento Exception.');
        }
    }

    public function getRecords(){
        $limit = $this->getNoOfOrderProcess();
        $status   =   rtrim(Mage::getStoreConfig('dhllabel/dhlstatus/status'),',');
        $status   = explode(',',$status);
        $dhlstatuscollection = Mage::getModel('dhllabel/dhllabel')->getCollection()
            ->addFieldToFilter('dhlstatus_flag',  array('IN'=> $status ));

        return $dhlstatuscollection;

    }

    public function getNoOfOrderProcess(){
        $number =  Mage::getStoreConfig('dhllabel/dhlstatus/no_of_orders_process');
        if( !empty( $number ) ){
            return $number;
        }else{
            return 20;
        }
    }

    public function getEnable(){
        $status = Mage::getStoreConfig('dhllabel/dhlstatus/enabled');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function getUrl(){
        $status = Mage::getStoreConfig('dhllabel/testmode/testing');
        if( $status == '1' )
            $url = "https://xmlpitest-ea.dhl.com/XMLShippingServlet";
        else
            $url = "https://xmlpi-ea.dhl.com/XMLShippingServlet";
        return $url;
    }

    public function getOrderStatus(){
        $orderStatus = Mage::getStoreConfig('dhllabel/dhlstatus/orderstatus');
        if( $orderStatus == '1' )
            return true;
        else
            return false;
    }

    /*
     * This function sync all dhl shippments into seperate( dhllabel ) table. And we use these records to update dhl shipment status
     * */
    public function syncrecord(){
        try {
            $query = 'SELECT * FROM sales_flat_shipment_track 
                      Where order_id NOT IN(select order_id from dhllabel ) 
                      AND carrier_code like "%dhl%" AND track_number is not null';
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $result = $connection->fetchAll($query);
            Mage::log(print_r($result,true),null,'dhl_sync.log');
            foreach ($result as $shipment) {
                $dhlCollection = Mage::getModel('dhllabel/dhllabel')
                    ->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('trackingnumber', array('eq' => $shipment['track_number']));
                Mage::log(print_r($dhlCollection->getData(),true),null,'dhl_sync.log');
                if (empty($dhlCollection->getData())) {
                    $dhl = Mage::getModel('dhllabel/dhllabel')->load();
                    $dhl->setTrackingnumber($shipment['track_number']);
                    $dhl->setOrderId($shipment['order_id']);
                    $dhl->setCreatedTime($shipment['created_at']);
                    $dhl->setUpdateTime(date('Y-m-d H:i:s'));
                    $dhl->save();
                }
            }
        }catch (Exception $e){
            return $e->getMessage();
        }
        return "All Ups Tracking are synced.";
    }
}