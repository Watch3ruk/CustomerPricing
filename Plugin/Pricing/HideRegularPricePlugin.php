<?php
namespace TR\CustomerPricing\Plugin\Pricing;

use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Customer\Model\Session as CustomerSession;
use TR\CustomerPricing\Api\PriceRepositoryInterface;
use Psr\Log\LoggerInterface;

class HideRegularPricePlugin
{
    private $customerSession;
    private $priceRepository;
    private $logger;

    public function __construct(
        CustomerSession $customerSession,
        PriceRepositoryInterface $priceRepository,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->priceRepository = $priceRepository;
        $this->logger          = $logger;
    }

    /**
     * Only return null (hide) if this customer really has a custom price for this SKU.
     *
     * @param RegularPrice $subject
     * @param float|null   $result
     * @return float|null
     */
    public function afterGetValue(RegularPrice $subject, $result)
    {
        try {
            // only for logged‐in customers
            if (! $this->customerSession->isLoggedIn()) {
                return $result;
            }

            // get customer code
            $custAttr = $this->customerSession
                             ->getCustomerData()
                             ->getCustomAttribute('accord_customer_code');
            if (! $custAttr) {
                return $result;
            }
            $customerCode = $custAttr->getValue();

            // get SKU
            $sku = $subject->getProduct()->getSku();

            // see if we have a custom price
            $customPrice = $this->priceRepository
                                ->getPriceByCodeAndSku($customerCode, $sku);

            if ($customPrice && $customPrice->getId()) {
                // we do—so hide the old price
                return null;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'TR_CustomerPricing HideRegularPricePlugin error: '.$e->getMessage()
            );
        }

        // no custom price—show the normal old price
        return $result;
    }
}