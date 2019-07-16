<?php
class Progos_Syncproduct_IndexController extends Mage_Core_Controller_Front_Action {
    public function indexAction(){
        $result = array();
        $result['errorStatus']  =   true;
        $result['successStatus']=   false;
        $params             =   json_decode($this->getRequest()->getRawBody());
        if( $params ){
            $model          =   Mage::getModel('progos_syncproduct/syncproduct');
            $auth           =   $model->authenticate( $params );
            if( $model->getConfig()->getStatus() ) {
                if ($auth['status']) {
                    if( $params->request == 'fetch'){
                        $result['data']             =   $model->prepareProducts();
                        $result['successStatus']    =   true;
                        $result['errorStatus']      =   false;
                    }else if( $params->request == 'update'){
                        $result['data']             =   $model->updateStatus( $params );
                        $result['successStatus']    =   true;
                        $result['errorStatus']      =   false;
                    }else {
                        $result['errorStatus']  =   true;
                        $result['error']        =   "Wrong Request.";
                    }
                } else {
                    $result['errorStatus']  =   true;
                    $result['error']        =   $auth['error'];
                }
            }else{
                $result['errorStatus']  =   true;
                $result['error']        =   "Extension Disabled.";
            }
        }else{
            $result['errorStatus']  =   true;
            $result['error']        =   "Data Param Empty.";
        }
        echo json_encode($result);
    }
}