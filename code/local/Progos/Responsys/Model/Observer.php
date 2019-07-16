<?php
/**
 * Progos_Responsys.
 * @category Elabelz
 * @Author Hassan Ali Shahzad   <hassan.ali@progos.org>
 * @Date 08 -03-2018
 *
 */
class Progos_Responsys_Model_Observer extends Mage_Core_Model_Abstract
{

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Mage_Core_Model_Store_Exception
     */
    public function addCustomerEntryOnAccountCreation(Varien_Event_Observer $observer){
        if (Mage::getStoreConfig('responsys_settings/responsys/enable') == "1") {
            $eventData = $observer->getEvent()->getDataObject();
            $customerId = $eventData->getData('entity_id');
            if (!$customerId) {
                return $this;
            }

            $data[]        = $customerId;
            $data[]        = $eventData->getData('firstname');
            $data[]        = $eventData->getData('lastname');
            $data[]        = $eventData->getData('email');
            $data[]        = $this->_getHelper()->getGender($eventData->getData('gender'));
            $data[]        = Mage::app()->getStore()->getCode();
            $data[]        = 'yes';

            //create body for add member in list: CONTACTS_LIST
            if($eventData->getData('is_subscribed') == 1){
                $data[]    = 'yes';
                $requestBody =  [
                    'recordData' => [
                        'fieldNames' =>[ "CUSTOMER_ID_", "FIRST_NAME", "LAST_NAME","EMAIL_ADDRESS_","GENDER","STORE_VIEW","ACCOUNT_CREATED","NEWSLETTER_SUBSCRIBED"],
                        'records'    =>[$data],
                        "mapTemplateName" => null
                    ],
                    'mergeRule' => [ "htmlValue" => "H","optinValue" => "I","textValue" => "T","insertOnNoMatch" => true,"updateOnMatch" => "REPLACE_ALL","matchColumnName1" => "EMAIL_ADDRESS_","matchColumnName2" => null,"matchOperator" => "NONE","optoutValue" => "O","rejectRecordIfChannelEmpty" => null,"defaultPermissionStatus" => "OPTIN"]
                ];
            }
            else{
                $requestBody =  [
                    'recordData' => [
                        'fieldNames' =>[ "CUSTOMER_ID_", "FIRST_NAME", "LAST_NAME","EMAIL_ADDRESS_","GENDER","STORE_VIEW","ACCOUNT_CREATED"],
                        'records'    =>[$data],
                        "mapTemplateName" => null
                    ],
                    'mergeRule' => [ "htmlValue" => "H","optinValue" => "I","textValue" => "T","insertOnNoMatch" => true,"updateOnMatch" => "REPLACE_ALL","matchColumnName1" => "EMAIL_ADDRESS_","matchColumnName2" => null,"matchOperator" => "NONE","optoutValue" => "O","rejectRecordIfChannelEmpty" => null,"defaultPermissionStatus" => "OPTIN"]
                ];
            }
            $model  =   Mage::getSingleton('progos_responsys/responsys');
            $model->addUpdateCustomer($requestBody);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Model_Store_Exception
     */
    public function addCustomerEntryOnOrderPlace(Varien_Event_Observer $observer){
        if (Mage::getStoreConfig('responsys_settings/responsys/enable') == "1") {
            $order      = $observer->getEvent()->getOrder();
            $gender     = (!empty($order->getCustomerGender()))?$order->getCustomerGender():"";
            $customerId = (!empty($order->getCustomerId()))?$order->getCustomerId():"";

            $data[]        = $customerId;
            $data[]        = $order->getCustomerFirstname();
            $data[]        = $order->getCustomerLastname();
            $data[]        = $order->getCustomerEmail();
            $data[]        = $this->_getHelper()->getGender($gender);
            $data[]        = Mage::app()->getStore()->getCode();
            $data[]        = 'yes';
            //create body for add member in list: CONTACTS_LIST
            $requestBody =  [
                'recordData' => [
                    'fieldNames' =>[ "CUSTOMER_ID_", "FIRST_NAME", "LAST_NAME","EMAIL_ADDRESS_","GENDER","STORE_VIEW","PURCHASED"],
                    'records'    =>[$data],
                    "mapTemplateName" => null
                ],
                'mergeRule' => [ "htmlValue" => "H","optinValue" => "I","textValue" => "T","insertOnNoMatch" => true,"updateOnMatch" => "REPLACE_ALL","matchColumnName1" => "EMAIL_ADDRESS_","matchColumnName2" => null,"matchOperator" => "NONE","optoutValue" => "O","rejectRecordIfChannelEmpty" => null,"defaultPermissionStatus" => "OPTIN"]
            ];

            $model  =   Mage::getSingleton('progos_responsys/responsys');
            $model->addUpdateCustomer($requestBody);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Model_Store_Exception
     */
    public function addCustomerEntryOnNewsletterSubscription(Varien_Event_Observer $observer){
        if( Mage::getStoreConfig('responsys_settings/responsys/enable') == "1" ) {

            $params = Mage::app()->getRequest()->getParams();
            $data[] = $params['email'];
            if (isset($params['subscriber_name']))
                $data[] = $this->_getHelper()->getGender($params['subscriber_name']);
            else
                $data[] = $this->_getHelper()->getGender("");

            $data[] = Mage::app()->getStore()->getCode();
            $data[] = "yes";

            //create body for add member in list: CONTACTS_LIST
            $requestBody =  [
                'recordData' => [
                    'fieldNames' =>["EMAIL_ADDRESS_","GENDER","STORE_VIEW","NEWSLETTER_SUBSCRIBED"],
                    'records'    =>[$data],
                    "mapTemplateName" => null
                ],
                'mergeRule' => [ "htmlValue" => "H","optinValue" => "I","textValue" => "T","insertOnNoMatch" => true,"updateOnMatch" => "REPLACE_ALL","matchColumnName1" => "EMAIL_ADDRESS_","matchColumnName2" => null,"matchOperator" => "NONE","optoutValue" => "O","rejectRecordIfChannelEmpty" => null,"defaultPermissionStatus" => "OPTIN"]
            ];

            $model  =   Mage::getSingleton('progos_responsys/responsys');
            $res = $model->addUpdateCustomer($requestBody);
            // on success trigger custom event Subscriber
            if($res){
                unset($requestBody);
                $requestBody =  [
                    'customEvent' => [
                        'eventNumberDataMapping' => null,
                        'eventDateDataMapping'   => null,
                        'eventStringDataMapping' => null
                    ],
                    'recipientData' => [[
                        'recipient' => [
                            'customerId' => null,
                            'emailAddress' => $params['email'],
                            'listName' => ['folderName' => "!MasterData",'objectName'=> "CONTACTS_LIST"],
                            'recipientId' => null,
                            'mobileNumber' => null,
                            'emailFormat' => "HTML_FORMAT"
                        ]
                    ]]
                ];
                $res = $model->triggerCustomEvent($requestBody,Mage::getStoreConfig('responsys_settings/responsys/customeventsubscriber'));
            }
        }
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper($helper = null)
    {
        return ($helper == null) ? Mage::helper('progos_responsys') : Mage::helper($helper);
    }

}