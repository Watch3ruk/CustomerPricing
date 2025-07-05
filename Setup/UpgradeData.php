<?php
namespace TR\CustomerPricing\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Eav\Model\Config;

class UpgradeData implements UpgradeDataInterface
{
    private $customerSetupFactory;
    private $eavConfig;

    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        Config $eavConfig
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->eavConfig = $eavConfig;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->addAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                'last_price_sync_at',
                [
                    'type' => 'datetime', 'label' => 'Last Price Sync At', 'input' => 'date',
                    'required' => false, 'visible' => true, 'user_defined' => true,
                    'system' => 0, 'position' => 999
                ]
            );
            $attribute = $this->eavConfig->getAttribute('customer', 'last_price_sync_at');
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();
        }
        $setup->endSetup();
    }
}