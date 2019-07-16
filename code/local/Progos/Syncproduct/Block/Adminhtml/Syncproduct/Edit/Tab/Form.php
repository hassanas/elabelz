<?php

class Progos_Syncproduct_Block_Adminhtml_Syncproduct_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('syncproduct_form', array('legend' => Mage::helper('progos_syncproduct')->__('Sync Product by SKU')));

        $useCsv = $fieldset->addField('yesno', 'select', array(
            'name' => 'yesno',
            'label' => $this->__('Use CSV'),
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')
                ->toOptionArray(),
        ));

        $csvFile = $fieldset->addField('filename', 'file', array(
            'label' => Mage::helper('progos_syncproduct')->__('SKU CSV'),
            'required' => false,
            'name' => 'filename',
        ));

        $skuField = $fieldset->addField('sku', 'editor', array(
            'name' => 'sku',
            'label' => Mage::helper('progos_syncproduct')->__('SKU'),
            'title' => Mage::helper('progos_syncproduct')->__('SKU'),
            'style' => 'width:700px; height:500px;',
            'wysiwyg' => false,
            'required' => true,
            'note' => 'List one or more comma separated SKU'
        ));

        $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
            ->addFieldMap($useCsv->getHtmlId(), $useCsv->getName())
            ->addFieldMap($csvFile->getHtmlId(), $csvFile->getName())
            ->addFieldMap($skuField->getHtmlId(), $skuField->getName())
            ->addFieldDependence(
                $skuField->getName(),
                $useCsv->getName(),
                '0'
            )
            ->addFieldDependence(
                $csvFile->getName(),
                $useCsv->getName(),
                '1'
            )
        );
        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('progos_syncproduct')->__('Status'),
            'name' => 'status',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('progos_syncproduct')->__('Ready For Cron'),
                ),

                array(
                    'value' => 2,
                    'label' => Mage::helper('progos_syncproduct')->__('Disabled'),
                ),
            ),
        ));


        if (Mage::getSingleton('adminhtml/session')->getSyncproductData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getSyncproductData());
            Mage::getSingleton('adminhtml/session')->setSyncproductData(null);
        } elseif (Mage::registry('syncproduct_data')) {
            $form->setValues(Mage::registry('syncproduct_data')->getData());
        }
        return parent::_prepareForm();
    }
}