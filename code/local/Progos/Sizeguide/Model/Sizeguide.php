<?php

class Progos_Sizeguide_Model_Sizeguide extends  Mage_Core_Model_Abstract
{
	public function _construct()
    {
       parent::_construct();
       $this->_init('sizeguide/sizeguide');
    }

}