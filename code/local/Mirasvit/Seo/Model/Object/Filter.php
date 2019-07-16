<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Seo_Model_Object_Filter extends Mirasvit_Seo_Model_Object_Category
{
    public function _construct()
    {
        parent::_construct();
    }

	protected function processCurrentCategory()
	{
		// устанавливаем для текущей категории. наивысший приоритет.
		if ($this->_category->getFilterMetaTitleTpl()) {
			$this->setMetaTitle($this->parse($this->_category->getFilterMetaTitleTpl()));
		}

		if ($this->_category->getFilterMetaKeywordsTpl()) {
			$this->setMetaKeywords($this->parse($this->_category->getFilterMetaKeywordsTpl()));
		}
		if ($this->_category->getFilterMetaDescriptionTpl()) {
			$this->setMetaDescription($this->parse($this->_category->getFilterMetaDescriptionTpl()));
		}

		if ($this->_category->getFilterTitleTpl()) {
			$this->setTitle($this->parse($this->_category->getFilterTitleTpl()));
		}

		if ($this->_category->getFilterDescriptionTpl()) {
			$this->setDescription($this->parse($this->_category->getFilterDescriptionTpl()));
		}
	}

	protected function process($category)
    {
        parent::process($category);
    	if ($category->getFilterTitleTpl()) {
			$this->setTitle($this->parse($category->getFilterTitleTpl()));
		}

		if ($category->getFilterMetaTitleTpl()) {
			$this->setMetaTitle($this->parse($category->getFilterMetaTitleTpl()));
		}
		if ($category->getFilterMetaKeywordsTpl()) {
			$this->setMetaKeywords($this->parse($category->getFilterMetaKeywordsTpl()));
		}
		if ($category->getFilterMetaDescriptionTpl()) {
			$this->setMetaDescription($this->parse($category->getFilterMetaDescriptionTpl()));
		}

    	if ($category->getFilterDescriptionTpl()) {
			$this->setDescription($this->parse($category->getFilterDescriptionTpl()));
		}
	}

}