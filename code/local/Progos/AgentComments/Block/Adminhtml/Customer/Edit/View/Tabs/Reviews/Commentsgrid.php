<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Comments grid on customer information tab
 */
class Progos_AgentComments_Block_Adminhtml_Customer_Edit_View_Tabs_Reviews_Commentsgrid extends Mage_Adminhtml_Block_Template
{

    protected $_collection;

    public function __construct()
    {
        parent::__construct();
        $this->setId('customer_view_comments_grid');
    }
    /**
     * Getting comments collection
     */
    public function _beforeToHtml()
    {
        $this->_collection =  Mage::getModel('progos_agentcomments/comments')->getCollection();
        return parent::_beforeToHtml();
    }
    /**
     * Getting rows of the comments collection
     */
    public function getRows()
    {
        $customerId = $this->getRequest()->getParam('id');
        return $this->_collection->addFieldToFilter(
            array('customer_id'),array(
            array('eq'=> $customerId)));
    }
    /**
     * Getting username against each admin id
     */
    public function getUsername($id){
        $userName = Mage::getModel('admin/user')->load($id)->getData()['username'];
        return $userName;
    }

}
