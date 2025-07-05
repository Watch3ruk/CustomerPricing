<?php
namespace TR\CustomerPricing\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use TR\CustomerPricing\Api\PriceRepositoryInterface;
use Psr\Log\LoggerInterface;

class SetCustomPriceObserver implements ObserverInterface
{
    private $customerSession;
    private $checkoutSession;
    private $priceRepository;
    private $logger;

    public function __construct(
        CustomerSession         $customerSession,
        CheckoutSession         $checkoutSession,
        PriceRepositoryInterface $priceRepository,
        LoggerInterface         $logger
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->priceRepository = $priceRepository;
        $this->logger          = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return;
            }

            $accountCodeAttr = $this->customerSession
                                    ->getCustomerData()
                                    ->getCustomAttribute('accord_customer_code');
            if (!$accountCodeAttr) {
                return;
            }
            $customerCode = $accountCodeAttr->getValue();

            /** @var \Magento\Quote\Model\Quote\Item $item */
            $item = $observer->getEvent()->getQuoteItem();
            $item = $item->getParentItem() ?: $item;

            $sku = $item->getProduct()->getSku();
            $custom = $this->priceRepository
                           ->getPriceByCodeAndSku($customerCode, $sku);

            if ($custom && $custom->getId()) {
                $price = (float)$custom->getPrice();

                // stamp the item
                $item->setCustomPrice($price);
                $item->setOriginalCustomPrice($price);
                $item->getProduct()->setIsSuperMode(true);

                // **Removed to avoid interrupting add-to-cart flow:**
                // $quote = $item->getQuote();
                // $quote->setTotalsCollectedFlag(false)
                //       ->collectTotals()
                //       ->save();
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'TR_CustomerPricing Observer error: ' . $e->getMessage()
            );
        }
    }
}