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


class Mirasvit_Seo_Model_System_Cms_Observer extends Varien_Object
{
    public function savePage($observer)
    {
        $model = $observer->getEvent()->getPage();
        $request = $observer->getEvent()->getRequest();
        $data = $request->getPost();
        if($data['alternate_group']) {
            $model->setAlternateGroup($data['alternate_group']);
        }
        // pr($model->getData()); die;
        try {
            $model->save();
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            return;
        }
    }

    public function prepareForm($observer)
    {
        $model = Mage::registry('cms_page');
        $form = $observer->getForm();
        $fieldset = $form->addFieldset('seo_alternate_fieldset', array('legend'=>Mage::helper('cms')->__('Alternate Settings'),'class'=>'input-text'));
        $fieldset->addField('alternate_group', 'text', array(
            'name'      => 'alternate_group',
            'label'     => Mage::helper('cms')->__('Alternate group'),
            'title'     => Mage::helper('cms')->__('Alternate group'),
            'disabled'  => false,
            'value'     => $model->getAlternateGroup()
        ));

    }
}
