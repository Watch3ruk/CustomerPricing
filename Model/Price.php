<?php
namespace TR\CustomerPricing\Model;

use Magento\Framework\Model\AbstractModel;
use TR\CustomerPricing\Api\Data\PriceInterface;
use TR\CustomerPricing\Model\ResourceModel\Price as PriceResourceModel;

class Price extends AbstractModel implements PriceInterface
{
    protected function _construct()
    {
        $this->_init(PriceResourceModel::class);
    }

    public function getAccordCustomerCode()
    {
        return $this->getData(self::ACCORD_CUSTOMER_CODE);
    }

    public function setAccordCustomerCode($code)
    {
        return $this->setData(self::ACCORD_CUSTOMER_CODE, $code);
    }

    public function getSku()
    {
        return $this->getData(self::SKU);
    }

    public function setSku($sku)
    {
        return $this->setData(self::SKU, $sku);
    }



    public function getPrice()
    {
        return $this->getData(self::PRICE);
    }

    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }
}