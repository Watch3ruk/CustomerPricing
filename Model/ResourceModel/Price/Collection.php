<?php
namespace TR\CustomerPricing\Model\ResourceModel\Price;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TR\CustomerPricing\Model\Price as PriceModel;
use TR\CustomerPricing\Model\ResourceModel\Price as PriceResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(PriceModel::class, PriceResourceModel::class);
    }
}