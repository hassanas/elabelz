<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Seo_Block_Adminhtml_Template_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct ()
    {
        parent::__construct();
        $this->_objectId = 'template_id';
        $this->_controller = 'adminhtml_template';
        $this->_blockGroup = 'seo';

        $this->_addButton('saveandcontinue', array(
            'label'   => Mage::helper('seo')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class'   => 'save',
        ), -100);

        if (!Mage::registry('current_template_model') || !Mage::registry('current_template_model')->getId()) {
            $this->_removeButton('save');
            $this->_removeButton('reset');
        }

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";

        return $this;
    }

    public function getHeaderText ()
    {
        if (Mage::registry('current_template_model') && Mage::registry('current_template_model')->getId()) {
            return Mage::helper('seo')->__("Edit SEO Template (ID: %s)", $this->htmlEscape(Mage::registry('current_template_model')->getId()));
        } else {
            return Mage::helper('seo')->__('Add New SEO Template');
        }
    }
}