<?php
/**
 * Created by PhpStorm.
 * User: adnan
 * Date: 3/22/17
 * Time: 12:46 PM
 */ 
class Progos_Magidev_Block_Adminhtml_Catalog_Category_Edit_Form extends Mage_Adminhtml_Block_Catalog_Category_Edit_Form {
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $category = $this->getCategory();
        $categoryId = (int) $category->getId();
        if ($categoryId) {
            $this->addAdditionalButton('autorun_btn',
                array(
                    'name' => 'autosort_btn',
                    'title' => 'Run Autosort for this Category',
                    'type' => "button",
                    'label' => Mage::helper('catalog')->__('Run Autosort'),
                    'onclick' => "autosort('" . $this->getUrl('*/sortproduct/autosort', array('_current' => true)) . "', true, {$categoryId})"
                )
            );
        }
        
        if (!in_array($categoryId, $this->getRootIds()) && $category->isDeleteable()) {
            $params = array('_current'=>true);
            $this->addAdditionalButton('clear_category_cache', array(
                        'label' => Mage::helper('catalog')->__('Clear Cache'),
                        'class' => 'add',
                        'onclick'   => "clearCategoryCache('{$this->getUrl('*/fpc/category', array('id' => $categoryId) )}', true, {$categoryId})",
        ));
        }
        return parent::_prepareLayout();
    }
}