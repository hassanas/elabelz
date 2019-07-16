<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */
class Amasty_Audit_Model_Data extends Mage_Core_Model_Abstract
{
    const SUCCESS = 1;
    const UNSUCCESS = 0;
    const LOCKED = 2;
    const LOGOUT = 3;
    const DISABLED = 4;
    const AUTOLOGOUT = 5;
    const MIN_UNSUCCESSFUL_COUNT = 5;
    const MIN_ALL_COUNT = 5;
    const WEEK = 604800;

    public function _construct()
    {
        $this->_init('amaudit/data', 'entity_id');
    }

    /**
     * If email did not send - return count of unsuccessful login for last hour .
     * @return int
     */
    public function getUnsuccessfulCount()
    {
        $latestSending = Mage::getStoreConfig('amaudit/unsuccessful_log_mailing/latest_sending');
        $duration = 3600;
        $time = Mage::getModel('core/date')->gmtDate();
        $intTime = strtotime($time);
        $count = 0;
        if (($intTime - $latestSending) > $duration) {
            $unsuccessfulDataCollection = Mage::getModel('amaudit/data')->getCollection();
            $lastHour = $intTime - $duration;
            $fromHour = date('Y-m-d H:i:s', $lastHour);
            $unsuccessfulDataCollection
                ->addFieldToFilter('date_time', array('from' => $fromHour, 'to' => $time))
                ->addFieldToFilter('status', Amasty_Audit_Model_Data::UNSUCCESS);

            $count = $unsuccessfulDataCollection->count();
        }

        if ($count >= Amasty_Audit_Model_Data::MIN_UNSUCCESSFUL_COUNT) {
            Mage::getConfig()->saveConfig('amaudit/unsuccessful_log_mailing/latest_sending', $intTime);
            Mage::getConfig()->cleanCache();
        }

        return $count;
    }

    public function isSuspicious($userData)
    {
        $time = Mage::getModel('core/date')->gmtDate();
        $intTime = strtotime($time);
        $intlastTime = $intTime - Amasty_Audit_Model_Data::WEEK;
        $lastTime = date('Y-m-d H:i:s', $intlastTime);
        $allCollection = Mage::getModel('amaudit/data')->getCollection()
            ->addFieldToFilter('date_time', array('from' => $lastTime, 'to' => $time))
            ->addFieldToFilter('status', 1);
        $allCount = $allCollection->count();
        $allCollection->clear();
        $currentUserCollection = $allCollection
            ->addFieldToFilter('country_id', substr($userData['country_id'], 0, 3));
        $currentUserCollection->getSelectCountSql();
        $currentUserCount = $currentUserCollection->count();

        if (($allCount >= Amasty_Audit_Model_Data::MIN_ALL_COUNT) && ($currentUserCount == 0)) {
            return true;
        }

        return false;
    }

    /**
     * save to data userdata with status = LOGOUT
     * @param $userData
     * @throws Exception
     */
    public function saveLogoutData($userData)
    {
        $userData = $this->_getUserData($userData);
        $userData['status'] = Amasty_Audit_Model_Data::LOGOUT;
        $this->setData($userData);
        $this->save();
    }

    /**
     * save to data userdata with status = AUTOLOGOUT
     * @param $userData
     * @throws Exception
     */
    public function saveAutoLogoutData($userData)
    {
        $userData = $this->_getUserData($userData);
        $userData['status'] = Amasty_Audit_Model_Data::AUTOLOGOUT;
        $userData['date_time'] = Mage::getModel('core/date')->gmtDate();
        $this->setData($userData);
        $this->save();
    }

    /**
     * get user name, ip, location, country_id
     * @param $userData
     * @return mixed
     */
    protected function _getUserData($userData)
    {
        $user = Mage::getModel('admin/user')->loadByUsername($userData['username']);
        $userData['name']  = $user->getFirstname() . ' ' . $user->getLastname();

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $userData['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $userData['ip'] = $_SERVER['REMOTE_ADDR'];
        }

        if (Mage::helper('core')->isModuleEnabled('Amasty_Geoip')
            && (Mage::getStoreConfig('amaudit/geoip/use') == 1)
            && !is_null($userData['ip'])
        ) {
            $geolocationModel = Mage::getSingleton('amaudit/geolocation');
            $location = $geolocationModel->getLocation($userData['ip']);
            $userData['location']   = $location['locationString'];
            $userData['country_id'] = $location['countryLabel'];
        }

        return $userData;
    }

    public function deleteLoginAttemptsLog()
    {
        $tableLoginAttemptsName = Mage::getSingleton('core/resource')->getTableName('amaudit/data');
        $days = Mage::getStoreConfig('amaudit/log/delete_login_attempts_after_days');

        if ($days > 0) {
            $query = "DELETE FROM `$tableLoginAttemptsName`";

            $query = $query . 'WHERE date_time < NOW() - INTERVAL ' . $days . ' DAY';

            Mage::getSingleton('core/resource')
                ->getConnection('core_write')
                ->query($query)
            ;
        }
    }

    /**
     * Processing data before model save
     * @return Amasty_Audit_Model_Data
     */
    public function _beforeSave()
    {
        $newLoginAdmin = array(
            'username' => $this->getUsername(),
            'ip'       => $this->getIp(),
            'status'   => $this->getStatus()
        );
        /*
         * Check new username, ip, status with username, ip, status in table
         * if identical get new date and count++
         * add this in admin in table
         * */
        if ($newLoginAdmin['status'] == Amasty_Audit_Model_Data::UNSUCCESS) {
            $lastLoginAdmin = Mage::getModel('amaudit/data')
                                    ->getCollection()
                                    ->setOrder('entity_id', 'ASC')
                                    ->getLastItem();

            $count = $lastLoginAdmin->getCountEntry();
            if ($lastLoginAdmin->getUsername()  == $newLoginAdmin['username']
                && $lastLoginAdmin->getIp()     == $newLoginAdmin['ip']
                && $lastLoginAdmin->getStatus() == $newLoginAdmin['status']
            ) {
                $count++;
                $date = Mage::getModel('core/date')->gmtDate();
                $data = array(
                    'count_entry' => $count,
                    'date_time'   => $date
                );
                $lastLoginAdminId = $lastLoginAdmin->getId();
                $this->load($lastLoginAdminId)
                        ->addData($data);
            }
        }

        return parent::_beforeSave();
    }
}