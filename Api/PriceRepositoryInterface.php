<?php
namespace TR\CustomerPricing\Api;

use TR\CustomerPricing\Api\Data\PriceInterface;

interface PriceRepositoryInterface
{
    public function save(PriceInterface $price);
    public function getById($id);
    public function getPriceByCodeAndSku($customerCode, $sku);
    public function getPricesByCodeAndSkus($customerCode, array $skus);
    public function delete(PriceInterface $price);
    public function deleteById($id);
}