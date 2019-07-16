<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Touqeer Jalal <touqeer.jalal@progos.org>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
class Progos_CustomOrderFlags_Block_Adminhtml_Sales_Order_View_Info_Block extends Mage_Core_Block_Template 
{

    protected $order;
    
    public function getOrder() {
        if (is_null($this->order)) {
            if (Mage::registry('current_order')) {
                $order = Mage::registry('current_order');
            }
            elseif (Mage::registry('order')) {
                $order = Mage::registry('order');
            }
            else {
                $order = new Varien_Object();
            }
            $this->order = $order;
        }
        return $this->order;
    }
    
    public function getOoStatusActionUrl(){
        return Mage::helper("adminhtml")->getUrl('*/sales_order/setOos', array('order_id'=>$this->getOrder()->getId()) );
    }
    
    public function getPrefferedCourierActionUrl(){
        return Mage::helper("adminhtml")->getUrl('*/sales_order/setPfCourier', array('order_id'=>$this->getOrder()->getId()) );
    }
    
    public function getCustomerFlagActionUrl(){
        return Mage::helper("adminhtml")->getUrl('*/sales_order/setCstFlag', array('order_id'=>$this->getOrder()->getId()) );
    }

    public function getUpsstatusFlagActionUrl(){
        return Mage::helper("adminhtml")->getUrl('*/sales_order/setUpsstatusFlag', array('order_id'=>$this->getOrder()->getId()) );
    }

    public function getDhlstatusFlagActionUrl(){
        return Mage::helper("adminhtml")->getUrl('*/sales_order/setDhlstatusFlag', array('order_id'=>$this->getOrder()->getId()) );
    }

    public function getAramexstatusFlagActionUrl(){
        return Mage::helper("adminhtml")->getUrl('*/sales_order/setAramexstatusFlag', array('order_id'=>$this->getOrder()->getId()) );
    }
}