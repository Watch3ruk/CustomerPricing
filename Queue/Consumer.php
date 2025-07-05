<?php
namespace TR\CustomerPricing\Queue;

use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Adlab\Accord\ApiConnector\ProductsIntialiser as ApiConnector;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Consumer
{
    protected $logger;
    protected $customerRepository;
    protected $apiConnector;
    protected $resourceConnection;
    protected $date;

    public function __construct(
        LoggerInterface $logger, // Magento's DI will inject our custom logger here
        CustomerRepositoryInterface $customerRepository,
        ApiConnector $apiConnector,
        ResourceConnection $resourceConnection,
        DateTime $date
    ) {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->apiConnector = $apiConnector;
        $this->resourceConnection = $resourceConnection;
        $this->date = $date;
    }

    /**
     * Process the message from the queue.
     *
     * @param string $customerId
     * @return void
     */
    public function process($customerId)
    {
        $this->logger->info("--- Consumer: Received job for customer ID {$customerId} ---");
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerCodeAttr = $customer->getCustomAttribute('accord_customer_code');

            if (!$customerCodeAttr || !$customerCodeAttr->getValue()) {
                $this->logger->info("Consumer: Customer {$customerId} has no accord_customer_code. Skipping sync.");
                return;
            }
            $customerCode = $customerCodeAttr->getValue();
            $this->logger->info("Consumer: Found customer code '{$customerCode}'.");

            $this->logger->info("Consumer: Calling API to get available SKUs for {$customerCode}.");
            $availableSkus = $this->apiConnector->getProductSkus($this->apiConnector->restrictedProducts($customerCode));
            if (empty($availableSkus)) {
                $this->logger->info("Consumer: API returned no available products for customer code: {$customerCode}.");
                return;
            }
            $this->logger->info("Consumer: Found " . count($availableSkus) . " available SKUs from API.");

            $this->logger->info("Consumer: Calling API to get prices for SKUs.");
            $pricesFromApi = $this->apiConnector->initialiseProductPrices($customerCode, $availableSkus);
            if (empty($pricesFromApi)) {
                $this->logger->warning("Consumer: Did not receive any prices from API for customer code: {$customerCode}.");
                return;
            }
            $this->logger->info("Consumer: Received " . count($pricesFromApi) . " prices from API.");

            $dataToInsert = [];
            $now = $this->date->gmtDate(); // Get current timestamp
            foreach ($pricesFromApi as $sku => $price) {
                $dataToInsert[] = [
                    'accord_customer_code' => $customerCode,
                    'sku' => $sku,
                    'price' => $price,
                    'updated_at' => $now // Add the timestamp to the data
                ];
            }

            if (!empty($dataToInsert)) {
                $connection = $this->resourceConnection->getConnection();
                $tableName = $this->resourceConnection->getTableName('tr_customer_pricing');
                $this->logger->info("Consumer: Attempting to save " . count($dataToInsert) . " prices to the database.");
                // Update both price and updated_at on duplicate
                $connection->insertOnDuplicate($tableName, $dataToInsert, ['price', 'updated_at']);
                $this->logger->info("Consumer: SUCCESS! Synced " . count($dataToInsert) . " prices for customer {$customerCode}.");
            }

        } catch (NoSuchEntityException $e) {
            $this->logger->error("Consumer FATAL ERROR: Customer ID {$customerId} not found.");
        } catch (\Exception $e) {
            $this->logger->error("Consumer FATAL ERROR: Sync failed for customer {$customerId}: " . $e->getMessage());
        }
    }
}