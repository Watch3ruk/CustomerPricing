<?php
namespace TR\CustomerPricing\Plugin\Pricing;

use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;
use TR\CustomerPricing\Api\PriceRepositoryInterface;
use Psr\Log\LoggerInterface;

class FinalPricePlugin
{
    private $customerSession;
    private $priceRepository;
    private $logger;
    private $httpContext;

    public function __construct(
        CustomerSession $customerSession,
        PriceRepositoryInterface $priceRepository,
        LoggerInterface $logger,
        HttpContext $httpContext
    ) {
        $this->customerSession = $customerSession;
        $this->priceRepository = $priceRepository;
        $this->logger = $logger;
        $this->httpContext = $httpContext;
    }

    public function afterGetValue(FinalPrice $subject, $result)
    {
        if (!$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return $result;
        }

        try {
            $customerCodeAttr = $this->customerSession->getCustomerData()->getCustomAttribute('accord_customer_code');
            if (!$customerCodeAttr) {
                return $result;
            }

            $customerCode = $customerCodeAttr->getValue();
            $sku = $subject->getProduct()->getSku();
            
            $customPrice = $this->priceRepository->getPriceByCodeAndSku($customerCode, $sku);

            if ($customPrice && $customPrice->getId()) {
                return (float)$customPrice->getPrice();
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in FinalPricePlugin: ' . $e->getMessage());
        }

        return $result;
    }
}