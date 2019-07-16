<?php
class Progos_Ccache_Block_Adminhtml_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $collection = Mage::getModel('ccache/warmup_products')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    
    protected function _prepareColumns()
    {
        $this->addColumn('id',
            array(
                'header' => Mage::helper('ccache')->__('ID'),
                'index' => 'id',
            ));
	$this->addColumn('product_id', array(
            'header' => Mage::helper('ccache')->__('Product Id'),
            'index' => 'product_id',
        ));
        
        return parent::_prepareColumns();
    }
    
    
    public function getRowUrl($row)
    {
         return null;
    }
}
