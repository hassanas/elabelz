<?php

class Progos_Partialindex_Model_Website 
{

    public function updateWebsiteDate()
    {
        $this->_prepareWebsiteDateTable();
    }

    /**
     * Retrieve website current dates table name
     *
     * @return string
     */
    protected function _getWebsiteDateTable()
    {
        return Mage::getSingleton('core/resource')->getTableName('catalog/product_index_website');
    }

    /**
     * Prepare website current dates table
     *
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Price
     */
    protected function _prepareWebsiteDateTable()
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $baseCurrency = Mage::app()->getBaseCurrencyCode();

        $select = $write->select()
            ->from(
                array('cw' => Mage::getSingleton('core/resource')->getTableName('core/website')),
                array('website_id'))
            ->join(
                array('csg' => Mage::getSingleton('core/resource')->getTableName('core/store_group')),
                'cw.default_group_id = csg.group_id',
                array('store_id' => 'default_store_id'))
            ->where('cw.website_id != 0');


        $data = array();
        foreach ($write->fetchAll($select) as $item) {
            /** @var $website Mage_Core_Model_Website */
            $website = Mage::app()->getWebsite($item['website_id']);

            if ($website->getBaseCurrencyCode() != $baseCurrency) {
                $rate = Mage::getModel('directory/currency')
                    ->load($baseCurrency)
                    ->getRate($website->getBaseCurrencyCode());
                if (!$rate) {
                    $rate = 1;
                }
            } else {
                $rate = 1;
            }

            /** @var $store Mage_Core_Model_Store */
            $store = Mage::app()->getStore($item['store_id']);
            if ($store) {
                $timestamp = Mage::app()->getLocale()->storeTimeStamp($store);
                $data[] = array(
                    'website_id' => $website->getId(),
                    'website_date'       => Varien_Date::formatDate($timestamp, false),
                    'rate'       => $rate
                );
            }
        }
        
        $table = $this->_getWebsiteDateTable();
        
        $write->beginTransaction();
        
        try {
            $write->delete($table);

            if ($data) {
                $write->insertMultiple($table, $data);
            }
            $write->commit();
        } catch (Exception $e) {
            $write->rollBack();
            throw $e;
        }

        return $this;
    }
    
}