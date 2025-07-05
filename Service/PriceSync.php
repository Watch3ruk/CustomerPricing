<?php
namespace TR\CustomerPricing\Service;

use Adlab\Accord\ApiConnector\ProductsIntialiser;
use TR\CustomerPricing\Api\PriceRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

class PriceSync
{
    private $apiInitialiser;
    private $priceRepo;
    private $customerRepo;
    private $logger;

    public function __construct(
        ProductsIntialiser $apiInitialiser,
        PriceRepositoryInterface $priceRepo,
        CustomerRepositoryInterface $customerRepo,
        LoggerInterface $logger
    ) {
        $this->apiInitialiser = $apiInitialiser;
        $this->priceRepo = $priceRepo;
        $this->customerRepo = $customerRepo;
        $this->logger = $logger;
    }

    public function syncForCustomer(string $customerCode)
    {
        $productsData = $this->apiInitialiser->initialiseProducts($customerCode);
        $skus = $productsData['available_products_skus'] ?? [];
        $prices = $this->apiInitialiser->initialiseProductPrices($customerCode, $skus);

        foreach ($prices as $sku => $price) {
            try {
                $model = $this->priceRepo->getByCodeAndSku($customerCode, $sku);
                $model->setPrice($price);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $model = $this->priceRepo->getEmpty();
                $model->setCustomerCode($customerCode);
                $model->setSku($sku);
                $model->setPrice($price);
            }

            try {
                $this->priceRepo->save($model);
            } catch (\Exception $e) {
                $this->logger->error("Failed saving price for $customerCode:$sku - " . $e->getMessage());
            }
        }
    }

    public function syncAllCustomers()
    {
        $searchCriteria = $this->customerRepo->getSearchCriteriaBuilder()->create();
        $customers = $this->customerRepo->getList($searchCriteria)->getItems();
        foreach ($customers as $customer) {
            $code = $customer->getCustomAttribute('accord_customer_code')->getValue();
            $this->syncForCustomer($code);
        }
    }
}