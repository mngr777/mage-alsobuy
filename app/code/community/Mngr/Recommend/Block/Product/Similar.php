<?php
class Mngr_Recommend_Block_Product_Similar
extends Mage_Catalog_Block_Product_Abstract
{
    const DEFAULT_PRODUCT_COUNT = 10;

    protected function _construct()
    {
        if (!$this->hasData('template'))
            $this->setData('template', 'recommend/product/similar.phtml');
        parent::_construct();

        $this->addData(array('cache_lifetime' => 86400));
        $this->addCacheTag(Mage_Catalog_Model_Product::CACHE_TAG);
        $this->addCacheTag(Mngr_Recommend_Model_Indexer_Similarity::CACHE_TAG);
    }

    public function getProduct()
    {
        if ($product = Mage::registry('current_product'))
            return $product;
        if ($product = Mage::registry('product'))
            return $product;
        return null;
    }

    public function getNumber()
    {
        if ($this->hasData('number'))
            return $this->getData('number');
        return self::DEFAULT_PRODUCT_COUNT;
    }

    public function getColumnCount()
    {
        if ($this->hasData('column_count')) {
            $columnCount = $this->getData('column_count');
            return $columnCount > 0 ? $columnCount : 1;
        }
        return 1;
    }

    public function getSimilarProductCollection($number = null)
    {
        if (is_null($number))
            $number = $this->getNumber();
        $product = $this->getProduct();
        if ($product) {
            $collection = Mage::getSingleton('recommend/product_similarity')
                ->getSimilarProductCollection($product->getId());
            $collection->setVisibility(
                Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());
            $collection = $this->_addProductAttributesAndPrices($collection)
                ->addStoreFilter()
                ->setPageSize($number)
                ->setCurPage(1);
            return $collection;
        } else {
            return null;
        }
    }

    public function getCacheKeyInfo()
    {
        return array(
            'RECOMMEND_PRODUCT_SIMILAR',
            Mage::app()->getStore()->getId(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->getCustomerGroupId(),
            'template' => $this->getTemplate(),
            ($this->getProduct() ? $this->getProduct()->getId() : 0),
            $this->getNumber(),
            $this->getColumnCount());
    }
}