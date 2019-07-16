<?php
class Progos_Xlanding_Model_Cron{	

    protected $_pageProductInstance = null;
    public function updatePageProducts(){
        $resource = Mage::getSingleton('core/resource');
        $write =$resource->getConnection('core_write');
        $table = $resource->getTableName('amlanding/page');
        $pages = Mage::getModel('amlanding/page')->getCollection();
        $pages->addFieldToFilter('updated','1');
        $data['updated'] =0;
        foreach ($pages as $key =>$page) {
            $page->load();
            $this->getPageProductInstance()->savePageRelation($page);
            try {
                $query = "UPDATE {$table} SET updated = 0 WHERE page_id = ". (int)$page->getId();
                $write->query($query);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }
    public function getPageProductInstance()
    {
        if (!$this->_pageProductInstance) {
            $this->_pageProductInstance = Mage::getSingleton('xlanding/product');
        }
        return $this->_pageProductInstance;
    }
}