<?php
namespace TR\CustomerPricing\Plugin\Quote\Item;

use Magento\Quote\Model\Quote\Item as QuoteItem;

class PricePlugin
{
    /**
     * If a custom price has been stamped on the item, return that instead
     * of the original price.
     */
    public function aroundGetPrice(QuoteItem $subject, \Closure $proceed)
    {
        $custom = $subject->getCustomPrice();
        if ($custom !== null) {
            return (float)$custom;
        }
        return $proceed();
    }

    /**
     * Same for the base-currency price.
     */
    public function aroundGetBasePrice(QuoteItem $subject, \Closure $proceed)
    {
        $custom = $subject->getCustomPrice();
        if ($custom !== null) {
            // assume base amount is same numeric value; adjust if you need currency conversion
            return (float)$custom;
        }
        return $proceed();
    }
}