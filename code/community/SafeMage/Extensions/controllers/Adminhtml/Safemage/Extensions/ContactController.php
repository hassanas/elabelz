<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Adminhtml_Safemage_Extensions_ContactController extends Mage_Adminhtml_Controller_Action
{
    public function sendAction()
    {
        $result = array('success' => true);
        try {
            $data = $this->getRequest()->getPost();
            $data['version'] = Mage::getVersion();
            $data['url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            $data['id'] = '77-90-52-317';
            $this->_sendContactEmail($data);
        } catch (Exception $e) {
            Mage::logException($e);
            $result['success'] = false;
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        }
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    protected function _sendContactEmail($data)
    {
        $params = Mage::helper('core')->urlEncode(Mage::helper('core')->jsonEncode($data));
        if ($params) {
            $httpClient = new Varien_Http_Client();
            $response = $httpClient
                ->setMethod(Zend_Http_Client::POST)
                ->setUri('http://www.safemage.com/safemage_support/index/index/')
                ->setConfig(
                    array(
                        'maxredirects' => 0,
                        'timeout'      => 30,
                    )
                )
                ->setRawData($params)
                ->request();
            if (!$response->isSuccessful()) {
                throw new Mage_Core_Exception($response->getBody());
            }
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/safemage_extensions');
    }
}
