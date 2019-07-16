<?php
if ((bool)Mage::getStoreConfig('progos_partialindex/index/disable_index')) {
    class Progos_Partialindex_Model_Index_Pure extends Progos_Partialindex_Model_Index_Indexer { } 
} else {
    class Progos_Partialindex_Model_Index_Pure extends Mage_Index_Model_Indexer { }
}