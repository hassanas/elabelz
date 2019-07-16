<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php
class Infomodus_Dhllabel_Block_Adminhtml_Account extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_account';
        $this->_blockGroup = 'dhllabel';
        $this->_headerText = Mage::helper('dhllabel')->__('Account Manager');
        $this->_addButtonLabel = Mage::helper('dhllabel')->__('Add Account');
        parent::__construct();
    }
}