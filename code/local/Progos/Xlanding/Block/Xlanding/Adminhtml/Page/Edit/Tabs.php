<?php
class Progos_Xlanding_Block_Xlanding_Adminhtml_Page_Edit_Tabs extends Amasty_Xlanding_Block_Adminhtml_Page_Edit_Tabs
{
    protected function _beforeToHtml()
    {
        $tabs = array(
            'main' => 'General',
            'meta' => 'Meta',
            'design' => 'Design',
//            'filter' => 'Conditions',
            'links' => ' Featured Links'
        );

        foreach ($tabs as $code => $label){
            $label = Mage::helper('amlanding')->__($label);
            $content = $this->getLayout()->createBlock('amlanding/adminhtml_page_edit_tab_' . $code)
                ->setTitle($label)
                ->toHtml();

            $this->addTab($code, array(
                'label'     => $label,
                'content'   => $content,
            ));
        }

        if (Mage::getStoreConfig('amlanding/xlanding/merchandising')) {
            $this->addTab('merchandising', array(
                'label'     => Mage::helper('core')->__('Merchandise'),
                'url'       =>  $this->getUrl('admin_xlanding/adminhtml_xlanding/merchandising', array('_current' => true)),
                'class'     => 'ajax',
            ));
        }
        return parent::_beforeToHtml();
    }
}
			