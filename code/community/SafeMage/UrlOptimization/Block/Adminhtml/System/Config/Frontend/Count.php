<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Block_Adminhtml_System_Config_Frontend_Count extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        return $this->_getAddTotalCountHtml();
    }

    protected function _getAddTotalCountHtml() {
        $url = Mage::helper('adminhtml')->getUrl('adminhtml/urlrewrite/index');

        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $select = $connection->select()->from($resource->getTableName('core/url_rewrite'), array('COUNT(*)'));
        $count = $connection->fetchOne($select);

        return '<a href="'. $url .'" target="_blank">'. $count . '</a>';
    }
}
