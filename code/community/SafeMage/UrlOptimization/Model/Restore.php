<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Model_Restore
{
    public function process() {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $resourceModel = Mage::getResourceModel('catalog/url');

        $urlRewriteTable = $resource->getTableName('core/url_rewrite');

        $select = $connection
            ->select()
            ->from($urlRewriteTable, array('*'))
            ->where('is_system = 1 AND (product_id > 0 OR category_id > 0)'.
                    " AND  request_path REGEXP  '^.+(-)[0-9]+(.html|.htm)$'");

        $rows = $connection->fetchAll($select);
        $count = 0;
        foreach($rows as $rewriteData) {
            $restoredRequestPath = explode('-', $rewriteData['request_path']);
            $restoredRequestPath[count($restoredRequestPath) - 2] .= trim($restoredRequestPath[count($restoredRequestPath) - 1], '0123456789');
            unset($restoredRequestPath[count($restoredRequestPath) - 1]);
            $restoredRequestPath = implode('-', $restoredRequestPath);

            $rewrite = $resourceModel->getRewriteByRequestPath($restoredRequestPath, $rewriteData['store_id']);
            if ($rewrite) {
                continue;
            }

            $connection->update(
                $urlRewriteTable,
                array('request_path' => $restoredRequestPath),
                'url_rewrite_id = ' . $rewriteData['url_rewrite_id']
            );

            $count++;

            // add 302 redirect
            unset($rewriteData['url_rewrite_id']);
            $rewriteData['target_path'] = $restoredRequestPath;
            $rewriteData['request_path'] = $rewriteData['request_path'];
            $rewriteData['id_path'] = $this->generateUniqueIdPath();
            $rewriteData['is_system'] = 0;
            $rewriteData['options'] = 'RP'; // Redirect = Permanent
            $resourceModel->saveRewrite($rewriteData);
        }

        return $count;
    }

    public function generateUniqueIdPath()
    {
        return str_replace('0.', '', str_replace(' ', '_', microtime()));
    }
}
