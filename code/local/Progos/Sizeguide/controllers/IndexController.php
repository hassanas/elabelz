<?php
class Progos_Sizeguide_IndexController extends Mage_Core_Controller_Front_Action
{
	public function indexAction()
    {	
	
		//Get current layout state
        $this->loadLayout();   
		$block = $this->getLayout()->createBlock('sizeguide/sizeguide')->setTemplate('sizeguide/sizeguide.phtml');
		$this->getLayout()->getBlock('content')->append($block);
		$this->_initLayoutMessages('core/session');
        $this->renderLayout();
    }



}
