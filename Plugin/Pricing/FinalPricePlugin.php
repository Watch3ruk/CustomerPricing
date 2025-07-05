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
        $sku = $subject->getProduct()->getSku();
        $this->logger->info("--- [SKU: {$sku}] Debugging FinalPricePlugin ---");

        if (!$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            $this->logger->info("[SKU: {$sku}] Exiting: Customer not logged in.");
            return $result;
        }
        $this->logger->info("[SKU: {$sku}] Check PASSED: Customer is logged in.");

        try {
            $customerData = $this->customerSession->getCustomerData();
            if (!$customerData) {
                $this->logger->info("[SKU: {$sku}] Exiting: No customer data available in session.");
                return $result;
            }

            $customerCodeAttr = $customerData->getCustomAttribute('accord_customer_code');

            if (!$customerCodeAttr || !$customerCodeAttr->getValue()) {
                $this->logger->info("[SKU: {$sku}] Exiting: 'accord_customer_code' not found in customer session data.");
                return $result;
            }
            
            $customerCode = $customerCodeAttr->getValue();
            $this->logger->info("[SKU: {$sku}] Check PASSED: Found customer code '{$customerCode}'.");
            
            $customPrice = $this->priceRepository->getPriceByCodeAndSku($customerCode, $sku);

            if ($customPrice && $customPrice->getId()) {
                $price = (float)$customPrice->getPrice();
                $this->logger->info("[SKU: {$sku}] SUCCESS: Found custom price '{$price}'. Overriding original price.");
                return $price;
            } else {
                $this->logger->info("[SKU: {$sku}] Exiting: No custom price found in the database for code '{$customerCode}' and SKU '{$sku}'.");
            }

        } catch (\Exception $e) {
            $this->logger->error("[SKU: {$sku}] ERROR: " . $e->getMessage());
        }

        $this->logger->info("[SKU: {$sku}] Final Exit: Returning original price.");
        return $result;
    }
}