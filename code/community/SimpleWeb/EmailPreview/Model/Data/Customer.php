<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Customer
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Add item to customer's wishlist
	 *
	 * @param Mage_Customer_Model_Customer $customer
	 * @param array $products
	 */
	protected function _addItemToWishlist(Mage_Customer_Model_Customer $customer, $products)
	{
		$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer, true);

		foreach($products as $product)
		{
			$wishlist->addNewItem($product);
		}
	}

	/**
	 * Create customer with addresses
	 *
	 * @return Mage_Customer_Model_Customer
	 */
	protected function _createCustomer()
	{
		$customer = Mage::getModel('customer/customer');
		$customer->setWebsiteId($this->_websiteId)
			->setStore($this->_store)
			->setFirstname('John')
			->setLastname('Doe')
			->setEmail('john.doe@example.com')
			->setPassword('_password12345_');

		$customer->save();

		$region = Mage::getModel('directory/region')->loadByCode('CA', 'US');

		$address = Mage::getModel("customer/address");
		$address->setCustomerId($customer->getId())
			->setFirstname($customer->getFirstname())
			->setLastname($customer->getLastname())
			->setCountryId('US')
			->setPostcode('90024')
			->setCity('Los Angeles')
			->setRegionId($region->getRegionId())
			->setTelephone('00112233445566')
			->setFax('00112233445566')
			->setStreet('123 Main St')
			->setIsDefaultBilling(1)
			->setIsDefaultShipping(1)
			->setSaveInAddressBook(1);

		$address->save();

		// Reset address cache
		$customer->cleanAllAddresses();

		return $customer->load($customer->getId());
	}

	/**
	 * Create new customer
	 *
	 * @param array $data
	 * @return Mage_Customer_Model_Customer
	 */
	public function install($data = array())
	{
		$customer = $this->_createCustomer();
		$this->_addItemToWishlist($customer, $data['products']);

		$this->save('customer', $customer->getId());

		return $customer;
	}

	/**
	 * Uninstall customer
	 */
	public function uninstall()
	{
		Mage::getModel('customer/customer')
			->load($this->getEntityId('customer'))
			->delete();
	}
}