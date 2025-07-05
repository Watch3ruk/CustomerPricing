<?php
namespace TR\CustomerPricing\Controller\Price;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session as CustomerSession;
use TR\CustomerPricing\Api\PriceRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Get implements HttpGetActionInterface
{
    private $jsonFactory;
    private $request;
    private $customerSession;
    private $priceRepository;
    private $priceCurrency;

    public function __construct(
        JsonFactory $jsonFactory,
        RequestInterface $request,
        CustomerSession $customerSession,
        PriceRepositoryInterface $priceRepository,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->priceRepository = $priceRepository;
        $this->priceCurrency = $priceCurrency;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $skus = $this->request->getParam('skus', []);
        
        $priceData = [];

        if (!empty($skus) && $this->customerSession->isLoggedIn()) {
            $customerCodeAttr = $this->customerSession->getCustomerData()->getCustomAttribute('accord_customer_code');
            if ($customerCodeAttr && $customerCodeAttr->getValue()) {
                $customerCode = $customerCodeAttr->getValue();
                
                foreach ($skus as $sku) {
                    $customPrice = $this->priceRepository->getPriceByCodeAndSku($customerCode, $sku);
                    if ($customPrice && $customPrice->getId()) {
                        $price = (float)$customPrice->getPrice();
                        $priceData[$sku] = [
                            'price' => $price,
                            'formatted' => $this->priceCurrency->convertAndFormat($price, false)
                        ];
                    }
                }
            }
        }

        return $result->setData($priceData);
    }
}