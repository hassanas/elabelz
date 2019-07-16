<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php
class Infomodus_Upslabel_Block_Adminhtml_Upslabel extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_upslabel';
        $this->_blockGroup = 'upslabel';
        $this->_headerText = Mage::helper('upslabel')->__('Item Manager');
        /*$this->_addButtonLabel = Mage::helper('upslabel')->__('Add Item');*/
        parent::__construct();
    }

    protected function implodeServiceCodeFromXmlArray($xmlArray){
        $serviceCodes = array();
        $arrNew = array();
        if(!is_array($xmlArray) || !isset($xmlArray[0])){$arrNew[0] = $xmlArray;} else {
            $arrNew = $xmlArray;
        }
        foreach ($arrNew AS $code){
            $serviceCodes[] = $code['Service']['Code'];
        }
        return $serviceCodes;
    }
}