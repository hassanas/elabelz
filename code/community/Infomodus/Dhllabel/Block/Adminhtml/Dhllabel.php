<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php
class Infomodus_Dhllabel_Block_Adminhtml_Dhllabel extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_dhllabel';
        $this->_blockGroup = 'dhllabel';
        $this->_headerText = Mage::helper('dhllabel')->__('Item Manager');
        parent::__construct();
    }
}