<?php
class Mngr_Recommend_Helper_Data
extends Mage_Core_Helper_Abstract
{
    public function log($message, $level = null, $force = false)
    {
        Mage::log($message, $level, 'recommend.log', $force);
    }
}