<?php

namespace Magento\Framework\Serialize;
interface SerializerInterface {
    public function serialize($data);
    public function unserialize($string);
}

namespace Magento\Framework\App;
interface CacheInterface {
    public function load($id);
    public function save($data, $id, $tags = [], $lifetime = null);
    public function remove($id);
    public function clean($tags = []);
}

namespace TR\CustomerPricing\Model\ResourceModel;
class Price {}

namespace Tests\Stubs;

use TR\CustomerPricing\Api\Data\PriceInterface;

class Serializer implements \Magento\Framework\Serialize\SerializerInterface {
    public function serialize($data) { return json_encode($data); }
    public function unserialize($string) { return json_decode($string, true); }
}

class Cache implements \Magento\Framework\App\CacheInterface {
    private $data = [];
    public function load($id) { return $this->data[$id] ?? false; }
    public function save($data, $id, $tags = [], $lifetime = null) { $this->data[$id] = $data; return true; }
    public function remove($id) { unset($this->data[$id]); return true; }
    public function clean($tags = []) { return true; }
}

class Price implements PriceInterface {
    private $data = [];
    public function __construct(array $data = []) { $this->data = $data; }
    public function getId() { return $this->data[self::ENTITY_ID] ?? null; }
    public function getAccordCustomerCode() { return $this->data[self::ACCORD_CUSTOMER_CODE] ?? null; }
    public function setAccordCustomerCode($code) { $this->data[self::ACCORD_CUSTOMER_CODE] = $code; return $this; }
    public function getSku() { return $this->data[self::SKU] ?? null; }
    public function setSku($sku) { $this->data[self::SKU] = $sku; return $this; }
    public function getPrice() { return $this->data[self::PRICE] ?? null; }
    public function setPrice($price) { $this->data[self::PRICE] = $price; return $this; }
    public function getData() { return $this->data; }
}

class PriceCollection implements \IteratorAggregate {
    private $items; private $customerCode; private $skuCond;
    public function __construct(array $items) { $this->items = $items; }
    public function addFieldToFilter($field, $condition) {
        if ($field === 'accord_customer_code') { $this->customerCode = $condition; }
        elseif ($field === 'sku') { $this->skuCond = $condition; }
        return $this;
    }
    public function getIterator() {
        $filtered = [];
        foreach ($this->items as $item) {
            if ($this->customerCode !== null && $item[PriceInterface::ACCORD_CUSTOMER_CODE] !== $this->customerCode) continue;
            if ($this->skuCond !== null) {
                if (is_array($this->skuCond)) {
                    if (isset($this->skuCond['in']) && !in_array($item[PriceInterface::SKU], $this->skuCond['in'])) continue;
                    if (!isset($this->skuCond['in']) && $item[PriceInterface::SKU] !== $this->skuCond) continue;
                } elseif ($item[PriceInterface::SKU] !== $this->skuCond) continue;
            }
            $filtered[] = new Price($item);
        }
        return new \ArrayIterator($filtered);
    }
}

namespace TR\CustomerPricing\Model;
class PriceFactory {
    public function create(array $data = []) { if(isset($data['data'])) $data = $data['data']; return new \Tests\Stubs\Price($data); }
}

namespace TR\CustomerPricing\Model\ResourceModel\Price;
class CollectionFactory {
    private $items;
    public function __construct(array $items) { $this->items = $items; }
    public function create() { return new \Tests\Stubs\PriceCollection($this->items); }
}
