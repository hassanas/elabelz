<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Model_Cron
{
    public function urlClean()
    {
        $helper = Mage::helper('safemage_urloptimization');
        if ($helper->isCronEnabled()) {
            Mage::getModel('safemage_urloptimization/clean')->process($helper->getClearLimit());
        }

        return $this;
    }
}
