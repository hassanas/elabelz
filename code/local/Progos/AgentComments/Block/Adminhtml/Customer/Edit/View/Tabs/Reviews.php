<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Block to show the tab on customer settings on backend
 */
class Progos_AgentComments_Block_Adminhtml_Customer_Edit_View_Tabs_Reviews
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{


    protected function _construct()
    {
        parent::_construct();
        $this->setAfter('tags');
    }

    /**
     * Get tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('progos_agentcomments')->__('Agent Reviews');
    }
    /**
     * Get tab label
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('progos_agentcomments')->__('Agent Reviews');
    }
    /**
     * Get tab label
     *
     * @return string
     */
    public function canShowTab()
    {
        return true;
    }
    /**
     * Check if tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

}