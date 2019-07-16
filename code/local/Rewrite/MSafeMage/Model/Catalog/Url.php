<?php
/*
** @author: Sooraj Malhi <sooraj.malhi@progos.org>
** @package: Rewrite_MSafeMage  
** @description: Rewrite Catalog Url model to overide SafeMage category url issue 
*/
class Rewrite_MSafeMage_Model_Catalog_Url extends SafeMage_UrlOptimization_Model_Catalog_Url
{
   
    /**
     * Get unique category request path
     *
     * @param Varien_Object $category
     * @param string $parentPath
     * @return string
     */
    public function getCategoryRequestPath($category, $parentPath)
    {	
        $storeId = $category->getStoreId();
        $idPath  = $this->generatePath('id', null, $category);
        $suffix  = $this->getCategoryUrlSuffix($storeId);

        if (isset($this->_rewrites[$idPath])) {
            $this->_rewrite = $this->_rewrites[$idPath];
            $existingRequestPath = $this->_rewrites[$idPath]->getRequestPath();
        }

        if ($category->getUrlKey() == '') {
            $urlKey = $this->getCategoryModel()->formatUrlKey($category->getName());
        } else {
            $urlKey = $this->getCategoryModel()->formatUrlKey($category->getUrlKey());
        }

        if (null === $parentPath) {
            $parentPath = $this->getResource()->getCategoryParentPath($category);
        } elseif ($parentPath == '/') {
            $parentPath = '';
        }
        $parentPath = Mage::helper('catalog/category')
            ->getCategoryUrlPath($parentPath, true, $category->getStoreId());
 
        /* Remove parent category path from url */
        $requestPath = $urlKey . $suffix;

        if (isset($existingRequestPath) && $existingRequestPath == $requestPath) {
            return $existingRequestPath;
        }
		
        /**
         * Check if existing request past can be used
         */
        if (isset($this->_rewrites[$idPath]) && $requestPath && $existingRequestPath) {
            $existingRequestPath = preg_replace('/' . preg_quote($suffix, '/') . '$/', '', $existingRequestPath);
            $requestPathWithoutSuffix = preg_replace('/' . preg_quote($suffix, '/') . '$/', '', $requestPath);
            if ($existingRequestPath && strpos($existingRequestPath, $requestPathWithoutSuffix) === 0) {
                $existingRequestPathEnding = preg_replace(
                    '/^' . preg_quote($requestPathWithoutSuffix, '/') . '/', '', $existingRequestPath
                );
                if (preg_match('#^-([0-9]+)$#i', $existingRequestPathEnding)) {
                    return $this->_rewrites[$idPath]->getRequestPath();
                }
            }
        }

        if ($this->_deleteOldTargetPath($requestPath, $idPath, $storeId)) {
            return $requestPath;
        }

        return $this->getUnusedPath($category->getStoreId(), $requestPath,
            $this->generatePath('id', null, $category)
        );
    }
}
