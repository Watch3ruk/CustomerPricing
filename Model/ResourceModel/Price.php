<?php
namespace TR\CustomerPricing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Price extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('tr_customer_pricing', 'entity_id');
    }
}