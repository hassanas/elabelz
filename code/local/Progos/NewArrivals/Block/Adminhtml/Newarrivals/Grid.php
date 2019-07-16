<?php

class Progos_NewArrivals_Block_Adminhtml_Newarrivals_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("newarrivalsGrid");
				$this->setDefaultSort("id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
            $newthreshold = 30;
            $allCatIds = Mage::helper('newarrivals')->getAllCategoryIds();
            $newInCat = Mage::helper('newarrivals')->getOnNewProducts();
            $productCollection = Mage::getModel('catalog/product')->getCollection();
            if (Mage::getStoreConfig('catalog/newarrivals/onlyconfigurable')) {
                $productCollection->addAttributeToFilter('type_id', 'configurable');
            }
            $productCollection->addAttributeToSelect('name');

            $productCollection->addAttributeToSelect('created_at');
            $productCollection->addAttributeToSelect('news_from_date');
            $productCollection->addAttributeToSelect('news_to_date');
            $productCollection
                ->getSelect()
                ->group('entity_id');
            $productCollection->addAttributeToFilter('entity_id', array('nin' => $newInCat));
            if (Mage::getStoreConfig('catalog/newarrivals/statuschk')) {
                Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
            }
            $productCollection
                ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
                ->addAttributeToFilter('category_id', array('in' => $allCatIds));

            $productCollection->getSelect()->where('datediff(now(), created_at) < ?', $newthreshold);
            $productCollection->addPriceData(null, 1);




            $this->setCollection($productCollection);
            return parent::_prepareCollection();

		}
		protected function _prepareColumns()
		{
				$this->addColumn("id", array(
				"header" => Mage::helper("newarrivals")->__("ID"),
				"align" =>"right",
				"width" => "50px",
			    "type" => "number",
				"index" => "entity_id",
				));
                
				$this->addColumn("name", array(
				"header" => Mage::helper("newarrivals")->__("Name"),
				"index" => "name",
				));
				$this->addColumn("created_at", array(
				"header" => Mage::helper("newarrivals")->__("Created At"),
				"index" => "created_at",
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
            $this->addExportType('*/*/exportCsv', Mage::helper('newarrivals')->__('CSV'));
            $this->addExportType('*/*/exportExcel', Mage::helper('newarrivals')->__('Excel'));

				return parent::_prepareColumns();
		}

		public function getRowUrl($row)
		{
			   return '#';
		}



		

}