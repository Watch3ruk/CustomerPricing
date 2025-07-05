<?php
namespace TR\CustomerPricing\Cron;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config as EavConfig;

class ResetFlags
{
    private LoggerInterface $logger;
    private ResourceConnection $resource;
    private EavConfig $eavConfig;

    public function __construct(
        LoggerInterface $logger,
        ResourceConnection $resource,
        EavConfig $eavConfig
    ) {
        $this->logger   = $logger;
        $this->resource = $resource;
        $this->eavConfig = $eavConfig;
    }

    public function execute(): void
    {
        $this->logger->info('Starting Daily Price Sync Flag Reset.');

        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName('customer_entity_datetime');
        $attrId     = $this->eavConfig
            ->getAttribute('customer', 'last_price_sync_at')
            ->getAttributeId();

        try {
            $updated = $connection->update(
                $table,
                ['value' => null],
                ['attribute_id = ?' => $attrId]
            );
            $this->logger->info("Reset flags for {$updated} customers.");
        } catch (\Exception $e) {
            $this->logger->error('Error resetting flags: ' . $e->getMessage());
        }

        $this->logger->info('Finished Daily Price Sync Flag Reset.');
    }
}