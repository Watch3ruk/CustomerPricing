<?php
namespace TR\CustomerPricing\Block\Price\Renderer;

use Magento\Catalog\Pricing\Render\FinalPriceBox;

class Custom extends FinalPriceBox
{
    protected function _toHtml()
    {
        // custom HTML for price
        return '<div class="custom-price">' . parent::_toHtml() . '</div>';
    }
}