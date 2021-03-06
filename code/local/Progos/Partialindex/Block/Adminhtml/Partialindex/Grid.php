<?php
class Progos_Partialindex_Block_Adminhtml_Partialindex_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
   public function __construct()
   {
       parent::__construct();
       $this->setId('product_id');
       $this->setDefaultSort('product_id');
       $this->setDefaultDir('DESC');
       $this->setSaveParametersInSession(true);
   }
   
   protected function _prepareCollection()
   {
      $collection = Mage::getModel('partialindex/product_index')->getCollection()->addFieldToSelect(array('product_id', 'sort_order'));
      $collection->getSelect()->joinLeft('catalog_product_entity', 'main_table.product_id = catalog_product_entity.entity_id',array('type_id','sku'));
      $collection->getSelect()->distinct(true);
      $this->setCollection($collection);
      return parent::_prepareCollection();
    }
	
   protected function _prepareColumns()
   {
        $this->addColumn('product_id',
             array(
                    'header' => Mage::helper('partialindex')->__('Product ID'),
                    'index' => 'product_id',
               ));
			   
        $this->addColumn('sku', array(
            'header' => Mage::helper('partialindex')->__('SKU'),
            'index' => 'sku',
        ));
		
       $this->addColumn('type_id', array(
            'header' => Mage::helper('partialindex')->__('Product Type'),
            'index' => 'type_id',
        ));
        $this->addColumn('sort_order', array(
            'header' => Mage::helper('partialindex')->__('Sort Order'),
            'index' => 'sort_order',
        ));
		
         return parent::_prepareColumns();
    }
	
    public function getRowUrl($row)
    {
         return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
