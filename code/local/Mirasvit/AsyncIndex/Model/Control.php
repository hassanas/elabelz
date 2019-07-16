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
 * @package   Fast Asynchronous Re-indexing
 * @version   1.1.13
 * @build     434
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */



class Mirasvit_AsyncIndex_Model_Control
{
    const PROCESSING_MODE_PER_INDEX = 'index';
    const PROCESSING_MODE_PER_ENTITY = 'entity';

    /** @var  Mirasvit_AsyncIndex_Helper_Data */
    protected $helper;

    protected $lockFile;

    public function __construct()
    {
        $this->helper = Mage::helper('asyncindex');
    }

    public function run()
    {
        //load default adminhtml events (for observers)
        try {
            Mage::getConfig()->loadEventObservers('adminhtml');
            Mage::app()->addEventArea('adminhtml');
        } catch (Exception $e) {
        }

        // Mage::getSingleton('core/resource')->getConnection('core_write')->beginTransaction();

        if (!$this->isLocked()) {
            $this->lock();

            $this->helper->setVariable('start_time', Mage::getSingleton('core/date')->gmtTimestamp());

            // save mysql connection id
            $connectionId = Mage::getSingleton('core/resource')->getConnection('core_write')
                ->fetchOne('SELECT CONNECTION_ID()');
            $this->helper->setVariable('connection_id', $connectionId);

            if ($this->getMode() == self::PROCESSING_MODE_PER_INDEX) {
                /** @var Mirasvit_AsyncIndex_Model_Processing_PerIndex $mode */
                $mode = Mage::getModel('asyncindex/processing_perIndex');
            } else {
                /** @var Mirasvit_AsyncIndex_Model_Processing_PerEntity $mode */
                $mode = Mage::getModel('asyncindex/processing_perEntity');
            }

            $mode->setControl($this);

            if (Mage::getSingleton('asyncindex/config')->isFullReindexAllowed()) {
                $mode->fullReindex();
            }

            if (Mage::getSingleton('asyncindex/config')->isChangeReindexAllowed()) {
                $mode->reindexQueue();
            }

            $this->processValidation();
            $this->uninvalidateCache();

            $this->unlock();
        }

        Mage::getSingleton('core/resource')->getConnection('core_write')->commit();
    }

    public function processValidation()
    {
        $unprocessed = 0;

        /** @var Mage_Index_Model_Process $process */
        foreach (Mage::getModel('index/process')->getCollection() as $process) {
            $unprocessed += intval($process->getUnprocessedEventsCollection()->getSize());
        }
        if ($unprocessed != 0) {
            return $this;
        }

        if (Mage::getSingleton('asyncindex/config')->isProductValidationAllowed()) {
            $uid = $this->helper->start('Validation of Product Index');

            Mage::getModel('asyncindex/validator_productFlat')->validate();

            $this->helper->finish($uid);
        }

        if (Mage::getSingleton('asyncindex/config')->isCategoryValidationAllowed()) {
            $uid = $this->helper->start('Validation of Category Index');

            Mage::getModel('asyncindex/validator_categoryFlat')->validate();

            $this->helper->finish($uid);
        }

        return $this;
    }

    public function uninvalidateCache()
    {
        Mage::app()->getCacheInstance()->save(serialize(array()), Mage_Core_Model_Cache::INVALIDATED_TYPES);

        return $this;
    }

    public function getMode()
    {
        return Mage::getSingleton('asyncindex/config')->getProcessingMode();
    }

    /**
     * Возвращает файл лока
     *
     * @return resource
     */
    protected function _getLockFile()
    {
        if ($this->lockFile === null) {
            $varDir = Mage::getConfig()->getVarDir('locks');
            $file = $varDir . DS . 'asyncreindex.lock';

            if (is_file($file)) {
                $this->lockFile = fopen($file, 'w');
            } else {
                $this->lockFile = fopen($file, 'x');
            }
            fwrite($this->lockFile, date('r'));
        }

        return $this->lockFile;
    }

    /**
     * Лочим файл, любой другой php процесс может узнать
     * что файл залочен.
     * Если процесс упал, файл разлочиться
     *
     * @return object
     */
    public function lock()
    {
        flock($this->_getLockFile(), LOCK_EX | LOCK_NB);

        return $this;
    }

    /**
     * Lock and block process.
     * If new instance of the process will try validate locking state
     * script will wait until process will be unlocked
     */
    public function lockAndBlock()
    {
        flock($this->_getLockFile(), LOCK_EX);

        return $this;
    }

    /**
     * Разлочит файл
     *
     * @return object
     */
    public function unlock()
    {
        flock($this->_getLockFile(), LOCK_UN);

        return $this;
    }

    /**
     * Проверяет, залочен ли файл
     *
     * @return bool
     */
    public function isLocked()
    {
        $fp = $this->_getLockFile();
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            flock($fp, LOCK_UN);

            $collection = Mage::getModel('index/process')->getCollection();

            foreach ($collection as $index) {
                if ($index->isLocked()) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }


    public function __destruct()
    {
        if ($this->lockFile) {
            fclose($this->lockFile);
        }
    }
}