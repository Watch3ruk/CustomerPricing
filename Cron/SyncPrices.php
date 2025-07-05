<?php
namespace TR\CustomerPricing\Cron;

use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;

class SyncPrices
{
    const PARALLEL_PROCESSES = 50; // Number of processes to run at once

    protected $logger;
    protected $customerRepository;
    protected $searchCriteriaBuilder;
    protected $directoryList;

    public function __construct(
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->directoryList = $directoryList;
    }

    public function execute()
    {
        $this->logger->info('Starting Price Sync Dispatcher cron job.');

        // 1. Get ALL customer IDs that have the accord_customer_code
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('accord_customer_code', true, 'notnull')
            ->create();
        $customers = $this->customerRepository->getList($searchCriteria)->getItems();
        $allCustomerIds = array_map(function ($customer) {
            return $customer->getId();
        }, $customers);

        if (empty($allCustomerIds)) {
            $this->logger->info('Dispatcher: No customers found to sync.');
            return;
        }

        // 2. Split the IDs into chunks for parallel processing
        $chunks = array_chunk($allCustomerIds, ceil(count($allCustomerIds) / self::PARALLEL_PROCESSES));
        
        $magentoPath = $this->directoryList->getRoot();
        $phpPath = 'php'; // Or specify full path to PHP binary, e.g., /usr/bin/php

        // 3. Dispatch a background process for each chunk
        foreach ($chunks as $chunk) {
            $customerIdString = implode(',', $chunk);
            $command = "{$phpPath} {$magentoPath}/bin/magento tr:prices:process-chunk {$customerIdString}";
            
            // Execute the command in the background
            $this->logger->info("Dispatcher: Starting worker for " . count($chunk) . " customers.");
            shell_exec($command . ' > /dev/null 2>&1 &');
        }

        $this->logger->info('Price Sync Dispatcher: All workers have been started.');
    }
}