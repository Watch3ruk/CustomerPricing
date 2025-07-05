<?php
namespace TR\CustomerPricing\Cron;

use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\MessageQueue\PublisherInterface;

class SyncPrices
{
    const TOPIC_NAME = 'tr.customer.price.sync';

    protected $logger;
    protected $customerRepository;
    protected $searchCriteriaBuilder;
    protected $publisher;

    public function __construct(
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->publisher = $publisher;
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

        foreach ($allCustomerIds as $customerId) {
            $this->publisher->publish(self::TOPIC_NAME, $customerId);
            $this->logger->info("Dispatcher: Job created for customer ID {$customerId}");
        }

        $this->logger->info('Price Sync Dispatcher: Dispatched jobs for all customers.');
    }
}