<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */
class Amasty_Audit_Model_Log extends Mage_Core_Model_Abstract
{
    public function _construct()
    {    
        $this->_init('amaudit/log', 'entity_id');
    }

    public function clearLog($fromObserver = true)
    {
        $days = Mage::getStoreConfig('amaudit/log/delete_logs_afret_days');

        if ($days > 0) {
            $tableLogName = Mage::getSingleton('core/resource')->getTableName('amaudit/log');
            $query = "DELETE FROM `$tableLogName`";

            if ($fromObserver) {
                $query = $query . 'WHERE date_time < NOW() - INTERVAL ' . $days . ' DAY';
            }

            Mage::getSingleton('core/resource')
                ->getConnection('core_write')
                ->query($query)
            ;
        }
    }

    public function addClearToLog($logType)
    {
        $data = array();
        /** @var Amasty_Audit_Helper_Data $helper */
        $helper = Mage::helper('amaudit');

        $username = Mage::getSingleton('admin/session')->getUser() ? Mage::getSingleton('admin/session')->getUser()->getUsername() : '';
        $data['date_time'] = Mage::getModel('core/date')->gmtDate();
        $data['username'] = $username;
        $data['type'] = 'Delete';
        $data['category'] = "cleared/log";
        $data['category_name'] = $helper->__("Cleared Log");
        $data['parametr_name'] = 'index';
        $data['info'] = $helper->__("Cleared Log") . ' ' . "'$logType'";
        $this->setData($data);

        $this->save();
    }
}
