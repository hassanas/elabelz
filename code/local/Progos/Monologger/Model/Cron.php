<?php

class Progos_Monologger_Model_Cron
{
    public function removeLogs()
    {
        if (Mage::getStoreConfig('dev/monologger/cleanLog')) {
            $days = Mage::getStoreConfig('dev/monologger/clean_after_day');
            $cleanTime = $days * 60 * 60 * 24;
            $cleanTime = date('Y-m-d H:m:s', time() - $cleanTime);
            $thrashhold = strtotime($cleanTime);;
            $collection = Mage::getModel("monologger/monologger")->getCollection();
            $collection->addFieldToFilter('time', array('lt' => $thrashhold));
            foreach ($collection as $logitem) {
                $logitem->delete();
            }
        }
    }

}
	 