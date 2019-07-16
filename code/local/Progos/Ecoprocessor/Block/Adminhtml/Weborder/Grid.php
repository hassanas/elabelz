<?php

/**
 * This Module is created to complete the orders from Web
 * @category      Progos
 * @package       Progos_Ecoprocessor
 * @copyright     Progos TechCopyright (c) 13-02-2018
 * @author       Saroop Chand
 */
class Progos_Ecoprocessor_Block_Adminhtml_Weborder_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $this->setId('weborderGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * prepare collection
     *
     * @access protected
     * @return Progos_Ecoprocessor_Block_Adminhtml_Weborder_Grid
     *
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ecoprocessor/quote_index')
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
     * @return Progos_Ecoprocessor_Block_Adminhtml_Weborder_Grid
     *
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Id'),
                'index' => 'id',
                'type' => 'number'
            )
        );
        $this->addColumn(
            'qid',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Quote Id'),
                'index' => 'qid',
                'type' => 'number',

            )
        );
        $this->addColumn(
            'payemnt_method',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Payemnt Method'),
                'index' => 'payemnt_method',
                'type' => 'text',

            )
        );
        $this->addColumn(
            'shipping_method',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Shipping Method'),
                'index' => 'shipping_method',
                'type' => 'text',

            )
        );
        $this->addColumn(
            'payment_status',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Payment Status'),
                'index' => 'payment_status',
                'type' => 'number',

            )
        );
        $this->addColumn(
            'reserved_order_id',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Reserved Order Id'),
                'index' => 'reserved_order_id',
                'type' => 'text',

            )
        );
        $this->addColumn(
            'cc_cid',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Credit Card ID'),
                'index' => 'cc_cid',
                'type' => 'text',

            )
        );

        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Created At'),
                'index' => 'created_at',
                'type' => 'text',
            )
        );
        $this->addColumn(
            'updated_at',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Updated At'),
                'index' => 'updated_at',
                'type' => 'text',
            )
        );
        $this->addColumn(
            'status',
            array(
                'header' => Mage::helper('ecoprocessor')->__('Order Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => array(
                    '1' => Mage::helper('ecoprocessor')->__('Processed'),
                    '0' => Mage::helper('ecoprocessor')->__('Pending'),
                )
            )
        );


        return parent::_prepareColumns();
    }

    /**
     * prepare mass action
     *
     * @access protected
     * @return Progos_Ecoprocessor_Block_Adminhtml_Weborder_Grid
     *
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('weborder');

        $this->getMassactionBlock()->addItem(
            'status',
            array(
                'label' => Mage::helper('ecoprocessor')->__('Process'),
                'url' => $this->getUrl('*/*/runwebOrdersProcess', array('_current' => true)),
                'additional' => array(
                    'status' => array(
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => Mage::helper('ecoprocessor')->__(''),
                        'values' => array(
                            '0' => Mage::helper('ecoprocessor')->__(' Pending Orders'),
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
     * @return Progos_Ecoprocessor_Block_Adminhtml_Weborder_Grid
     *
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }
}
