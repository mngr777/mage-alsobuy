<?php
class Mngr_Recommend_Model_Resource_Product_Similarity_Collection
extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('recommend/product_similarity');
    }
}