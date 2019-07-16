<?php

/**
 * This Module is created to complete the orders from App
 * working on Eid
 * @category      Progos
 * @package       Progos_MobileAppOrders
 * @copyright     Progos TechCopyright (c) 01-09-201
 * @author       Hassan Ali Shahzad
 */
class Progos_Restmob_Block_Adminhtml_Mobileapporder_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * constructor
     *
     * @access public
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('mobileapporderGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * prepare collection
     *
     * @access protected
     * @return Progos_Restmob_Block_Adminhtml_Mobileapporder_Grid
     *
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('restmob/quote_index')
            ->getCollection()
            ->addFieldToFilter('status', array('eq' => 0))
            ->addFieldToFilter('payment_status', array('eq' => 1));
            //->addFieldToFilter('payemnt_method', array('neq' => 'free'));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare grid collection
     *
     * @access protected
     * @return Progos_Restmob_Block_Adminhtml_Mobileapporder_Grid
     *
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            array(
                'header' => Mage::helper('restmob')->__('Id'),
                'index' => 'id',
                'type' => 'number'
            )
        );
        $this->addColumn(
            'qid',
            array(
                'header' => Mage::helper('restmob')->__('Quote Id'),
                'index' => 'qid',
                'type' => 'number',

            )
        );
        $this->addColumn(
            'payemnt_method',
            array(
                'header' => Mage::helper('restmob')->__('Payemnt Method'),
                'index' => 'payemnt_method',
                'type' => 'text',

            )
        );
        $this->addColumn(
            'shipping_method',
            array(
                'header' => Mage::helper('restmob')->__('Shipping Method'),
                'index' => 'shipping_method',
                'type' => 'text',

            )
        );
        $this->addColumn(
            'payment_status',
            array(
                'header' => Mage::helper('restmob')->__('Payment Status'),
                'index' => 'payment_status',
                'type' => 'number',

            )
        );
        $this->addColumn(
            'reserved_order_id',
            array(
                'header' => Mage::helper('restmob')->__('Reserved Order Id'),
                'index' => 'reserved_order_id',
                'type' => 'text',

            )
        );
        $this->addColumn(
            'cc_cid',
            array(
                'header' => Mage::helper('restmob')->__('Credit Card ID'),
                'index' => 'cc_cid',
                'type' => 'text',

            )
        );

        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('restmob')->__('Created At'),
                'index' => 'created_at',
                'type' => 'text',
            )
        );
        $this->addColumn(
            'updated_at',
            array(
                'header' => Mage::helper('restmob')->__('Updated At'),
                'index' => 'updated_at',
                'type' => 'text',
            )
        );
        $this->addColumn(
            'status',
            array(
                'header' => Mage::helper('restmob')->__('Order Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => array(
                    '1' => Mage::helper('restmob')->__('Processed'),
                    '0' => Mage::helper('restmob')->__('Pending'),
                )
            )
        );


        return parent::_prepareColumns();
    }

    /**
     * prepare mass action
     *
     * @access protected
     * @return Progos_Restmob_Block_Adminhtml_Mobileapporder_Grid
     *
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('mobileapporder');

        $this->getMassactionBlock()->addItem(
            'status',
            array(
                'label' => Mage::helper('restmob')->__('Process'),
                'url' => $this->getUrl('*/*/runAppOrdersProcess', array('_current' => true)),
                'additional' => array(
                    'status' => array(
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => Mage::helper('restmob')->__(''),
                        'values' => array(
                            '0' => Mage::helper('restmob')->__(' Pending Orders'),
                        )
                    )
                )
            )
        );
        return $this;
    }

    /**
     * get the grid url
     *
     * @access public
     * @return string
     *
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * after collection load
     *
     * @access protected
     * @return Progos_Restmob_Block_Adminhtml_Mobileapporder_Grid
     *
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }
}
