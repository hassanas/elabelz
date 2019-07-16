<?php
/**
 * Progos_Page.
 *
 * @category Progos
 *
 * Modifier Hassan Ali Shazhad
 * This is wrong implementation Progos_Miravist module present but even then some one create this one
 *
 */

/**
 * Class Progos_Page_Block_Page_Html_Header
 */
class Progos_Page_Block_Page_Html_Head extends Mirasvit_Seo_Block_Html_Head
{

 /**
     * Get HEAD HTML with CSS/JS/RSS definitions
     * (actually it also renders other elements, TODO: fix it up or rename this method)
     *
     * @return string
     */
    public function getCssJsHtml()
    {
        // separate items by types
        $lines  = array();
        foreach ($this->_data['items'] as $item) {
            if (!is_null($item['cond']) && !$this->getData($item['cond']) || !isset($item['name'])) {
                continue;
            }
            $if     = !empty($item['if']) ? $item['if'] : '';
            $params = !empty($item['params']) ? $item['params'] : '';
            switch ($item['type']) {
                case 'js':        // js/*.js
                case 'skin_js':   // skin/*/*.js
                case 'js_css':    // js/*.css
                case 'skin_css':  // skin/*/*.css
                    $lines[$if][$item['type']][$params][$item['name']] = $item['name'];
                    break;
                default:
                    $this->_separateOtherHtmlHeadElements($lines, $if, $item['type'], $params, $item['name'], $item);
                    break;
            }
        }

        // prepare HTML
        $shouldMergeJs = Mage::getStoreConfigFlag('dev/js/merge_files');
        $shouldMergeCss = Mage::getStoreConfigFlag('dev/css/merge_css_files');
        $html   = '';
        foreach ($lines as $if => $items) {
            if (empty($items)) {
                continue;
            }
            if (!empty($if)) {
                // open !IE conditional using raw value
                if (strpos($if, "><!-->") !== false) {
                    $html .= $if . "\n";
                } else {
                    $html .= '<!--[if '.$if.']>' . "\n";
                }
            }

            // static and skin css
            $html .= $this->_prepareStaticAndSkinElements('<link rel="stylesheet prefetch" as="style" type="text/css" href="%s"%s />'."\n",
                empty($items['js_css']) ? array() : $items['js_css'],
                empty($items['skin_css']) ? array() : $items['skin_css'],
                $shouldMergeCss ? array(Mage::getDesign(), 'getMergedCssUrl') : null
            );

            // static and skin javascripts
            $html .= $this->_prepareStaticAndSkinElements('<script rel="prefetch" as="script" type="text/javascript" src="%s"%s></script>' . "\n",
                empty($items['js']) ? array() : $items['js'],
                empty($items['skin_js']) ? array() : $items['skin_js'],
                $shouldMergeJs ? array(Mage::getDesign(), 'getMergedJsUrl') : null
            );

            // other stuff
            if (!empty($items['other'])) {
                $html .= $this->_prepareOtherHtmlHeadElements($items['other']) . "\n";
            }

            if (!empty($if)) {
                // close !IE conditional comments correctly
                if (strpos($if, "><!-->") !== false) {
                    $html .= '<!--<![endif]-->' . "\n";
                } else {
                    $html .= '<![endif]-->' . "\n";
                }
            }
        }
        return $html;
    }

    /**
     * @ Hassan Ali Shahzad
     * This function add to skip some js/css file to remove queryfier number, you can add these files in admin under Queryfier section
     * @param $format
     * @param array $staticItems
     * @param array $skinItems
     * @param null $mergeCallback
     * @return string
     */
    protected function &_prepareStaticAndSkinElements($format, array $staticItems, array $skinItems, $mergeCallback = null)
    {
        $dontaddedUrls          = explode(',',Mage::getStoreConfig('bubble_queryfier/suffix_js_css/dontadd'));
        $dontaddedUrlsMobile    = explode(',',Mage::getStoreConfig('bubble_queryfier/suffix_js_css/dontaddmobile'));
        $dontaddedUrlsDeskTop   = explode(',',Mage::getStoreConfig('bubble_queryfier/suffix_js_css/dontadddesktop'));
        $designPackage = Mage::getDesign();
        $baseJsUrl = Mage::getBaseUrl('js');
        $items = array();
        if ($mergeCallback && !is_callable($mergeCallback)) {
            $mergeCallback = null;
        }

        // get static files from the js folder, no need in lookups
        foreach ($staticItems as $params => $rows) {
            foreach ($rows as $name) {
                $items[$params][] = $mergeCallback ? Mage::getBaseDir() . DS . 'js' . DS . $name : $baseJsUrl . $name;
            }
        }

        // lookup each file basing on current theme configuration
        foreach ($skinItems as $params => $rows) {
            foreach ($rows as $name) {
                $items[$params][] = $mergeCallback ? $designPackage->getFilename($name, array('_type' => 'skin'))
                    : $designPackage->getSkinUrl($name, array());
            }
        }

        $html = '';
        foreach ($items as $params => $rows) {
            // attempt to merge
            $mergedUrl = false;
            if ($mergeCallback) {
                $mergedUrl = call_user_func($mergeCallback, $rows);
            }
            // render elements
            $params = trim($params);
            $params = $params ? ' ' . $params : '';
            if ($mergedUrl) {
                if ($this->isUrlSuffixEnabled()) {
                    if ($this->isUrlSuffixAutoGenerated()) {
                        $suffix = $this->_getFileModificationTimeFromUrl($mergedUrl);
                    } else {
                        $suffix = $this->getUrlSuffix();
                    }

                    $mergedUrl .= '?q=' . urlencode($suffix);

                }
                $html .= sprintf($format, $mergedUrl, $params);
            } else {
                foreach ($rows as $src) {
                    if ($this->isUrlSuffixEnabled()) {
                        if ($this->isUrlSuffixAutoGenerated()) {
                            $suffix = $this->_getFileModificationTimeFromUrl($src);
                        } else {
                            $suffix = $this->getUrlSuffix();
                        }

                        if(is_array($dontaddedUrls) && in_array(basename($src),$dontaddedUrls)){
                            //do nothing
                        }
                        elseif(Mage::getSingleton('core/design_package')->getTheme('frontend') == 'mobile'      && is_array($dontaddedUrlsMobile) && in_array(basename($src),$dontaddedUrlsMobile) ){
                            //do nothing
                        }
                        elseif(Mage::getSingleton('core/design_package')->getTheme('frontend') == 'superstore'  && is_array($dontaddedUrlsDeskTop) && in_array(basename($src),$dontaddedUrlsDeskTop) ){
                            //do nothing
                        }
                        else{
                            $src .= '?q=' . urlencode($suffix);
                        }

                    }
                    $html .= sprintf($format, $src, $params);
                }
            }
        }
        return $html;
    }
}