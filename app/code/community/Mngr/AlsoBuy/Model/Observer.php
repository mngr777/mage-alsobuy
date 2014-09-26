<?php
class Mngr_AlsoBuy_Model_Observer
{
    public function orderSaveBefore(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();
        Mage::getSingleton('index/indexer')->logEvent(
            $order,
            Mngr_AlsoBuy_Model_Indexer_Similarity::ORDER_ENTITY,
            Mage_Index_Model_Event::TYPE_SAVE);
    }

    public function orderSaveAfter(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('index/indexer')->indexEvents(
            Mngr_AlsoBuy_Model_Indexer_Similarity::ORDER_ENTITY,
            Mage_Index_Model_Event::TYPE_SAVE);
    }
}