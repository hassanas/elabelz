<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_FrontShippingMethod
{
    public function toOptionArray($isMultiSelect = false)
    {
        /*multistore*/
        $storeId = Mage::app()->getRequest()->getParam('store', null);
        $code = Mage::helper('dhllabel/help')->getStoreByCode($storeId);
        if($code){
            $storeId = $code->getId();
        }
        /*multistore*/

        $option = array(array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
        $_methods = Mage::getSingleton('shipping/config')->getActiveCarriers(/*multistore*/$storeId/*multistore*/);
        foreach($_methods as $_carrierCode => $_carrier){
            if($_carrierCode !=="ups" && $_carrierCode !=="dhlint" && $_carrierCode !=="usps" && $_method = $_carrier->getAllowedMethods())  {
                /*if(!$_title = Mage::getStoreConfig('carriers/'.$_carrierCode.'/title', $storeId)) {*/
                $_title = $_carrierCode;
                /*}*/
                foreach($_method as $_mcode => $_m){
                    $_code = $_carrierCode . '_' . $_mcode;
                    $option[] = array('label' => "(".$_title.")  ". $_m, 'value' => $_code);
                }
            }
        }
        return $option;
    }
}