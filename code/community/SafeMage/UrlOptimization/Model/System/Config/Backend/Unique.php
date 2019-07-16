<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Model_System_Config_Backend_Unique extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        parent::_afterSave();
        if ($this->isValueChanged()) {
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection('core_write');
            $connection->update(
                $resource->getTableName('eav/attribute'),
                array('is_unique' => $this->getValue()),
                "`attribute_code` = 'url_key'"
            );
        }
        return $this;
    }
}
