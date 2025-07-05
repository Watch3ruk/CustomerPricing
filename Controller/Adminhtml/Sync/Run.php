<?php
namespace TR\CustomerPricing\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use TR\CustomerPricing\Service\PriceSync;

class Run extends Action
{
    public function execute()
    {
        try {
            $this->_objectManager->get(PriceSync::class)->syncAllCustomers();
            $this->messageManager->addSuccessMessage(__('Sync completed.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $this->_redirect('customerpricing/manage/index');
    }
}