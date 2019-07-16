<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Block_Adminhtml_System_Config_Frontend_Log_Url extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $element->setScope(false);
        return $this->_getAddTotalCountHtml();
    }

    protected function _getAddTotalCountHtml() {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $select = $connection->select()->from($resource->getTableName('log/url_table'), array('COUNT(*)'));
        $count = $connection->fetchOne($select);

        if (!$count) {
            return '<strong style="color: green">0</strong>';
        }

        if ($count < 300000) {
            $count = '<strong style="color: green">' . $count . '</strong>';
        } elseif ($count < 1000000) {
            $count = '<strong style="color: orange">' . $count . '</strong>';
        } else {
            $count = '<strong style="color: red">' . $count . '</strong>';
        }

        $select = $connection->select()->from($resource->getTableName('log/url_table'), array('MIN(visit_time)'));
        $lastDate = $connection->fetchOne($select);
        if ($lastDate) {
            $lastDate = substr($lastDate, 0, 10);
            $cleanDate = Mage::getSingleton('core/date')->gmtDate(
                'Y-m-d',
                time() - ((Mage::getStoreConfig(Mage_Log_Model_Log::XML_LOG_CLEAN_DAYS) + 15) * 86400)
            );
            if ($lastDate < $cleanDate) {
                $lastDate = '<strong style="color: red">' . $lastDate . '</strong>';
            } else {
                $lastDate = '<strong style="color: green">' . $lastDate . '</strong>';
            }
        }

        return $count . ' / ' . $lastDate;
    }
}
