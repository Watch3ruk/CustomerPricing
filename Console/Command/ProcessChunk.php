<?php
namespace TR\CustomerPricing\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Adlab\Accord\ApiConnector\ProductsIntialiser as ApiConnector;
use Magento\Framework\App\ResourceConnection;

class ProcessChunk extends Command
{
    // All the same properties from the old Cron class
    protected $appState;
    protected $logger;
    protected $customerRepository;
    protected $searchCriteriaBuilder;
    protected $apiConnector;
    protected $resourceConnection;

    public function __construct(
        AppState $appState,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ApiConnector $apiConnector,
        ResourceConnection $resourceConnection
    ) {
        $this->appState = $appState;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->apiConnector = $apiConnector;
        $this->resourceConnection = $resourceConnection;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('tr:prices:process-chunk')
             ->setDescription('Worker command to process a chunk of customer IDs.')
             ->addArgument('customer_ids', InputArgument::REQUIRED, 'Comma-separated list of customer entity IDs to process.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $customerIds = explode(',', $input->getArgument('customer_ids'));

        $output->writeln("Processing chunk of " . count($customerIds) . " customers.");
        $this->logger->info("Starting Price Sync Worker for IDs: " . implode(',', $customerIds));

        // Get customer objects from the provided IDs
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('entity_id', $customerIds, 'in')->create();
        $customers = $this->customerRepository->getList($searchCriteria)->getItems();

        // The rest of this logic is identical to the previous cron job's loop
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('tr_customer_pricing');

        foreach ($customers as $customer) {
            $customerCodeAttr = $customer->getCustomAttribute('accord_customer_code');
            if (!$customerCodeAttr) {
                continue;
            }
            $customerCode = $customerCodeAttr->getValue();
            
            // ... (The entire API call and bulk insert logic is the same as before) ...
            try {
                $availableSkus = $this->apiConnector->getProductSkus($this->apiConnector->restrictedProducts($customerCode));
                if (empty($availableSkus)) continue;

                $pricesFromApi = $this->apiConnector->initialiseProductPrices($customerCode, $availableSkus);
                if (empty($pricesFromApi)) continue;

                $dataToInsert = [];
                foreach ($pricesFromApi as $sku => $price) {
                    $dataToInsert[] = ['accord_customer_code' => $customerCode, 'sku' => $sku, 'price' => $price];
                }

                if (!empty($dataToInsert)) {
                    $connection->insertOnDuplicate($tableName, $dataToInsert, ['price']);
                    $this->logger->info("Worker synced " . count($dataToInsert) . " prices for customer code: {$customerCode}.");
                }
            } catch (\Exception $e) {
                $this->logger->error("Worker error for customer {$customerCode}: " . $e->getMessage());
            }
        }
        $this->logger->info("Finished Price Sync Worker for IDs: " . implode(',', $customerIds));
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}