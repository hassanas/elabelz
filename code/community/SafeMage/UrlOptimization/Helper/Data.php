<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CLEAR_PROCESS_WORKING = 1;
    const CLEAR_PROCESS_DONE = 2;

    public function checkDisabledUrl()
    {
        return Mage::getStoreConfigFlag('safemage_urloptimization/general/disabled_url_check');
    }

    public function getClearMode()
    {
        return (int) Mage::getStoreConfig('safemage_urloptimization/clearing_settings/clear_mode');
    }

    public function getKeepRedirectQty()
    {
        return (int) Mage::getStoreConfig('safemage_urloptimization/clearing_settings/keep_redirect_qty');
    }

    public function removeOnlyWithDigit()
    {
        return Mage::getStoreConfigFlag('safemage_urloptimization/clearing_settings/remove_only_with_digit');
    }

    public function getClearLimit()
    {
        return (int) Mage::getStoreConfig('safemage_urloptimization/clearing_settings/clear_limit');
    }

    public function isCronEnabled()
    {
        return Mage::getStoreConfigFlag('safemage_urloptimization/clearing_settings/cron_enabled');
    }
}
