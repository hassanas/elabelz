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
 * @package   Advanced Product Feeds
 * @version   1.1.10
 * @build     739
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */



/**
 * @method int getSize() - returns size of currently iterated collection
 */
class Mirasvit_FeedExport_Model_Feed_Generator_Action_Iterator extends Mirasvit_FeedExport_Model_Feed_Generator_Action
{
    /**
     * Get model for current iterator.
     *
     * @return Mirasvit_FeedExport_Model_Feed_Generator_Action_Iterator_Entity|Mirasvit_FeedExport_Model_Feed_Generator_Action_Iterator_Rule
     *
     * @throws Mage_Core_Exception
     */
    public function getIteratorModel()
    {
        $iteratorModel = null;
        switch ($this->getType()) {
            case 'rule':
                $iteratorModel = Mage::getModel('feedexport/feed_generator_action_iterator_rule');
                break;

            case 'product':
            case 'category':
            case 'review':
                $iteratorModel = Mage::getModel('feedexport/feed_generator_action_iterator_entity');
                break;

            default:
                Mage::throwException(sprintf('Undefined iterator type %s', $this->getType()));
                break;
        }

        $iteratorModel->setData($this->getData())->setFeed($this->getFeed());

        return $iteratorModel;
    }

    public function process()
    {
        $iteratorModel = $this->getIteratorModel();
        if ($iteratorModel->init() === false) {
            $this->finish();

            return;
        }

        $collection = $iteratorModel->getCollection();
        // Set size of current collection
        $this->setData('size', $collection->getConnection()->fetchOne($collection->getSelectCountSql()));
        $idx = intval($this->getValue('idx'));
        $add = intval($this->getValue('add'));
        $limit = $this->getLimit($this->getSize());

        if ($idx == 0) {
            $this->start();
            $iteratorModel->start();
        }

        $result = array();
        $rows = $this->fetchCollection($iteratorModel->getCollection(), $limit, $idx);
        Varien_Profiler::start('feedexport:iterator_'.$this->getType().':process');
        for ($counter = 1, $rowSize = count($rows); $counter <= $rowSize; ++$counter) {
            $isTimeout = Mage::helper('feedexport')->getState()->isTimeout();

            Varien_Profiler::start('feedexport:iterator:callback');
            $callbackResult = $iteratorModel->callback($rows[$counter - 1]);
            Varien_Profiler::stop('feedexport:iterator:callback');

            if ($callbackResult !== null) {
                $result[] = $callbackResult;
                ++$add;
            }

            if ($counter >= $limit || $counter >= $rowSize || $isTimeout) {
                $this->setValue('idx', $idx + $counter)
                    ->setValue('size', $this->getSize())
                    ->setValue('add', $add);
            }

            if ($isTimeout) {
                break;
            }
        }

        $iteratorModel->save($result);
        Varien_Profiler::stop('feedexport:iterator_'.$this->getType().':process');

        if ($idx >= $this->getSize()) {
            $iteratorModel->finish();
            $this->finish();
            $this->setIteratorType($this->getKey());
        }
    }

    /**
     * Fetch collection according to limit and idx defined in state file.
     *
     * @param $collection
     * @param $limit
     * @param $idx
     *
     * @return array
     */
    protected function fetchCollection($collection, $limit, $idx)
    {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');

        $collection->getSelect()->limit($limit, $idx);

        if ($this->getFeed()->getGenerator()->getMode() == 'test') {
            if ($ids = Mage::app()->getRequest()->getParam('ids')) {
                $ids = explode(',', $ids);
                if ($this->getType() == 'review') {
                    $collection->addFieldToFilter('main_table.review_id', $ids);
                } else {
                    $collection->addFieldToFilter('entity_id', $ids);
                }
            } else {
                $collection->getSelect()
                    ->order(new Zend_Db_Expr('RAND()'))
                    ->limit(100);
            }
            // Change size of current collection
            if ($this->getSize() > $collection->count()) {
                $this->setData('size', $collection->count() - 1);
            }
        }

        $stmt = $connection->query($collection->getSelect());

        return $stmt->fetchAll();
    }

    /**
     * Get limit number for size of products per iteration.
     *
     * @param int $size
     *
     * @return int
     */
    private function getLimit($size)
    {
        $limit = Mage::getSingleton('feedexport/config')->getPageSize();
        if (!$limit) {
            $limit = intval($size / 100);
            if ($limit < 100) {
                $limit = 100;
            }
        }

        return $limit;
    }
}
