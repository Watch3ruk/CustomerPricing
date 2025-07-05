<?php
namespace TR\CustomerPricing\Model\ResourceModel\Price;
class DummyCollection extends Collection {
    private $item;
    public function __construct($item) { $this->item = $item; }
    public function addFieldToFilter($field, $value) { return $this; }
    public function getFirstItem() { return $this->item; }
}
?>
