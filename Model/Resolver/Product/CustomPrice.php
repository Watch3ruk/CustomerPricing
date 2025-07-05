<?php
declare(strict_types=1);

namespace TR\CustomerPricing\Model\Resolver\Product;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Model\GroupManagement;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use TR\CustomerPricing\Api\PriceRepositoryInterface;

class CustomPrice implements ResolverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var PriceRepositoryInterface
     */
    private $priceRepository;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param PriceRepositoryInterface $priceRepository
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        PriceRepositoryInterface $priceRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->priceRepository = $priceRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $product = $value['model'];
        $customerId = $context->getUserId();

        // Only return a custom price for logged-in customers
        if (!$customerId || $context->getUserType() !== \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
            return null;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerCodeAttr = $customer->getCustomAttribute('accord_customer_code');

            if (!$customerCodeAttr || !$customerCodeAttr->getValue()) {
                return null;
            }

            $customerCode = $customerCodeAttr->getValue();
            $sku = $product->getSku();

            $customPrice = $this->priceRepository->getPriceByCodeAndSku($customerCode, $sku);

            if ($customPrice && $customPrice->getId()) {
                return [
                    'value' => (float)$customPrice->getPrice(),
                    'currency' => 'USD' // Replace with your store's currency code or get it dynamically
                ];
            }
        } catch (\Exception $e) {
            // Fail silently to avoid breaking the entire GraphQL response
            return null;
        }

        return null;
    }
}
