<?php
class Progos_Partialindex_Block_Adminhtml_Partialindex_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
   protected function _prepareForm()
   {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('partialindex_form', array('legend'=>Mage::helper('partialindex')->__('Product information')));
        $fieldset->addField('product_id', 'text',
                array(
                  'label' => Mage::helper('partialindex')->__('Product Id'),
                  'class' => 'required-entry',
                  'required' => true,
                  'name' => 'product_id',
            ));
        if (Mage::registry('partialindex_data'))
        {
            $form->setValues(Mage::registry('partialindex_data')->getData());
        }
        return parent::_prepareForm();
    }
}
