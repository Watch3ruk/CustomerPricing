<?php
namespace TR\CustomerPricing\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Config;

class InstallData implements InstallDataInterface
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

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        
        $customerSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'last_price_sync_at',
            [
                'type' => 'datetime',
                'label' => 'Last Price Sync At',
                'input' => 'date',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'system' => 0,
                'position' => 999,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
            ]
        );

        $attribute = $this->eavConfig->getAttribute('customer', 'last_price_sync_at');
        $attribute->setData('used_in_forms', ['adminhtml_customer']);
        $attribute->save();

        $setup->endSetup();
    }
}