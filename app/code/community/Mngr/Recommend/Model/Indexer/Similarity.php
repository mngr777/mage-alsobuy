<?php
class Mngr_Recommend_Model_Indexer_Similarity
extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'recommend_similarity_match_result';
    const EVENT_PRODUCT_IDS_KEY = 'recommend_product_ids_to_update';
    const SAVE_BUNDLE_SIZE = 100;
    const CACHE_TAG = 'recommend_similarity_index';
    const PROCESS_CODE = 'recommend_similarity';

    protected $_matchedEntities = array();

    protected $_productCustomerIds = array();

    protected $_updatedProductMarks = array();

    protected $_dataToSave = array();

    public function getName()
    {
        return Mage::helper('recommend')->__('Product Similarity');
    }

    public function getDescription()
    {
        return Mage::helper('recommend')->__(
            'Index product similarities using item-to-item algorithm');
    }

    public function reindexAll()
    {
        Varien_Profiler::start(__METHOD__);
        try {
            $this->_startUpdate();
            foreach ($this->_getPurchasedProductIds() as $productId)
                $this->_updateProduct($productId);
            $this->_finishUpdate();
            $this->_cleanCache();
        } catch (Exception $e) {
            // log and re-throw exception
            Mage::logException($e);
            throw $e;
        }
        Varien_Profiler::stop(__METHOD__);
    }

    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (isset($data[self::EVENT_MATCH_RESULT_KEY])) {
            return $data[self::EVENT_MATCH_RESULT_KEY];
        }
        $result = parent::matchEvent($event);
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);
        return $result;
    }

    protected function _registerEvent(Mage_Index_Model_Event $event)
    {

    }

    protected function _processEvent(Mage_Index_Model_Event $event)
    {

    }

    public function updateOrderProducts(Mage_Sales_Model_Order $order)
    {
        Varien_Profiler::start(__METHOD__);
        $productIds = $this->_getOrderProductIds($order);
        $this->_startUpdate();
        foreach ($productIds as $productId)
            $this->_updateProduct($productId);
        $this->_finishUpdate();
        Varien_Profiler::stop(__METHOD__);
    }

    protected function _startUpdate()
    {

    }

    protected function _finishUpdate()
    {
        $this->_resetCache();
        $this->_saveData(true);
    }

    protected function _updateProduct($productId)
    {
        Varien_Profiler::start(__METHOD__);
        $resource = Mage::getResourceSingleton('recommend/product_similarity');
        $dataToSave = array();
        foreach ($this->_getPurchasedProductIds() as $similarProductId) {
            if ($similarProductId == $productId) continue;
            if ($this->_isProductUpdated($similarProductId)) continue;

            $similarity = $this->_calcCustomerVectorSimilarity(
                $this->_getProductCustomerIds($productId),
                $this->_getProductCustomerIds($similarProductId));
            $this->_addDataToSave(
                array($productId, $similarProductId, $similarity));
            $this->_addDataToSave(
                array($similarProductId, $productId, $similarity));
        }
        $this->_markProductUpdated($productId);
        Varien_Profiler::start(__METHOD__);
    }

    protected function _getPurchasedProductIds()
    {
        if (is_null($this->_purchasedProductIds)) {
            $productTable = Mage::getResourceSingleton('catalog/product')->getEntityTable();
            $orderItemTable = Mage::getResourceSingleton('sales/order_item')->getMainTable();
            $select = $this->_getReadAdapter()->select()
                ->distinct(true)
                ->from(
                    array('product' => $productTable),
                    array('product_id' => 'product.entity_id'))
                ->join(
                    array('order_item' => $orderItemTable),
                    'order_item.product_id = product.entity_id',
                    array())
                ->where('order_item.parent_item_id IS NULL');
            $this->_purchasedProductIds =
                $this->_getReadAdapter()->fetchCol($select);
        }
        return $this->_purchasedProductIds;
    }

    protected function _getProductCustomerIds($productId)
    {
        if (!isset($this->_productCustomerIds[$productId])) {
            $orderItemTable = Mage::getResourceSingleton('sales/order_item')->getMainTable();
            $orderTable = Mage::getResourceSingleton('sales/order')->getMainTable();
            $select = $this->_getReadAdapter()->select()
                ->distinct(true)
                ->from(
                    array('order_item' => $orderItemTable),
                    array())
                ->join(
                    array('order' => $orderTable),
                    'order.entity_id = order_item.order_id',
                    array('customer_id' => 'order.customer_id'))
                ->where('order_item.product_id = ?', $productId)
                ->where(
                    'order.state != ?',
                    Mage_Sales_Model_Order::STATE_CANCELED)
                ->where('NOT order.customer_id IS NULL');
            $this->_productCustomerIds[$productId] =
                $this->_getReadAdapter()->fetchCol($select);
        }
        return $this->_productCustomerIds[$productId];
    }

    protected function _addDataToSave($row)
    {
        $this->_dataToSave[] = $row;
        $this->_saveData();
    }

    protected function _saveData($force = false)
    {
        if ($force || count($this->_dataToSave >= self::SAVE_BUNDLE_SIZE)) {
            $this->_getResource()->saveDataBundle($this->_dataToSave);
            $this->_dataToSave = array();
        }
    }

    protected function _markProductUpdated($productId)
    {
        $this->_updatedProductMarks[$productId] = true;
    }

    protected function _isProductUpdated($productId)
    {
        return isset($this->_updatedProductMarks[$productId]);
    }

    protected function _calcCustomerVectorSimilarity(
        $customerIds, $similarCustomerIds)
    {
        // percent of customers who bought either of products
        // that bought both of them

        $dotProd = count(array_intersect($customerIds, $similarCustomerIds));
        if ($dotProd == 0)
            return 0;
        $lengthProd = sqrt(count($customerIds))
            * sqrt(count($similarCustomerIds));
        if ($lengthProd == 0)
            return 0;
        $similarity = 100 * $dotProd / $lengthProd;
        return $similarity;
    }

    protected function _cleanCache()
    {
        Mage::app()->cleanCache(self::CACHE_TAG);
    }

    protected function _getResource()
    {
        return Mage::getResourceSingleton('recommend/product_similarity');
    }

    protected function _getReadAdapter()
    {
        return Mage::getSingleton('core/resource')->getConnection('read');
    }

    protected function _getOrderProductIds(Mage_Sales_Model_Order $order)
    {
        return $order->getItemsCollection()->getColumnValues('product_id');
    }

    protected function _resetCache() {
        $this->_productCustomerIds = array();
        $this->_updatedProductMarks = array();
    }
}