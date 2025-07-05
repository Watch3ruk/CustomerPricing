<?php
use PHPUnit\Framework\TestCase;
use TR\CustomerPricing\Model\PriceRepository;
use TR\CustomerPricing\Model\ResourceModel\Price as PriceResource;
use TR\CustomerPricing\Model\PriceFactory;
use TR\CustomerPricing\Model\ResourceModel\Price\CollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use TR\CustomerPricing\Model\ResourceModel\Price\DummyCollection;
use TR\CustomerPricing\Model\Price;

class PriceRepositoryTest extends TestCase
{
    public function testGetPriceByCodeAndSkuReturnsNullWhenPriceMissing()
    {
        $resource = $this->createStub(PriceResource::class);
        $priceFactory = $this->createStub(PriceFactory::class);

        $emptyPrice = $this->createStub(Price::class);
        $emptyPrice->method('getId')->willReturn(null);

        $collection = new DummyCollection($emptyPrice);

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->expects($this->once())
                          ->method('create')
                          ->willReturn($collection);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
              ->method('load')
              ->willReturn(false);
        $cache->expects($this->never())
              ->method('save');

        $serializer = $this->createStub(SerializerInterface::class);

        $repo = new PriceRepository($resource, $priceFactory, $collectionFactory, $cache, $serializer);
        $result = $repo->getPriceByCodeAndSku('CODE', 'SKU');
        $this->assertNull($result);
    }
}
?>
