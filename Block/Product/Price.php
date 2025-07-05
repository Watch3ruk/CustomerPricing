<?php
namespace TR\CustomerPricing\Block\Product;

use Magento\Catalog\Block\Product\Price as BasePrice;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Json\EncoderInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Math\Random;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Model\Product;

class Price extends BasePrice
{
    /** @var CustomerSession */
    private $customerSession;

    public function __construct(
        Context          $context,
        EncoderInterface $jsonEncoder,
        CatalogHelper    $catalogHelper,
        Registry         $registry,
        StringUtils      $stringUtils,
        Random           $mathRandom,
        CartHelper       $cartHelper,
        CustomerSession  $customerSession,
        array            $data = []
    ) {
        $this->customerSession = $customerSession;
        parent::__construct(
            $context,
            $jsonEncoder,
            $catalogHelper,
            $registry,
            $stringUtils,
            $mathRandom,
            $cartHelper,
            $data
        );
    }

    public function getCacheKeyInfo(): array
    {
        $key = parent::getCacheKeyInfo();
        $key[] = $this->customerSession->isLoggedIn()
            ? $this->customerSession->getCustomerId()
            : 'guest';
        return $key;
    }

public function getPriceHtml(Product $product = null): string
{
    $result = parent::getPriceHtml($product ?: $this->getProduct());
    return $result !== null ? $result : '';
}
}
