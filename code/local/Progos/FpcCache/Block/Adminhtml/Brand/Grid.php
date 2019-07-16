<?php

class Progos_FpcCache_Block_Adminhtml_Brand_Grid extends Magestore_Shopbybrand_Block_Adminhtml_Brand_Grid
{
    
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->addColumn('cache',
            array(
                'header'    =>    Mage::helper('shopbybrand')->__('Cache'),
                'width'        => '100',
                'type'        => 'action',
                'getter'    => 'getId',
                'actions'    => array(
                    array(
                        'caption'    => Mage::helper('shopbybrand')->__('Clear Cache'),
                        'onclick'   => "clearBrandCache('{$this->getUrl('*/fpc/brand', array('id' => '$brand_id') )}', true)",
                        'field'        => 'id'
                    )),
                'filter'    => false,
                'sortable'    => false,
                'index'        => 'stores',
                'is_system'    => true,
        ));
        
    }
}