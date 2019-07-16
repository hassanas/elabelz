<?php
/**
* PHXSolution Mergeinfo
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@magentocommerce.com so you can be sent a copy immediately.
*
* Original code copyright (c) 2008 Irubin Consulting Inc. DBA Varien
*
* @category   Phxsolution_Mergeinfo_OnepageController
* @package    Phxsolution_Mergeinfo
* @author     Prakash Vaniya
* @contact    contact@phxsolution.com
* @site       www.phxsolution.com
* @copyright  Copyright (c) 2014 PHXSolution Mergeinfo
* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/
?>

<?php
require_once 'Mage/Checkout/controllers/OnepageController.php';
class Phxsolution_Mergeinfo_OnepageController extends Mage_Checkout_OnepageController
{
	public function saveBillingAction()
	{
		if ($this->_expireAjax()) {
			return;
		}
		if ($this->getRequest()->isPost()) {
			
			$billingData = $this->getRequest()->getPost('billing', array());
			$customerBillingAddressId = $this->getRequest()->getPost('billing_address_id', false);
			
			if (isset($billingData['email'])) {
				$billingData['email'] = trim($billingData['email']);
			}
			$result = $this->getOnepage()->saveBilling($billingData, $customerBillingAddressId);
			
			if (!isset($result['error'])) {
				
				$shippingData = $this->getRequest()->getPost('shipping', array());
				$customerShippingAddressId = $this->getRequest()->getPost('shipping_address_id', false);
				$result = $this->getOnepage()->saveShipping($shippingData, $customerShippingAddressId);
				if (!isset($result['error'])) {
					if ($this->getOnepage()->getQuote()->isVirtual()) {
						$result['goto_section'] = 'payment';
						$result['update_section'] = array(
							'name' => 'payment-method',
							'html' => $this->_getPaymentMethodsHtml()
						);
					} else {
						$result = $this->applyShippingCharges();
						if(!$result) {
							Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
								array('request'=>$this->getRequest(),
									'quote'=>$this->getOnepage()->getQuote()));
							$this->getOnepage()->getQuote()->collectTotals();
							$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	
							$result['goto_section'] = 'payment';
							$result['update_section'] = array(
								'name' => 'payment-method',
								//'review_html' => $this->_getReviewHtml2(),
								'html' => $this->_getPaymentMethodsHtml()
							);
						}
					}
				}
			}
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		}
	}
	
	protected function applyShippingCharges(){
		$cart = Mage::getSingleton('checkout/cart');
		$quote = $cart->getQuote();
		
		$address = $cart->getQuote()->getShippingAddress();
		// Find if our shipping has been included.
		$rates = $address->collectShippingRates()
						 ->getGroupedAllShippingRates();
		
		$shipping_method = "";
		foreach ($rates as $carrier) {
			foreach ($carrier as $rate) {
				$rateData = $rate->getData();
				$shipping_method = $rateData['code'];
			}
		}
		//echo $shipping_method;
		$result = $this->getOnepage()->saveShippingMethod($shipping_method);
		$this->getOnepage()->getQuote()->save();
		return $result;
		}
	
	/**
     * Get review html
     *
     * @return string
     */
    protected function updateAction()
    {
       $result['update_section'] = array(
			'html' => $this->_getReviewHtml2()
		);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
	
	protected function _getReviewHtml2()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_index');
        $layout->generateXml();
        $layout->generateBlocks();
        return $layout->getBlock('order.review')->toHtml();
		//$output = $layout->getOutput();
        //return $output;
    }
}