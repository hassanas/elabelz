<?php
/*
** @author: Sooraj Malhi <sooraj.malhi@progos.org>
** @package: Rewrite_CAramexShipment
** @description: Rewrite Shipment controller postAction to prevent shipment before invoice
*/
require_once (Mage::getModuleDir('controllers','Aramex_Shipment').DS.'ShipmentController.php');

class Rewrite_CAramexShipment_ShipmentController extends Aramex_Shipment_ShipmentController {

    public function postAction() {
        $post = $this->getRequest()->getPost();
        $storeId = $post['store_id'];
        $baseUrl = Mage::helper('aramexshipment')->getWsdlPath($storeId);
        //SOAP object
        $soapClient = new SoapClient($baseUrl . 'shipping.wsdl');
        $aramex_errors = false;


        $flag = true;
        $error = "";
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            /* here's your form processing */
            $order = Mage::getModel('sales/order')->loadByIncrementId($post['aramex_shipment_original_reference']);

            /* Redirect to order page if no invoice is created yet */
            if(!$order->hasInvoices()) {
                Mage::getSingleton('adminhtml/session')->addError('Before shipment please create invoice of this order.');
                $this->_redirect('adminhtml/sales_order/view/', array('order_id'=>$order->getId()));
                return;
            }

            $payment = $order->getPayment();

            $totalWeight = 0;
            $totalItems = 0;

            $items = $order->getAllItems();

            $descriptionOfGoods = '';
            foreach ($order->getAllVisibleItems() as $itemname) {
                $descriptionOfGoods .= $itemname->getId() . ' - ' . trim($itemname->getName());
            }
            $descriptionOfGoods = substr($descriptionOfGoods, 0, 65);
            $aramex_items_counter = 0;
            foreach ($post['aramex_items'] as $key => $value) {
                $aramex_items_counter++;
                if ($value != 0) {
                    //itrating order items
                    foreach ($items as $item) {
                        if ($item->getId() == $key) {
                            //get weight
                            if ($item->getWeight() != 0) {
                                $weight = $item->getWeight() * $item->getQtyOrdered();
                            } else {
                                $weight = 0.5 * $item->getQtyOrdered();
                            }

                            // collect items for aramex
                            $aramex_items[] = array(
                                'PackageType' => 'Box',
                                'Quantity' => $post[$item->getId()],
                                'Weight' => array(
                                    'Value' => $weight,
                                    'Unit' => 'Kg'
                                ),
                                'Comments' => $item->getName(), //'',
                                'Reference' => ''
                            );

                            $totalWeight += $weight;
                            //$totalItems += $post[$item->getId()];
                        }
                    }
                }
            }

            $totalItems = (trim($post['number_pieces']) == '') ? 1 : (int)$post['number_pieces'];
            $aramex_atachments = array();
            //attachment
            for ($i = 1; $i <= 3; $i++) {
                $fileName = $_FILES['file' . $i]['name'];
                if (isset($fileName) != '') {
                    $fileName = explode('.', $fileName);
                    $fileName = $fileName[0]; //filename without extension
                    $fileData = '';
                    if ($_FILES['file' . $i]['tmp_name'] != '')
                        $fileData = file_get_contents($_FILES['file' . $i]['tmp_name']);
                    //$fileData = base64_encode($fileData); //base64binary encode
                    $ext = pathinfo($_FILES['file' . $i]['name'], PATHINFO_EXTENSION); //file extension
                    if ($fileName && $ext && $fileData)
                        $aramex_atachments[] = array(
                            'FileName' => $fileName,
                            'FileExtension' => $ext,
                            'FileContents' => $fileData
                        );
                }
            }
            $totalWeight = $post['order_weight'];
            $params = array();

            if($post['aramex_shipment_shipper_account_show'] == 1){
                $AccountNumber_1 = ($post['aramex_shipment_info_billing_account'] == 1) ? $post['aramex_shipment_shipper_account'] : $post['aramex_shipment_shipper_account'];
                $AccountPin_1 =  ($post['aramex_shipment_info_billing_account'] == 1) ? $post['aramex_shipment_shipper_account_pin'] : $post['aramex_shipment_shipper_account_pin'];
                # $AccountNumber_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account'] : '';
                # $AccountPin_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account_pin'] : '';
                $AccountNumber_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account'] : $post['aramex_shipment_shipper_account'];
                $AccountPin_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account_pin'] : $post['aramex_shipment_shipper_account_pin'];
                $AccountNumber_3 = $post['aramex_shipment_shipper_account'];
                $AccountPin_3 = $post['aramex_shipment_shipper_account_pin'];
            }else{
                $AccountNumber_1 = ($post['aramex_shipment_info_billing_account'] == 1) ? $post['aramex_shipment_shipper_account_cod'] : $post['aramex_shipment_shipper_account_cod'];
                $AccountPin_1 =  ($post['aramex_shipment_info_billing_account'] == 1) ? $post['aramex_shipment_shipper_account_pin_cod'] : $post['aramex_shipment_shipper_account_pin_cod'];
                $AccountNumber_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account_cod'] : '';
                $AccountPin_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account_pin_cod'] : '';
                $AccountNumber_3 = $post['aramex_shipment_shipper_account_cod'];
                $AccountPin_3 = $post['aramex_shipment_shipper_account_pin_cod'];
            }

            //shipper parameters
            $params['Shipper'] = array(
                'Reference1' => $post['aramex_shipment_shipper_reference'], //'ref11111',
                'Reference2' => '',
                'AccountNumber' => $AccountNumber_1,
                'AccountPin' => $AccountPin_1,
                //Party Address
                'PartyAddress' => array(
                    'Line1' => addslashes($post['aramex_shipment_shipper_street']), //'13 Mecca St',
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $post['aramex_shipment_shipper_city'], //'Dubai',
                    'StateOrProvinceCode' => $post['aramex_shipment_shipper_state'], //'',
                    'PostCode' => $post['aramex_shipment_shipper_postal'],
                    'CountryCode' => $post['aramex_shipment_shipper_country'], //'AE'
                ),
                //Contact Info
                'Contact' => array(
                    'Department' => '',
                    'PersonName' => $post['aramex_shipment_shipper_name'], //'Suheir',
                    'Title' => '',
                    'CompanyName' => $post['aramex_shipment_shipper_company'], //'Aramex',
                    'PhoneNumber1' => $post['aramex_shipment_shipper_phone'], //'55555555',
                    'PhoneNumber1Ext' => '',
                    'PhoneNumber2' => '',
                    'PhoneNumber2Ext' => '',
                    'FaxNumber' => '',
                    'CellPhone' => $post['aramex_shipment_shipper_phone'],
                    'EmailAddress' => $post['aramex_shipment_shipper_email'], //'',
                    'Type' => ''
                ),
            );

            //consinee parameters
            $params['Consignee'] = array(
                'Reference1' => $post['aramex_shipment_receiver_reference'], //'',
                'Reference2' => '',
                'AccountNumber' => $AccountNumber_2,
                'AccountPin' => $AccountPin_2,
                //Party Address
                'PartyAddress' => array(
                    'Line1' => $post['aramex_shipment_receiver_street'], //'15 ABC St',
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $post['aramex_shipment_receiver_city'], //'Amman',
                    'StateOrProvinceCode' => '',
                    'PostCode' => $post['aramex_shipment_receiver_postal'],
                    'CountryCode' => $post['aramex_shipment_receiver_country'], //'JO'
                ),
                //Contact Info
                'Contact' => array(
                    'Department' => '',
                    'PersonName' => $post['aramex_shipment_receiver_name'], //'Mazen',
                    'Title' => '',
                    'CompanyName' => $post['aramex_shipment_receiver_company'], //'Aramex',
                    'PhoneNumber1' => $post['aramex_shipment_receiver_phone'], //'6666666',
                    'PhoneNumber1Ext' => '',
                    'PhoneNumber2' => '',
                    'PhoneNumber2Ext' => '',
                    'FaxNumber' => '',
                    'CellPhone' => $post['aramex_shipment_receiver_phone'],
                    'EmailAddress' => $post['aramex_shipment_receiver_email'], //'mazen@aramex.com',
                    'Type' => ''
                )
            );

            //new

            if ($post['aramex_shipment_info_billing_account'] == 3) {
                $params['ThirdParty'] = array(
                    'Reference1' => $post['aramex_shipment_shipper_reference'], //'ref11111',
                    'Reference2' => '',
                    'AccountNumber' => $AccountNumber_3,
                    'AccountPin' => $AccountPin_3,
                    //Party Address
                    'PartyAddress' => array(
                        'Line1' => addslashes(Mage::getStoreConfig('aramexsettings/shipperdetail/address',$storeId)), //'13 Mecca St',
                        'Line2' => '',
                        'Line3' => '',
                        'City' => Mage::getStoreConfig('aramexsettings/shipperdetail/city', $storeId), //'Dubai',
                        'StateOrProvinceCode' => Mage::getStoreConfig('aramexsettings/shipperdetail/state', $storeId), //'',
                        'PostCode' => Mage::getStoreConfig('aramexsettings/shipperdetail/postalcode', $storeId),
                        'CountryCode' => Mage::getStoreConfig('aramexsettings/shipperdetail/country', $storeId), //'AE'
                    ),
                    //Contact Info
                    'Contact' => array(
                        'Department' => '',
                        'PersonName' => Mage::getStoreConfig('aramexsettings/shipperdetail/name', $storeId), //'Suheir',
                        'Title' => '',
                        'CompanyName' => Mage::getStoreConfig('aramexsettings/shipperdetail/company', $storeId), //'Aramex',
                        'PhoneNumber1' => Mage::getStoreConfig('aramexsettings/shipperdetail/phone', $storeId), //'55555555',
                        'PhoneNumber1Ext' => '',
                        'PhoneNumber2' => '',
                        'PhoneNumber2Ext' => '',
                        'FaxNumber' => '',
                        'CellPhone' => Mage::getStoreConfig('aramexsettings/shipperdetail/phone', $storeId),
                        'EmailAddress' => Mage::getStoreConfig('aramexsettings/shipperdetail/email', $storeId), //'',
                        'Type' => ''
                    ),
                );
            }
            // Other Main Shipment Parameters
            $params['Reference1'] = $post['aramex_shipment_info_reference']; //'Shpt0001';
            $params['Reference2'] = '';
            $params['Reference3'] = '';
            $params['ForeignHAWB'] = $post['aramex_shipment_info_foreignhawb'];

            $params['TransportType'] = 0;
            $params['ShippingDateTime'] = time(); //date('m/d/Y g:i:sA');
            $params['DueDate'] = time() + (7 * 24 * 60 * 60); //date('m/d/Y g:i:sA');
            $params['PickupLocation'] = 'Reception';
            $params['PickupGUID'] = '';
            $params['Comments'] = $post['aramex_shipment_info_comment'];
            $params['AccountingInstrcutions'] = '';
            $params['OperationsInstructions'] = '';

            ////// add COD
            $services = array();
            if($post['aramex_shipment_info_product_type'] == "CDA"){
                if( $post['aramex_shipment_info_service_type'] == null ){
                    array_push($services, "CODS");
                }elseif ( !in_array("CODS", $post['aramex_shipment_info_service_type'])){
                    $services = array_merge($services, $post['aramex_shipment_info_service_type']);
                    array_push($services, "CODS");
                }else{
                    $services = array_merge($services, $post['aramex_shipment_info_service_type']);
                }
            }else{
                if($post['aramex_shipment_info_service_type'] == null){
                    $post['aramex_shipment_info_service_type'] = array();
                }

                $services = array_merge($services, $post['aramex_shipment_info_service_type']);
            }
            $services = implode(',', $services);
            ///// add COD and

            //$aramex_services = implode(",", $aramex_services);
            $params['Details'] = array(
                'Dimensions' => array(
                    'Length' => '0',
                    'Width' => '0',
                    'Height' => '0',
                    'Unit' => 'cm'
                ),
                'ActualWeight' => array(
                    'Value' => $totalWeight,
                    'Unit' => $post['weight_unit']
                ),
                'ProductGroup' => $post['aramex_shipment_info_product_group'], //'EXP',
                'ProductType' => $post['aramex_shipment_info_product_type'], //,'PDX'
                'PaymentType' => $post['aramex_shipment_info_payment_type'],
                'PaymentOptions' => $post['aramex_shipment_info_payment_option'], //$post['aramex_shipment_info_payment_option']
                'Services' => $services,
                'NumberOfPieces' => $totalItems,
                'DescriptionOfGoods' => (trim($post['aramex_shipment_description']) == '') ? $descriptionOfGoods : $post['aramex_shipment_description'],
                'GoodsOriginCountry' => $post['aramex_shipment_shipper_country'], //'JO',
                'Items' => $totalItems
            );


            if (count($aramex_atachments)) {
                $params['Attachments'] = $aramex_atachments;
            }

            $params['Details']['CashOnDeliveryAmount'] = array(
                'Value' => $post['aramex_shipment_info_cod_amount'],
                'CurrencyCode' => $post['aramex_shipment_currency_code']
            );


            $params['Details']['CustomsValueAmount'] = array(
                'Value' => $post['aramex_shipment_info_custom_amount'],
                'CurrencyCode' => $post['aramex_shipment_currency_code_custom']
            );
            $major_par['Shipments'][] = $params;
            if($post['aramex_shipment_shipper_account_show'] == 1){

                $clientInfo = Mage::helper('aramexshipment')->getClientInfo($storeId);
            }else{
                $clientInfo = Mage::helper('aramexshipment')->getClientInfoCOD($storeId);
            }


            $major_par['ClientInfo'] = $clientInfo;
            $report_id = (int) Mage::getStoreConfig('aramexsettings/config/report_id', $storeId);
            if (!$report_id) {
                $report_id = 9729;
            }

            $major_par['LabelInfo'] = array(
                'ReportID' => $report_id, //'9201',
                'ReportType' => 'URL'
            );


            $formSession = Mage::getSingleton('adminhtml/session');
            $formSession->setData("form_data", $post);

            try {
                //create shipment call
                $auth_call = $soapClient->CreateShipments($major_par);
                if ($auth_call->HasErrors) {
                    if (empty($auth_call->Shipments)) {
                        if (count($auth_call->Notifications->Notification) > 1) {
                            foreach ($auth_call->Notifications->Notification as $notify_error) {
                                Mage::throwException($this->__('Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message));
                            }
                        } else {
                            Mage::throwException($this->__('Aramex: ' . $auth_call->Notifications->Notification->Code . ' - ' . $auth_call->Notifications->Notification->Message));
                        }
                    } else {

                        if (isset($auth_call->Notifications->Notification)) {
                            if (count($auth_call->Notifications->Notification) > 1) {
                                $notification_string = '';
                                foreach ($auth_call->Notifications->Notification as $notification_error) {
                                    $notification_string .= $notification_error->Code . ' - ' . $notification_error->Message . ' <br />';
                                }
                                Mage::throwException($notification_string);
                            } else {
                                Mage::throwException( 'Aramex: ' . $auth_call->Notifications->Notification->Code . ' - ' . $auth_call->Notifications->Notification->Message );
                            }
                        } else {
                            if (count($auth_call->Shipments->ProcessedShipment->Notifications->Notification) > 1) {
                                $notification_string = '';
                                foreach ($auth_call->Shipments->ProcessedShipment->Notifications->Notification as $notification_error) {
                                    $notification_string .= $notification_error->Code . ' - ' . $notification_error->Message . ' <br />';
                                }
                                Mage::throwException($notification_string);
                            } else {
                                Mage::throwException('Aramex: ' . $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Code . ' - ' . $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Message );
                            }
                        }
                    }
                } else {
                    if ($order->canShip() && $post['aramex_return_shipment_creation_date'] == "create") {
                        $shipmentid = Mage::getModel('sales/order_shipment_api')->create($order->getIncrementId(), $post['aramex_items'], "AWB No. " . $auth_call->Shipments->ProcessedShipment->ID . " - Order No. " . $auth_call->Shipments->ProcessedShipment->Reference1 . " - <a style='cursor:pointer' onclick='myObj.printLabel(". $auth_call->Shipments->ProcessedShipment->ID .");'>Print Label</a>");
                        $ship = true;
                        $ship = Mage::getModel('sales/order_shipment_api')->addTrack($shipmentid, 'aramex', 'Aramex', $auth_call->Shipments->ProcessedShipment->ID);
                        /* sending mail */
                        if ($ship) {
                            $shipments_type = "Created";
                            $this->sendMail($order, $auth_call, $shipments_type);
                        }

                        Mage::getSingleton('core/session')->addSuccess('Aramex Shipment Number: ' . $auth_call->Shipments->ProcessedShipment->ID . ' has been created.');
                        /* $order->setState('warehouse_pickup_shipped', true); */
                    } elseif ($post['aramex_return_shipment_creation_date'] == "return") {
                        if ($post['aramex_email_customer'] == 'yes') {
                            $shipments_type = "Return";
                            $this->sendMail($order, $auth_call, $shipments_type);
                        }
                        $message = "Aramex Shipment Return Order AWB No. " . $auth_call->Shipments->ProcessedShipment->ID . " - Order No. " . $auth_call->Shipments->ProcessedShipment->Reference1 . " - <a  style='cursor:pointer' onclick='myObj.printLabel( ". $auth_call->Shipments->ProcessedShipment->ID .");'>Print Label</a>";
                        Mage::getSingleton('core/session')->addSuccess('Aramex Shipment Return Order Number: ' . $auth_call->Shipments->ProcessedShipment->ID . ' has been created.');
                        $order->addStatusToHistory($order->getStatus(), $message,  array('p',  'em'));

                        $order->save();
                    } else {
                        Mage::throwException($this->__('Cannot do shipment for the order.'));
                    }
                }
            } catch (Exception $e) {

                $aramex_errors = true;
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }

            if ($aramex_errors) {
                $strip = strstr($post['aramex_shipment_referer'], "aramexpopup", true);
                $url = $strip;
                if (empty($strip)) {
                    $url = $post['aramex_shipment_referer'];
                }
                $this->_redirectUrl($url . 'aramexpopup/show');
            } else {
                $this->_redirectUrl($post['aramex_shipment_referer']);
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }
    /*
     *  @overried by : Saroop
     *  @date        : 18-04-2017
     *  @ticket      : ELABELZ-1355
     *  This method override becuase readfile is not working with weburl. So we will get file content by using curl.
     *  Code changes start from Line 494 to 515
     * */
    public function printLabelAction() {
        $_order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));
        $storeId = $_order->getStore()->getId();
        $previuosUrl = Mage::getSingleton('core/session')->getPreviousUrl();

        if ($_order->getId()) {
            $baseUrl = Mage::helper('aramexshipment')->getWsdlPath($storeId);
            $soapClient = new SoapClient($baseUrl . 'shipping.wsdl');
            $clientInfo = Mage::helper('aramexshipment')->getClientInfo($storeId);
            $commentTable = Mage::getSingleton('core/resource')->getTableName('sales/shipment_comment');
            $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                ->addAttributeToSelect('*')
                ->addFieldToFilter("order_id", $_order->getId())->join("sales/shipment_comment", 'main_table.entity_id=parent_id', 'comment')->addFieldToFilter('comment', array('like' => "%{$_order->getIncrementId()}%"))->load();

            $awbno = '';
            $orderHistory = Mage::getModel('sales/order_status_history')->getCollection()
                ->addFieldToFilter('parent_id', $_order->getId())->setOrder('created_at', 'desc');
            foreach ($orderHistory as $history) {
                $comments = $history->getComment();
                if ($comments && preg_match('/Aramex Shipment Return Order AWB No. ([0-9]+)/', $comments, $cmatches)) {
                    $awbno = $cmatches[1];
                    break;
                }
            }
            if ($shipments->count()) {
                $storeId = Mage::app()->getStore()->getStoreId();
                $report_id = (int) Mage::getStoreConfig('aramexsettings/config/report_id', $storeId);
                if (!$report_id) {
                    $report_id = 9729;
                }
                $params = array(
                    'ClientInfo' => $clientInfo,
                    'Transaction' => array(
                        'Reference1' => $_order->getIncrementId(),
                        'Reference2' => '',
                        'Reference3' => '',
                        'Reference4' => '',
                        'Reference5' => '',
                    ),
                    'LabelInfo' => array(
                        'ReportID' => $report_id,
                        'ReportType' => 'URL',
                    ),
                );
                $params['ShipmentNumber'] = $this->getRequest()->getParam('number');
                if($params['ShipmentNumber'] == "undefined"){

                    foreach ($shipments as $shipment) {
                        $comments = $shipment->getComment();
                        if($comments && preg_match('/AWB No. ([0-9]+) - Order No./', $comments, $cmatches)){
                            $params['ShipmentNumber'] = $cmatches[1];
                        }
                    }
                }
                try {
                    $auth_call = $soapClient->PrintLabel($params);
                    /* bof  PDF demaged Fixes debug */
                    if ($auth_call->HasErrors) {
                        if (count($auth_call->Notifications->Notification) > 1) {
                            foreach ($auth_call->Notifications->Notification as $notify_error) {
                                Mage::throwException($this->__('Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message));
                            }
                        } else {
                            Mage::throwException($this->__('Aramex: ' . $auth_call->Notifications->Notification->Code . ' - ' . $auth_call->Notifications->Notification->Message));
                        }
                    }
                    /* eof  PDF demaged Fixes */
                    $filepath = $auth_call->ShipmentLabel->LabelURL;

                    $name = "{$_order->getIncrementId()}-shipment-label.pdf";

                    set_time_limit(0);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $filepath);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    $r = curl_exec($ch);
                    curl_close($ch);
                    header('Expires: 0'); // no cache
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
                    header('Cache-Control: private', false);
                    header('Content-Type: application/force-download');
                    header('Content-Disposition: attachment; filename="' . $name . '"');
                    header('Content-Transfer-Encoding: binary');
                    header('Content-Length: ' . strlen($r)); // provide file size
                    header('Connection: close');
                    echo $r;
                    exit();

                } catch (SoapFault $fault) {
                    Mage::getSingleton('adminhtml/session')->addError('Error : ' . $fault->faultstring);
                    $this->_redirectUrl($previuosUrl);
                } catch (Exception $e) {
                    $aramex_errors = true;
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    $this->_redirectUrl($previuosUrl);
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addError($this->__('Shipment is empty or not created yet.'));
                $this->_redirectUrl($previuosUrl);
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError($this->__('This order no longer exists.'));
            $this->_redirectUrl($previuosUrl);
        }
    }
}