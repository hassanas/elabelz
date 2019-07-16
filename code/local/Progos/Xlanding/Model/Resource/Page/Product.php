<?php

class Progos_Xlanding_Model_Resource_Page_Product extends Mage_Core_Model_Resource_Db_Abstract
{
    
    protected function  _construct()
    {
        $this->_init('xlanding/page_product', 'rel_id');
    }
    
    public function savePageRelation($page, $newProducts)
    {
        $pageId=$page->getId();
        $oldProducts = $this->lookupProductIds($pageId);
        
        if (empty($newProducts)) {
            $newProducts = [];
        }
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('xlanding/page_product'), 'IFNULL(max(position),0)+1')
            ->where('page_id = ?', (int)$pageId);
        $max = $adapter->fetchCol($select)[0];

        $table  = $this->getMainTable(); // $this->getTable('amlanding/page_product');
        $insert = array_diff($newProducts, $oldProducts);
        $delete = array_diff($oldProducts, $newProducts);
        if ($delete) {
            $where = array(
                'page_id = ?' => (int) $page->getId(),
                'product_id IN (?)' => $delete
            );
            $this->_getWriteAdapter()->delete($table, $where);
        }
        if ($insert) {
            $order = Mage::getStoreConfig('amlanding/xlanding/show_new_frontend');
            if ($order==1) {
                sort($insert);
            } else {
                rsort($insert);
            }

            $data = array();
            foreach ($insert as $position =>  $product) {
                $data[] = array(
                    'page_id'  => (int) $page->getId(),
                    'product_id' => (int) $product,
                    'position'  =>@$max
                );
                $max= $max+1;
            }
            $this->_getWriteAdapter()->insertMultiple($table, $data);
        }
        return $this;
    }

    public function lookupProductIds($pageId)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('xlanding/page_product'), 'product_id')
            ->where('page_id = ?', (int)$pageId);
        return $adapter->fetchCol($select);
    }
}
