<?php

class Apptha_Marketplace_StoretittlesettingController extends Mage_Core_Controller_Front_Action
{
    CONST SECRET_TOKEN = "1bRtfmoW7jnEio0O";

    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function setupStoreTitlesAction()
    {

        $token = $this->getRequest()->getParam('token');
        if ($token == self::SECRET_TOKEN) {
            echo "Setting store titles now...";
            Mage::getSingleton('marketplace/sellerprofile')->setSellerStoreTitles();
            echo "Completed.";
        } else {
            $this->getResponse()->setBody("Bad request");
        }

        return;
    }
    
    public function getAllOptions()
      {
        $model = Mage::getModel('customer/customer');
        $collection = $model->getCollection();
        $condition = new Zend_Db_Expr("market.seller_id = e.entity_id AND e.is_active = 1");
        $collection->getSelect()->join(array('market' => $collection->getTable('marketplace/sellerprofile')),
        $condition,
        array('store_title' => 'market.store_title'));
        $customerArr = array();
        foreach($collection as $customer)
        {
            $customerArr[] = array($customer->getId());
        }
        if (!$this->_options) {
            $this->_options = $customerArr;
        }
        return $this->_options;
      }

    protected function getMessage($type, $msg = "")
    {
        $defaultMsg = ($type == "error") ? ['status' => "error", 'msg' => "There is some error performing update"] : ['status' => "success", 'msg' => "Record updated successfully."];
        return (!empty($msg)) ? ['success' => $type, 'msg' => $msg] : $defaultMsg;
    }

}