<?php
/**
 * Progos
 * @category  Progos
 * @package   Progos Category Cache Issue
 * @version   1.0.0
 * @author    Saroop
 * @date      04-05-2017
 * @description : Dynamically load the category content while using one static block.
 */

class  Progos_CategoryCache_Block_Cms_Block extends Mirasvit_SeoAutolink_Block_Cms_Block
{
    /**
     * Retrieve values of properties that unambiguously identify unique content
     * Category Id added into the Cache key.
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $blockId = $this->getBlockId();
        if ($this->getBlockId() && (Mage::registry('current_category')) ) {
            $currentyCategory = Mage::registry('current_category')->getId();
            return array(
                Mage_Cms_Model_Block::CACHE_TAG,
                Mage::app()->getStore()->getId(),
                $this->getBlockId(),
                $currentyCategory,
                (int) Mage::app()->getStore()->isCurrentlySecure()
            );
        } else if ($blockId) {
            $result = array(
                'CMS_BLOCK',
                $blockId,
                Mage::app()->getStore()->getCode(),
            );
        } else {
            $result = parent::getCacheKeyInfo();
        }
        return $result;
    }
}
