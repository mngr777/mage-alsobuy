<?php
$this->startSetup();

$table = $this->getConnection()
    ->newTable($this->getTable('alsobuy/product_similarity_index'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true),
        'Record ID')
    ->addColumn(
        'product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'unsigned' => true,
            'nullable' => false),
        'Product ID')
    ->addColumn(
        'similar_product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'unsigned' => true,
            'nullable' => false),
        'Product ID to compare')
    ->addColumn(
        'similarity', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(12, 4),
        array(),
        'Similarity value')
    ->addIndex(
        $this->getIdxName(
            'alsobuy/product_similarity_index',
            array('product_id', 'similar_product_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('product_id', 'similar_product_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    ->addIndex(
        $this->getIdxName(
            'alsobuy/product_similarity_index', array('product_id')),
        array('product_id'))
    ->addIndex(
        $this->getIdxName(
            'alsobuy/product_similarity_index', array('similar_product_id')),
        array('similar_product_id'))
    ->addForeignKey(
        $this->getFkName(
            'alsobuy/product_similarity_index', 'product_id',
            'catalog/product', 'entity_id'),
        'product_id',
        $this->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $this->getFkName(
            'alsobuy/product_similarity_index', 'similar_product_id',
            'catalog/product', 'entity_id'),
        'similar_product_id',
        $this->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Product similarity index table');
$this->getConnection()->createTable($table);

$this->endSetup();