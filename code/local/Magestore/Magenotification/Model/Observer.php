<?php

class Magestore_Magenotification_Model_Observer
{
	public function controllerActionPredispatch($observer)
	{
		try{//Disable this notification call 
			//Mage::getModel('magenotification/magenotification')->checkUpdate();
		}catch(Exception $e){
		
		}
	}
	
}