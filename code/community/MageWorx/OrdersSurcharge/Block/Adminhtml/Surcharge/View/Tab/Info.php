<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_View_Tab_Info
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Initialize block
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Initialize form
     *
     * @return Mage_Adminhtml_Block_Customer_Edit_Tab_Account
     */
    public function initForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('_info');
        $form->setFieldNameSuffix('info');

        $surcharge = $this->getSurcharge();

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('mageworx_orderssurcharge')->__('Surcharge Information')
        ));

        $fieldset->addField('entity_id', 'hidden', array(
            'required' => false,
            'name' => 'entity_id',
        ));

        $fieldset->addField('store_id', 'hidden', array(
            'required' => false,
            'name' => 'store_id',
        ));

        $form->setValues($surcharge->getData());
        $this->setForm($form);
        return $this;
    }

    /**
     * Retrieve surcharge model instance
     *
     * @return MageWorx_OrdersSurcharge_Model_Surcharge
     */
    public function getSurcharge()
    {
        return Mage::registry('current_surcharge');
    }

    /**
     * Retrieve source model instance
     *
     * @return MageWorx_OrdersSurcharge_Model_Surcharge
     */
    public function getSource()
    {
        return $this->getSurcharge();
    }

    public function getViewUrl($surchargeId)
    {
        return $this->getUrl('*/*/*', array('surcharge_id' => $surchargeId));
    }

    /////////////////////////// Tab interface methods ///////////////////////////

    public function getTabLabel()
    {
        return Mage::helper('mageworx_orderssurcharge')->__('Information');
    }

    public function getTabTitle()
    {
        return Mage::helper('mageworx_orderssurcharge')->__('Surcharge Information');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}