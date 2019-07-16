<?php
class Progos_Sizeguide_Block_Adminhtml_Sizeguide_Grid extends Mage_Adminhtml_Block_Widget_Grid
{	
    public function __construct()
    {
		
        parent::__construct();

		// Set some defaults for our grid
        $this->setId('sizeguideGrid');
        $this->setDefaultSort('sizeguide_id');
		$this->setDefaultDir('ASC');	
		$this->setSaveParametersInSession(true);	
    }
 

    protected function _prepareCollection(){
        $collection = Mage::getModel('sizeguide/sizeguide')->getCollection();
        foreach($collection as $link){
            if($link->getStoreId() && $link->getStoreId() != 0 ){
                $link->setStoreId(explode(',',$link->getStoreId()));
            }
            else{
                $link->setStoreId(array('0'));
            }

            if( $link->getCategories() ){
                $link->setCategories(explode('|',$link->getCategories()));
            }

            if($link->getBrandIds() ){
                $link->setBrandIds(explode(',',$link->getBrandIds()));
            }
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }


 
    protected function _prepareColumns()
    {   
        $helper = Mage::helper('sizeguide/sizeguide');
        $categories = $helper->getCategoriesGridCollection();
        $brands = $helper->getBrandGridCollection();

		// Add the columns that should appear in the grid
        $this->addColumn('title', array(
            'header' => Mage::helper('sizeguide')->__('Title'),	//'header'=> $this->__('ID'),
            'sortable' => false,
            'width' => '40',
            'filter' => false,
            'index' => 'title'
        ));

        $this->addColumn('name', array(
            'header' => Mage::helper('sizeguide')->__('Name'), //'header'=> $this->__('ID'),
            'sortable' => false,
            'width' => '40',
            'filter' => false,
            'index' => 'name'
        ));

        $this->addColumn('categories', array(
            'header' => Mage::helper('sizeguide')->__('Categories'), //'header'=> $this->__('ID'),
            'sortable' => false,
            'width' => '40',
            'filter' => false,
            'index' => 'categories',
            'type'  => 'options',
            'options'=>$categories,
        ));

        $this->addColumn('brand_ids', array(
            'header' => Mage::helper('sizeguide')->__('Brands'), //'header'=> $this->__('ID'),
            'sortable' => false,
            'width' => '40',
            'filter' => false,
            'index' => 'brand_ids',
            'type'  => 'options',
            'options'=>$brands,
        ));


        $this->addColumn('sizeguide_file', array(
            'header' => Mage::helper('sizeguide')->__('File'), //'header'=> $this->__('ID'),
            'sortable' => false,
            'width' => '40',
            'filter' => false,
            'index' => 'sizeguide_file'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'        => Mage::helper('sizeguide')->__('Store View'),
                'index'         => 'store_id',
                'type'          => 'store',
                'store_all'     => true,
                'store_view'    => true,
                'sortable'      => false,
                'width' => '40',
                'filter' => false,
                'filter_condition_callback' => array($this,
                    '_filterStoreCondition'),
            ));
        }

        $this->addColumn('status', array(
            'header' => Mage::helper('sizeguide')->__('Status'), //'header'=> $this->__('ID'),
            'type'  => 'options',
            'sortable' => false,
            'width' => '40',
            'filter' => false,
            'index' => 'status',
            'options' => array('1'=>'Enable','2'=>'Disable'),
        ));
 

	  
	  $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('sizeguide')->__('Action'),
                'width'     => '40',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('sizeguide')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
		
		
		return parent::_prepareColumns();
    }
	
	 protected function _prepareMassaction()
    {
        $this->setMassactionIdField('sizeguide_id');
        $this->getMassactionBlock()->setFormFieldName('sizeguide');
        $this->setFilterVisibility(false);
        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('sizeguide')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('sizeguide')->__('Are you sure?')
        ));
		
        return $this;
    }
	
	public function getRowUrl($row)
    {
        // This is where our row data will link to
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
 
}