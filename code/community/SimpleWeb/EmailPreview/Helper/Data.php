<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Dummy data
	 */
	const WORDS = 'lorem ipsum dolor sit amet consectetur adipiscing elit sin tantum modo ad indicia veteris memoriae cognoscenda curiosorum tu vero inquam ducas licet si sequetur ';
	const VAR_NAME = 'John Doe';
	const VAR_EMAIL = 'john.doe@example.com';
	const VAR_PHONE = '555-555-5555';
	const VAR_LINK = 'http://www.example.com/';

	/**
	 * Return store config
	 *
	 * @param string $path
	 * @return string
	 */
	public function getConfig($path)
	{
		return Mage::getStoreConfig($path);
	}

	/**
	 * Get dummy name
	 *
	 * @return string
	 */
	public function getName()
	{
		return self::VAR_NAME;
	}

	/**
	 * Get dummy email
	 *
	 * @return string
	 */
	public function getEmail()
	{
		return self::VAR_EMAIL;
	}

	/**
	 * Get dummy phone number
	 *
	 * @return string
	 */
	public function getPhone()
	{
		return self::VAR_PHONE;
	}

	/**
	 * Get dummy url
	 *
	 * @param string $path
	 * @return string
	 */
	public function getLink($path = '')
	{
		return self::VAR_LINK . $path;
	}

	/**
	 * Generate random sentence
	 *
	 * @param int $len
	 * @return string
	 */
	public function getRandomText($len = 10)
	{
		$words = explode(' ', str_repeat(self::WORDS, 10));
		shuffle($words);
		$words = array_slice($words, 0, $len);

		return ucfirst(implode(' ', $words)) . '.';
	}

}