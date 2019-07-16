<?php
class Phxsolution_Mergeinfo_Model_Observer
{
	public function saveOrder($observer)
	{
		//Mage::log('We just made an Observer!');
		$controllerAction = $observer->getEvent()->getControllerAction();
		$response = $controllerAction->getResponse();
		$paymentResponse = Mage::helper('core')->jsonDecode($response->getBody());
		if (!isset($paymentResponse['error']) || !$paymentResponse['error']) {
			$controllerAction->getRequest()->setParam('form_key', Mage::getSingleton('core/session')->getFormKey());
			$controllerAction->getRequest()->setPost('agreement', array_flip(Mage::helper('checkout')->getRequiredAgreementIds()));
			$controllerAction->saveOrderAction();
			$orderResponse = Mage::helper('core')->jsonDecode($response->getBody());
			if ($orderResponse['error'] === false && $orderResponse['success'] === true) {
				if (!isset($orderResponse['redirect']) || !$orderResponse['redirect']) {
					$orderResponse['redirect'] = Mage::getUrl('*/*/success');
				}
				$controllerAction->getResponse()->setBody(Mage::helper('core')->jsonEncode($orderResponse));
			}
		}
	}
}