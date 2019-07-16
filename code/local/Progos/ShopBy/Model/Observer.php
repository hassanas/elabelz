<?php

class Progos_ShopBy_Model_Observer {
    public function handleControllerFrontInitRouters($observer)
    {
        $observer->getEvent()->getFront()
            ->addRouter('amastyshopby', new Progos_ShopBy_Controller_Router());
    }
}