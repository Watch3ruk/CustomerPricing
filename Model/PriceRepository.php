<?php
namespace TR\CustomerPricing\Model;

use TR\CustomerPricing\Api\Data\PriceInterface;
use TR\CustomerPricing\Api\PriceRepositoryInterface;
use TR\CustomerPricing\Model\ResourceModel\Price as PriceResource;
use TR\CustomerPricing\Model\PriceFactory;
use TR\CustomerPricing\Model\ResourceModel\Price\CollectionFactory as PriceCollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

class PriceRepository implements PriceRepositoryInterface
{
    /**
     * Cache tag for custom prices.
     */
    const CACHE_TAG = 'tr_cust_price';

    /**
     * Cache lifetime in seconds (1 hour).
     */
    const CACHE_LIFETIME = 3600;

    private $resource;
    private $priceFactory;
    private $priceCollectionFactory;
    private $cache;
    private $serializer;

    public function __construct(
        PriceResource $resource,
        PriceFactory $priceFactory,
        PriceCollectionFactory $priceCollectionFactory,
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->resource = $resource;
        $this->priceFactory = $priceFactory;
        $this->priceCollectionFactory = $priceCollectionFactory;
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    public function save(PriceInterface $price)
    {
        try {
            // When saving, invalidate the relevant cache to ensure data is fresh
            if ($price->getAccordCustomerCode() && $price->getSku()) {
                $cacheKey = self::CACHE_TAG . '_' . $price->getAccordCustomerCode() . '_' . $price->getSku();
                $this->cache->remove($cacheKey);
            }
            $this->resource->save($price);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $price;
    }

    public function getById($id)
    {
        $price = $this->priceFactory->create();
        $this->resource->load($price, $id);
        if (!$price->getId()) {
            throw new NoSuchEntityException(__('The price with the ID "%1" does not exist.', $id));
        }
        return $price;
    }

    public function getPriceByCodeAndSku($customerCode, $sku)
    {
        // Create a unique cache key for this specific customer code and SKU
        $cacheKey = self::CACHE_TAG . '_' . $customerCode . '_' . $sku;

        // Try to load from cache first
        $cachedData = $this->cache->load($cacheKey);
        if ($cachedData) {
            $data = $this->serializer->unserialize($cachedData);
            // Return a new model instance with the cached data
            return $this->priceFactory->create(['data' => $data]);
        }

        // If not in cache, get from the database
        $collection = $this->priceCollectionFactory->create();
        $collection->addFieldToFilter('accord_customer_code', $customerCode)
                   ->addFieldToFilter('sku', $sku);

        $priceModel = $collection->getFirstItem();
        if ($priceModel && $priceModel->getId()) {

            // Save the found price data to cache for next time
            $this->cache->save(
                $this->serializer->serialize($priceModel->getData()),
                $cacheKey,
                [self::CACHE_TAG],
                self::CACHE_LIFETIME
            );

            return $priceModel;
        }
        return null;
    }

    public function getPricesByCodeAndSkus($customerCode, array $skus)
    {
        $results = [];
        $skusToQuery = [];

        foreach ($skus as $sku) {
            $cacheKey = self::CACHE_TAG . '_' . $customerCode . '_' . $sku;
            $cachedData = $this->cache->load($cacheKey);
            if ($cachedData) {
                $data = $this->serializer->unserialize($cachedData);
                $results[$sku] = $this->priceFactory->create(['data' => $data]);
            } else {
                $skusToQuery[] = $sku;
            }
        }

        if (!empty($skusToQuery)) {
            $collection = $this->priceCollectionFactory->create();
            $collection->addFieldToFilter('accord_customer_code', $customerCode)
                       ->addFieldToFilter('sku', ['in' => $skusToQuery]);

            foreach ($collection as $priceModel) {
                $sku = $priceModel->getSku();
                $results[$sku] = $priceModel;

                $cacheKey = self::CACHE_TAG . '_' . $customerCode . '_' . $sku;
                $this->cache->save(
                    $this->serializer->serialize($priceModel->getData()),
                    $cacheKey,
                    [self::CACHE_TAG],
                    self::CACHE_LIFETIME
                );
            }
        }

        return $results;
    }

    public function delete(PriceInterface $price)
    {
        try {
            // Invalidate the cache before deleting
            $cacheKey = self::CACHE_TAG . '_' . $price->getAccordCustomerCode() . '_' . $price->getSku();
            $this->cache->remove($cacheKey);
            $this->resource->delete($price);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($id)
    {
        $priceToDelete = $this->getById($id);
        return $this->delete($priceToDelete);
    }
}
