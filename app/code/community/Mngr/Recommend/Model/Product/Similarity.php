<?php
class Mngr_Recommend_Model_Product_Similarity
extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('recommend/product_similarity');
    }

    public function getSimilarProductCollection($productId)
    {
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->getSelect()
            ->join(
                array('similarity' => $this->_getResource()->getMainTable()),
                $collection->getConnection()->quoteInto(
                    'similarity.similar_product_id = e.entity_id AND product_id = ?',
                    $productId),
                array('similarity' => 'similarity.similarity'));
        $collection->getSelect()->order('similarity DESC');
        return $collection;
    }
}
