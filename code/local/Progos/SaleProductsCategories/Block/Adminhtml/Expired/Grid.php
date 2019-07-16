<?php

class Progos_SaleProductsCategories_Block_Adminhtml_Expired_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId("saleExpiredGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $allCatIds = Mage::helper('saleproductscategories')->getOnSaleCategories();
        $productCollection = Mage::getModel('catalog/product')->getCollection();
        $productCollection->addAttributeToFilter('type_id', 'configurable');
        $productCollection->addAttributeToSelect('name');
        $productCollection->addAttributeToSelect('price');
        $productCollection->addAttributeToSelect('final_price');
        $productCollection
            ->getSelect()
            ->group('entity_id');
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        $productCollection
            ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
            ->addAttributeToFilter('category_id', array('in' => $allCatIds));
        $productCollection->addPriceData(null, 1);
        $productCollection->getSelect()->where('price_index.final_price >= price_index.price');

        $this->setCollection($productCollection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn("id", array(
            "header" => Mage::helper("saleproductscategories")->__("ID"),
            "align" => "right",
            "width" => "50px",
            "type" => "number",
            "index" => "entity_id",
        ));

        $this->addColumn("name", array(
            "header" => Mage::helper("saleproductscategories")->__("Name"),
            "index" => "name",
        ));
        $this->addColumn("price", array(
            "header" => Mage::helper("saleproductscategories")->__("Price"),
            "index" => "price",
        ));
        $this->addColumn("final_price", array(
            "header" => Mage::helper("saleproductscategories")->__("Final Price"),
            "index" => "final_price",
        ));
        $this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('View'),
                        'url'     => array(
                            'base'=>'adminhtml/catalog_product/edit'
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
            ));
        $this->addExportType('*/*/expiredCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/expiredExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }


}