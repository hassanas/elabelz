<?php
class Progos_Ccache_Block_Adminhtml_Ccache_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
   public function __construct()
   {
       parent::__construct();
       $this->setId('id');
       $this->setDefaultSort('id');
       $this->setDefaultDir('DESC');
       $this->setSaveParametersInSession(true);
   }
   
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ccache/ccache')->getCollection();
        $collection->addFieldToFilter('count' , array('gteq' => 1));
        if(Mage::registry('ccache_key') == 'product') {
            $collection->addFieldToFilter('type' , 'product');
            $this->_addSku($collection);
        } elseif(Mage::registry('ccache_key') == 'category') {
            $collection->addFieldToFilter('type' , 'category');
            $this->_addCategory($collection);
        } elseif(Mage::registry('ccache_key') == 'manufacturer') {
            $collection->addFieldToFilter('type' , 'manufacturer');
            $this->_addManufacturer($collection);
            
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    protected function _addSku($collection)
    {
        $collection->getSelect()->joinLeft('catalog_product_entity', 'main_table.type_id = catalog_product_entity.entity_id',array('type_id as product_type','sku'));
        $collection->getSelect()->distinct(true);
    }
    protected function _addCategory($collection)
    {
        $attribute_id =  (Mage::getStoreConfig('ccache/settings/attribute_id') > 0 ? Mage::getStoreConfig('ccache/settings/attribute_id') : 41);
        $collection->getSelect()->joinLeft('catalog_category_entity_varchar as cv', 'main_table.type_id = cv.entity_id and cv.attribute_id = '. $attribute_id .' and cv.store_id = 0',array('value as category_name'));
        $collection->getSelect()->distinct(true);
    }
    
    protected function _addManufacturer($collection)
    {
        $collection->getSelect()->joinLeft('brand', 'main_table.type_id = brand.brand_id',array('name'));
        $collection->getSelect()->distinct(true);
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('id',
            array(
                'header' => Mage::helper('ccache')->__('ID'),
                'index' => 'id',
            ));
			   
        if(Mage::registry('ccache_key') == 'product') {
            $this->_prepareProductColumns();
        } elseif(Mage::registry('ccache_key') == 'category') {
            $this->_prepareCategoryColumns();
        } elseif(Mage::registry('ccache_key') == 'manufacturer') {
            $this->_prepareManufacturerColumns();
        }
	
        $this->addColumn('count', array(
            'header' => Mage::helper('ccache')->__('Count'),
            'index' => 'count',
        ));
        
        return parent::_prepareColumns();
    }
    
    protected function _prepareProductColumns()
    {
        $this->addColumn('type_id', array(
            'header' => Mage::helper('ccache')->__('Product Id'),
            'index' => 'type_id',
        ));
        $this->addColumn('sku', array(
            'header' => Mage::helper('partialindex')->__('SKU'),
            'index' => 'sku',
        ));
		
        $this->addColumn('product_type', array(
            'header' => Mage::helper('partialindex')->__('Product Type'),
            'index' => 'product_type',
        ));
        
        $this->addColumnAfter('cache',
            array(
                'header'    =>    Mage::helper('ccache')->__('Cache'),
                'width'        => '100',
                'type'        => 'action',
                'getter'    => 'getId',
                'actions'    => array(
                    array(
                        'caption'    => Mage::helper('ccache')->__('Clear Cache'),
                        'onclick'   => "CCache('{$this->getUrl('*/adminhtml_index/clearProduct', array('id' => '$type_id') )}', true)",
                        'field'        => 'id'
                    )),
                'filter'    => false,
                'sortable'    => false,
                'index'        => 'stores',
                'is_system'    => true,
        ), 'count');
    }
    
    protected function _prepareManufacturerColumns()
    {
        $this->addColumn('type_id', array(
            'header' => Mage::helper('ccache')->__('Manufacturer Id'),
            'index' => 'type_id',
        ));
        $this->addColumn('name', array(
            'header' => Mage::helper('ccache')->__('Manufacturer Name'),
            'index' => 'name',
        ));
        $this->addColumnAfter('cache',
            array(
                'header'    =>    Mage::helper('ccache')->__('Cache'),
                'width'        => '100',
                'type'        => 'action',
                'getter'    => 'getId',
                'actions'    => array(
                    array(
                        'caption'    => Mage::helper('ccache')->__('Clear Cache'),
                        'onclick'   => "CCache('{$this->getUrl('*/adminhtml_index/clearBrand', array('id' => '$type_id') )}', true)",
                        'field'        => 'id'
                    )),
                'filter'    => false,
                'sortable'    => false,
                'index'        => 'stores',
                'is_system'    => true,
        ), 'count');
    }
    
    protected function _prepareCategoryColumns()
    {
        $this->addColumn('type_id', array(
            'header' => Mage::helper('ccache')->__('Category Id'),
            'index' => 'type_id',
        ));
        $this->addColumn('category_name', array(
            'header' => Mage::helper('ccache')->__('Category Name'),
            'index' => 'category_name',
        ));
        
        $this->addColumnAfter('cache',
            array(
                'header'    =>    Mage::helper('ccache')->__('Cache'),
                'width'        => '100',
                'type'        => 'action',
                'getter'    => 'getId',
                'actions'    => array(
                    array(
                        'caption'    => Mage::helper('ccache')->__('Clear Cache'),
                        'onclick'   => "CCache('{$this->getUrl('*/adminhtml_index/clearCategory', array('id' => '$type_id') )}', true)",
                        'field'        => 'id'
                    )),
                'filter'    => false,
                'sortable'    => false,
                'index'        => 'stores',
                'is_system'    => true,
        ), 'count');
    }
    public function getRowUrl($row)
    {
         return null;
    }
}
