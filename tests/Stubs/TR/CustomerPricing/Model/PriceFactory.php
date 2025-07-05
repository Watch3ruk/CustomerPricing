<?php
namespace TR\CustomerPricing\Model;
class PriceFactory {
    public function create(array $data = []) {
        return new Price($data);
    }
}
?>
