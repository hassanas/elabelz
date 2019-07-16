<?php

class Progos_DirectAccess_Adminhtml_AutomateorderbackendController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__("Order Automate"));
        $this->renderLayout();
    }

    public function processOrdersAction(){
        $result = array();
        if( !empty( $this->getRequest()->getParam("order_id")) ){
            $order_id = trim($this->getRequest()->getParam("order_id"));
            $model = Mage::getModel('directaccess/automateorder');
            $result = $model->process( $order_id );
        }else{
            $result['status'] = false;
            $result['msg']    = "Invalid Order Id.";
        }
        $jsonData = json_encode($result);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}