<?php
/* @author: Humera Batool
 * @created at : 20 october 2017
 *
 * */
class Progos_Storecredit_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {
    public function  __construct() {

        parent::__construct();

        $this->_addButton('store_credit', array(
            'label'     => Mage::helper('aw_storecredit')->__('Add Storecredit'),
            'onclick'   => 'javascript:openStoreCreditPopupForm()',
            'class'     => 'go'
        ), 0, 100, 'header', 'header');
    }
}