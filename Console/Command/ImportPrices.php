<?php
namespace TR\CustomerPricing\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Adlab\Accord\ApiConnector\ProductsIntialiser as ApiConnector;
use TR\CustomerPricing\Api\PriceRepositoryInterface;
use TR\CustomerPricing\Api\Data\PriceInterfaceFactory;

class ImportPrices extends Command
{
    private $appState;
    private $priceRepository;
    private $priceFactory;
    private $apiConnector;

    public function __construct(
        AppState $appState,
        PriceRepositoryInterface $priceRepository,
        PriceInterfaceFactory $priceFactory,
        ApiConnector $apiConnector
    ) {
        $this->appState = $appState;
        $this->priceRepository = $priceRepository;
        $this->priceFactory = $priceFactory;
        $this->apiConnector = $apiConnector;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('tr:prices:sync')
             ->setDescription('Sync custom prices from Accord API for a given customer code.')
             ->addArgument('customer_code', InputArgument::REQUIRED, 'The accord_customer_code to sync prices for.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $customerCode = $input->getArgument('customer_code');

        $output->writeln("<info>Starting price sync for customer code: {$customerCode}</info>");

        try {
            $availableSkus = $this->apiConnector->getProductSkus(
                $this->apiConnector->restrictedProducts($customerCode)
            );

            if (empty($availableSkus)) {
                $output->writeln('<comment>No available products found for this customer code.</comment>');
                return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
            }

            $pricesFromApi = $this->apiConnector->initialiseProductPrices($customerCode, $availableSkus);

            if (empty($pricesFromApi)) {
                $output->writeln('<error>Failed to retrieve any prices from the API.</error>');
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }

            $syncedCount = 0;
            foreach ($pricesFromApi as $sku => $price) {
                $priceModel = $this->priceRepository->getPriceByCodeAndSku($customerCode, $sku) ?? $this->priceFactory->create();

                $priceModel->setAccordCustomerCode($customerCode);
                $priceModel->setSku($sku);
                $priceModel->setPrice($price);
                $this->priceRepository->save($priceModel);
                $syncedCount++;
            }

            $output->writeln("<info>Sync finished. {$syncedCount} prices synced for customer {$customerCode}.</info>");
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>An error occurred: ' . $e->getMessage() . '</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }
}