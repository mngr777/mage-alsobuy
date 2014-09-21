<?php
class Mngr_AlsoBuy_Model_Resource_Product_Similarity
extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('alsobuy/product_similarity_index', 'id');
    }

    public function saveDataBundle(array $data)
    {
        if (empty($data)) return $this;

        Varien_Profiler::start(__METHOD__);
        $dataPositive = array_filter($data, array($this, '_isDataPositive'));
        $dataZero = array_filter($data, array($this, '_isDataZero'));
        $write = $this->_getWriteAdapter();
        // insert/update positive values
        if (!empty($dataPositive)) {
            $sql = 'INSERT INTO '
                . $write->quoteIdentifier($this->getMainTable()) . ' '
                . '(product_id, similar_product_id, similarity) '
                . 'VALUES ';
            $valueStrings = array();
            foreach ($dataPositive as $row) {
                array_walk($row, array($write, 'quote'));
                $valueStrings[] = '(' . implode(', ', $row) . ')';
            }
            $sql .= implode(', ', $valueStrings);
            $sql .= 'ON DUPLICATE KEY UPDATE similarity = VALUES(similarity)';
            $write->query($sql);
        }

        // remove zero values
        if (!empty($dataZero)) {
            $sql = 'DELETE FROM '
                . $write->quoteIdentifier($this->getMainTable()) . ' '
                . 'WHERE ';
            $whereOr = array();
            foreach ($dataZero as $row) {
                list($productId, $similarProductId) = $row;
                $whereOr[] = '('
                    . $write->quoteInto(
                        'product_id = ?', $productId)
                    . ' AND '
                    . $write->quoteInto(
                        'similar_product_id = ?', $similarProductId)
                    . ')';
            }
            $sql .= implode(' OR ', $whereOr);
            $write->query($sql);
        }
        Varien_Profiler::stop(__METHOD__);
    }

    public function saveData($productId, $similarProductId, $similarity)
    {
        Varien_Profiler::start(__METHOD__);
        $write = $this->_getWriteAdapter();
        $row = array($productId, $similarProductId, $similarity);
        if ($this->_isDataPositive()) {
            $sql = 'INSERT INTO '
                . $write->quoteIdentifier($this->getMainTable()) . ' '
                . '(product_id, similar_product_id, similarity) '
                . 'VALUES (?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE similarity = VALUES(similarity)';
            $write->query($sql, $row);
        } else {
            $write->delete(
                $this->getMainTable(),
                array(
                    'product_id = ?' => $productId,
                    'similar_product_id = ?' => $similarProductId));
        }
        Varien_Profiler::stop(__METHOD__);
        return $this;
    }

    protected function _isDataPositive(array $row)
    {
        return isset($row[2]) && ($row[2] > 0);
    }

    protected function _isDataZero(array $row)
    {
        return !$this->_isDataPositive($row);
    }
}

