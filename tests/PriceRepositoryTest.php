<?php
use PHPUnit\Framework\TestCase;
use Tests\Stubs\Cache;
use Tests\Stubs\Serializer;
use TR\CustomerPricing\Model\PriceFactory;
use TR\CustomerPricing\Model\ResourceModel\Price\CollectionFactory;
use TR\CustomerPricing\Model\ResourceModel\Price as PriceResource;
use TR\CustomerPricing\Model\PriceRepository;

class PriceRepositoryTest extends TestCase
{
    public function testGetPricesByCodeAndSkusReturnsDataForMultipleSkus()
    {
        $items = [
            [\TR\CustomerPricing\Api\Data\PriceInterface::ACCORD_CUSTOMER_CODE => 'C001', \TR\CustomerPricing\Api\Data\PriceInterface::SKU => 'sku1', \TR\CustomerPricing\Api\Data\PriceInterface::PRICE => 10, \TR\CustomerPricing\Api\Data\PriceInterface::ENTITY_ID => 1],
            [\TR\CustomerPricing\Api\Data\PriceInterface::ACCORD_CUSTOMER_CODE => 'C001', \TR\CustomerPricing\Api\Data\PriceInterface::SKU => 'sku2', \TR\CustomerPricing\Api\Data\PriceInterface::PRICE => 20, \TR\CustomerPricing\Api\Data\PriceInterface::ENTITY_ID => 2],
            [\TR\CustomerPricing\Api\Data\PriceInterface::ACCORD_CUSTOMER_CODE => 'C002', \TR\CustomerPricing\Api\Data\PriceInterface::SKU => 'sku3', \TR\CustomerPricing\Api\Data\PriceInterface::PRICE => 30, \TR\CustomerPricing\Api\Data\PriceInterface::ENTITY_ID => 3],
        ];

        $repo = new PriceRepository(
            new PriceResource(),
            new PriceFactory(),
            new \TR\CustomerPricing\Model\ResourceModel\Price\CollectionFactory($items),
            new Cache(),
            new Serializer()
        );

        $prices = $repo->getPricesByCodeAndSkus('C001', ['sku1','sku2']);

        $this->assertCount(2, $prices);
        $this->assertEquals(10, $prices['sku1']->getPrice());
        $this->assertEquals(20, $prices['sku2']->getPrice());
    }
}
