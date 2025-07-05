<?php
namespace TR\CustomerPricing\Api\Data;

interface PriceInterface
{
    const ENTITY_ID = 'entity_id';
    const ACCORD_CUSTOMER_CODE = 'accord_customer_code';
    const SKU = 'sku';
    const PRICE = 'price';

    public function getId();
    public function getAccordCustomerCode();
    public function setAccordCustomerCode($code);
    public function getSku();
    public function setSku($sku);
    public function getPrice();
    public function setPrice($price);
}