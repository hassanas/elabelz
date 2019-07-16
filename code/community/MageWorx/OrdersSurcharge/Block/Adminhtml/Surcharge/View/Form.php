<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_View_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Retrieve surcharge model object
     *
     * @return MageWorx_OrdersSurcharge_Model_Surcharge
     */
    public function getSurcharge()
    {
        return Mage::registry('surcharge');
    }

    /**
     * Retrieve source
     *
     * @return MageWorx_OrdersSurcharge_Model_Surcharge
     */
    public function getSource()
    {
        return $this->getSurcharge();
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'        => 'surcharge_view_form',
            'action'    => $this->getData('action'),
            'method'    => 'post',
            'enctype'   => 'multipart/form-data'
        ));

        $surcharge = Mage::registry('current_surcharge');

        if ($surcharge->getId()) {
            $form->addField('entity_id', 'hidden', array(
                'name' => 'entity_id',
            ));
            $form->setValues($surcharge->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}