<?php
class Progos_CategoryToIsAnchor_Model_Observer
{
	public function categoryIsAnchor(Varien_Event_Observer $observer)
	{
		$category = $observer->getEvent()->getCategory();
		if($category->getCreatedAt() === $category->getUpdatedAt()) {
			$category->setData('is_anchor', 1);
			$category->save();
		}
	}
}
