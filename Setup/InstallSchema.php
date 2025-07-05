<?php
namespace TR\CustomerPricing\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $table = $installer->getConnection()->newTable(
            $installer->getTable('tr_customer_pricing')
        )
        ->addColumn('entity_id', \Magento\Framework\Db\Ddl\Table::TYPE_INTEGER, null, [
            'identity' => true, 'nullable' => false, 'primary' => true
        ])
        ->addColumn('customer_code', \Magento\Framework\Db\Ddl\Table::TYPE_TEXT, 255, ['nullable' => false], 'Customer code')
        ->addColumn('sku', \Magento\Framework\Db\Ddl\Table::TYPE_TEXT, 255, ['nullable' => false], 'Product SKU')
        ->addColumn('price', \Magento\Framework\Db\Ddl\Table::TYPE_DECIMAL, '12,4', ['nullable' => false], 'Custom Price')
        ->addColumn('created_at', \Magento\Framework\Db\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\Db\Ddl\Table::TIMESTAMP_INIT], 'Created At')
        ->addColumn('updated_at', \Magento\Framework\Db\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\Db\Ddl\Table::TIMESTAMP_INIT_UPDATE], 'Updated At')
        ->addIndex(
            $installer->getIdxName('tr_customer_pricing', ['customer_code', 'sku'], \Magento\Framework\Db\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE),
            ['customer_code', 'sku'], ['type' => \Magento\Framework\Db\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}