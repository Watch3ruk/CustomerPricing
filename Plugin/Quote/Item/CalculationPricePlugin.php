<?php
namespace TR\CustomerPricing\Plugin\Quote\Item;

use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Customer\Model\Session as CustomerSession;
use TR\CustomerPricing\Api\PriceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config as TaxConfig;

class CalculationPricePlugin
{
    private $customerSession;
    private $priceRepository;
    private $logger;
    private $taxCalculation;
    private $taxConfig;
    private $storeManager;

    public function __construct(
        CustomerSession          $customerSession,
        PriceRepositoryInterface $priceRepository,
        LoggerInterface          $logger,
        TaxCalculation           $taxCalculation,
        TaxConfig                $taxConfig,
        StoreManagerInterface    $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->priceRepository = $priceRepository;
        $this->logger          = $logger;
        $this->taxCalculation  = $taxCalculation;
        $this->taxConfig       = $taxConfig;
        $this->storeManager    = $storeManager;
    }

    /**
     * @param QuoteItem $subject
     * @param float     $result  Original price (usually excl. tax)
     * @return float
     */
    public function afterGetCalculationPrice(QuoteItem $subject, $result)
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $result;
            }
            $custAttr = $this->customerSession
                             ->getCustomerData()
                             ->getCustomAttribute('accord_customer_code');
            if (!$custAttr) {
                return $result;
            }
            $customerCode = $custAttr->getValue();
            $product      = $subject->getProduct();
            $sku          = $product->getSku();

            $customPrice = $this->priceRepository
                                ->getPriceByCodeAndSku($customerCode, $sku);

            if ($customPrice && $customPrice->getId()) {
                // **Your custom price from the table (assumed excl. VAT)**
                $netPrice = (float)$customPrice->getPrice();

                // --- If instead your custom price IS VAT-INCLUDED, uncomment this block: ---
                /*
                $store = $this->storeManager->getStore();
                $customerTaxClass = $this->customerSession
                                        ->getCustomerData()
                                        ->getCustomAttribute('tax_class_id')
                                        ->getValue();
                $productTaxClass  = $product->getTaxClassId();

                $taxRate = $this->taxCalculation
                                ->getRate($productTaxClass, $customerTaxClass, $store);

                // Calculate price excluding tax from your VAT-included price
                $netPrice = $this->taxCalculation
                                 ->calcPriceExcludeTax($netPrice, $taxRate, false);
                */

                // Stamp the quote item so it uses *net* price,
                // allowing Magento to add VAT later
                $subject->setCustomPrice($netPrice);
                $subject->setOriginalCustomPrice($netPrice);
                $subject->getProduct()->setIsSuperMode(true);

                return $netPrice;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'TR_CustomerPricing: error in CalculationPricePlugin: ' 
                . $e->getMessage()
            );
        }

        return $result;
    }

    public function afterGetBaseCalculationPrice(QuoteItem $subject, $result)
    {
        // Mirror the same logic in base currency
        return $this->afterGetCalculationPrice($subject, $result);
    }
}