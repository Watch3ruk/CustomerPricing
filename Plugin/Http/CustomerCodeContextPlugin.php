<?php
namespace TR\CustomerPricing\Plugin\Http;

use Magento\Framework\App\Http\Context;
use Magento\Customer\Model\Session as CustomerSession;

class CustomerCodeContextPlugin
{
    /** @var CustomerSession */
    private $customerSession;

    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * Ensure customer code is part of the HTTP context so page cache varies
     * for each logged-in customer.
     */
    public function beforeGetVaryString(Context $subject)
    {
        $code = '';
        if ($this->customerSession->isLoggedIn()) {
            $data = $this->customerSession->getCustomerData();
            if ($data) {
                $attr = $data->getCustomAttribute('accord_customer_code');
                if ($attr) {
                    $code = (string)$attr->getValue();
                }
            }
        }
        $subject->setValue('tr_customer_code', $code, '');
    }
}

