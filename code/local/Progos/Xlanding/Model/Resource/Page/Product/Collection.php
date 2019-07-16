<?php

class Progos_Xlanding_Model_Resource_Page_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{

    protected $_joinedFields = false;

    public function joinFields()
    {
        if (!$this->_joinedFields) {
            $this->getSelect()->join(
                array('related' => $this->getTable('xlanding/page_product')),
                'related.product_id = e.entity_id',
                array('position')
            );
            $this->_joinedFields = true;
        }
        return $this;
    }


    public function addPageFilter($page)
    {
        if ($page instanceof Amasty_Xlanding_Model_Page) {
            $page = $page->getId();
        }
        if (!$this->_joinedFields ) {
            $this->joinFields();
        }
        $this->getSelect()->where('related.page_id = ?', $page);
        return $this;
    }
}
