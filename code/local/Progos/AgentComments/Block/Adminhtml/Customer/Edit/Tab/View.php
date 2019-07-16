<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

class Progos_AgentComments_Block_Adminhtml_Customer_Edit_Tab_View
    extends Mage_Adminhtml_Block_Customer_Edit_Tab_View

{

    public function getRanking()
    {
        if (!$this->_customer) {
            $this->_customer = Mage::registry('current_customer');
        }
        $customer = $this->getCustomer();
        $val = $customer->getResource()
            ->getAttribute('classification')
            ->getFrontend()
            ->getValue($customer);
        if(strtolower($val)== 'no'){
            return Mage::helper('progos_agentcomments')->__('Not set');
        }
        return $val;
    }
}
