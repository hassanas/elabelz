<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Block_Adminhtml_Column_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'mageworx_ordersgrid';
        $this->_controller = 'adminhtml_column';
        $this->_mode = 'edit';

        $this->_removeButton('reset');
        $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getOrdersGridConfigUrl() . '\')');
        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Save'));

        $this->_addButton('save_and_continue', array(
            'label'     => Mage::helper('catalog')->__('Save and Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class' => 'save'
        ), 10);

        $confirmationMessage = Mage::helper('core')->jsQuoteEscape(
            Mage::helper('mageworx_ordersgrid')->__('Are you sure? The columns will be reset to the default values')
        );
        $this->_addButton('resetColumns', array(
            'label'     => Mage::helper('mageworx_ordersgrid')->__('Reset Columns'),
            'onclick'   => "confirmSetLocation('{$confirmationMessage}', '{$this->getResetColumnsUrl()}')",
            'class'     => 'save',
        ), 0);

        // set last tab
        $this->_formScripts[] = " function saveAndContinueEdit(){ editForm.submit($('edit_form').action + 'back/id/1/') } ";
    }

    public function getHeaderText()
    {
        return Mage::helper('mageworx_ordersgrid')->__('Manage Columns');
    }

    public function getResetColumnsUrl()
    {
        return $this->getUrl('*/*/resetColumns');
    }

    public function getOrdersGridConfigUrl()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/mageworx_ordersmanagement');
    }
}
