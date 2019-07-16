<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Observer
{
	/**
	 * @var SimpleWeb_EmailPreview_Helper_Data
	 */
	protected $_helper;

	/**
	 * @return SimpleWeb_EmailPreview_Helper_Data
	 */
	public function helper()
	{
		if (is_null($this->_helper))
		{
			$this->_helper = Mage::helper('simpleweb_emailpreview');
		}
		return $this->_helper;
	}

	/**
	 * @param Varien_Event_Observer $observer
	 */
	public function setVariables(Varien_Event_Observer $observer)
	{
		$data = $observer->getEvent()->getData();

		// If defined variable, set real code
		if (isset($data['orig_template_code']))
		{
			$data['template_id'] = $data['orig_template_code'];
		}

		$name = strtolower($data['template_id']);
		$name = str_replace('_', ' ', $name);
		$name = ucwords($name);
		$name = str_replace(' ', '', $name);
		$name = 'var' . $name;

		if (is_callable(array($this, $name)))
		{
			$this->{$name}($data['variables']);
		}
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varContactsEmailEmailTemplate($variables)
	{
		$data = new Varien_Object(array(
			'name' => $this->helper()->getName(),
			'email' => $this->helper()->getEmail(),
			'telephone' => $this->helper()->getPhone(),
			'comment' => $this->helper()->getRandomText(20),
		));
		$variables->setData('data', $data);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varAdminEmailsForgotEmailTemplate($variables)
	{
		$variables->setData('user', new Varien_Object(array(
			'name' => $this->helper()->getName()
		)));
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varGiftcardGiftcardaccountEmailTemplate($variables)
	{
		$variables->setData('name', $this->helper()->getName());
		$variables->setData('code', '00000000000');
		$variables->setData('balance', '$10.00');
	}

	/**
	 * @param Varien_Object $variables
	 */
	protected function _setPaymentHtml($variables)
	{
		if ($variables->getOrder() && $variables->getOrder()->getId())
		{
			$paymentBlock = Mage::helper('payment')
				->getInfoBlock($variables->getOrder()->getPayment())
				->setIsSecureMode(true);

			$paymentBlock->getMethod()
				->setStore($variables->getStore()->getId());

			$variables->setData('payment_html', $paymentBlock->toHtml());
		}
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSalesEmailCreditmemoTemplate($variables)
	{
		$this->_setPaymentHtml($variables);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSalesEmailCreditmemoGuestTemplate($variables)
	{
		$this->_setPaymentHtml($variables);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSalesEmailInvoiceTemplate($variables)
	{
		$this->_setPaymentHtml($variables);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSalesEmailInvoiceGuestTemplate($variables)
	{
		$this->_setPaymentHtml($variables);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSalesEmailOrderTemplate($variables)
	{
		$this->_setPaymentHtml($variables);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSalesEmailOrderGuestTemplate($variables)
	{
		$this->_setPaymentHtml($variables);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSalesEmailShipmentTemplate($variables)
	{
		$this->_setPaymentHtml($variables);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSalesEmailShipmentGuestTemplate($variables)
	{
		$this->_setPaymentHtml($variables);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varNewsletterSubscriptionConfirmEmailTemplate($variables)
	{
		$subscriber = new Varien_Object(array(
			'confirmation_link' => Mage::getModel('core/url')
				->setStore($variables->getStore()->getId())
				->getUrl('newsletter/subscriber/confirm', array(
					'id'     => 0,
					'code'   => md5(1),
					'_nosid' => true
				))
		));

		$variables->setData('subscriber', $subscriber);
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varCatalogProductalertEmailPriceTemplate($variables)
	{
		$priceBlock = Mage::helper('productalert')
			->createBlock('productalert/email_price')
			->setStore($variables->getStore());

		$collection = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*')
			->addFieldToFilter('visibility', 4)
			->setPage(0, 5);

		foreach($collection as $product)
		{
			$priceBlock->addProduct($product);
		}

		$variables->setData('alertGrid', $priceBlock->toHtml());

		if ($variables->getCustomer() && $variables->getCustomer()->getId())
		{
			$variables->setData('customerName', $variables->getCustomer()->getName());
		}
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varCatalogProductalertEmailStockTemplate($variables)
	{
		$stockBlock = Mage::helper('productalert')
			->createBlock('productalert/email_stock')
			->setStore($variables->getStore());

		$collection = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*')
			->addFieldToFilter('visibility', 4)
			->setPage(0, 5);

		foreach($collection as $product)
		{
			$stockBlock->addProduct($product);
		}

		$variables->setData('alertGrid', $stockBlock->toHtml());

		if ($variables->getCustomer() && $variables->getCustomer()->getId())
		{
			$variables->setData('customerName', $variables->getCustomer()->getName());
		}
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varSendfriendEmailTemplate($variables)
	{
		if ($variables->getCustomer() && $variables->getCustomer()->getId())
		{
			$variables->setData('name', $variables->getCustomer()->getName());
		}

		$variables->setData('product_url', $this->helper()->getLink('/test-product-name'));
		$variables->setData('product_name', 'Test product name');
		$variables->setData('message', $this->helper()->getRandomText(30));
	}

	/**
	 * @param Varien_Object $variables
	 */
	public function varWishlistEmailEmailTemplate($variables)
	{
		$addLink = Mage::getUrl('wishlist/shared/allcart', array('code' => md5(1)));
		$variables->setData('addAllLink', $addLink);

		$viewLink = Mage::getUrl('wishlist/shared/index', array('code' => md5(1)));
		$variables->setData('viewOnSiteLink', $viewLink);

		$variables->setData('message', $this->helper()->getRandomText(30));

		if ($variables->getWishlist() && $variables->getWishlist()->getId())
		{
			Mage::register('wishlist', $variables->getWishlist());

			$wishlistBlock = Mage::getModel('core/layout')
				->createBlock('wishlist/share_email_items')
				->toHtml();

			$variables->setData('items', $wishlistBlock);
		}
	}
}