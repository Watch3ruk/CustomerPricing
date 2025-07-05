<?php
namespace TR\CustomerPricing\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config as EavConfig;

class SyncPriceOnLogin implements ObserverInterface
{
    const TOPIC_NAME = 'tr.customer.price.sync';

    protected $logger;
    protected $customerRepository;
    protected $publisher;
    protected $resourceConnection;
    protected $eavConfig;

    public function __construct(
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        PublisherInterface $publisher,
        ResourceConnection $resourceConnection,
        EavConfig $eavConfig
    ) {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->publisher = $publisher;
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
    }

    public function execute(Observer $observer)
    {
        $this->logger->info('--- SyncPriceOnLogin Observer TRIGGERED ---');

        try {
            $customer = $observer->getEvent()->getCustomer();
            $customerId = $customer->getId();

            if ($this->hasSyncedToday($customerId)) {
                $this->logger->info("Sync skipped for customer {$customerId}: already synced today.");
                return;
            }

            $this->logger->info("Attempting to set flag and create sync job for customer ID {$customerId}");
            
            // ** THE FIX: Set the flag directly in the database **
            $this->setSyncFlag($customerId);
            $this->logger->info("SUCCESS: Flag 'last_price_sync_at' saved for customer {$customerId}.");

            $this->publisher->publish(self::TOPIC_NAME, $customerId);
            $this->logger->info("Publisher: Job created for customer ID {$customerId}");

        } catch (\Exception $e) {
            $this->logger->error("Observer Error: " . $e->getMessage());
        }
    }

    /**
     * Checks the database directly to see if a sync has occurred.
     * @param int $customerId
     * @return bool
     */
    private function hasSyncedToday($customerId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $attribute = $this->eavConfig->getAttribute('customer', 'last_price_sync_at');
            $attributeId = $attribute->getId();
            
            $select = $connection->select()
                ->from($connection->getTableName('customer_entity_datetime'), ['value'])
                ->where('entity_id = ?', $customerId)
                ->where('attribute_id = ?', $attributeId);

            $syncDate = $connection->fetchOne($select);

            return $syncDate ? true : false;
        } catch (\Exception $e) {
            $this->logger->error("Error checking sync flag directly: " . $e->getMessage());
            return false; // Fail safely
        }
    }

    /**
     * Sets the sync flag directly in the database.
     * @param int $customerId
     * @return void
     */
    private function setSyncFlag($customerId)
    {
        $connection = $this->resourceConnection->getConnection();
        $attribute = $this->eavConfig->getAttribute('customer', 'last_price_sync_at');
        $attributeId = $attribute->getId();
        $tableName = $connection->getTableName('customer_entity_datetime');

        $data = [
            'value' => date('Y-m-d H:i:s')
        ];
        $where = [
            'entity_id = ?' => $customerId,
            'attribute_id = ?' => $attributeId
        ];

        // Try to update an existing row first
        $updatedRows = $connection->update($tableName, $data, $where);

        // If no row was updated, it means one doesn't exist, so insert it
        if ($updatedRows === 0) {
            $data['entity_id'] = $customerId;
            $data['attribute_id'] = $attributeId;
            $connection->insert($tableName, $data);
        }
    }
}
