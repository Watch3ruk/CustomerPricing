# Customer Pricing Module

This Magento 2 module provides functionality for synchronising customer specific pricing from the Accord API. It includes several console commands for working with pricing data.

## Commands

### Import Prices

```
bin/magento tr:prices:sync <customer_code>
```

Synchronise pricing for a specific customer code. The command retrieves prices from the Accord API and stores them in the `tr_customer_pricing` table.

### Process Chunk

```
bin/magento tr:prices:process-chunk <customer_ids>
```

Worker command used for processing a comma-separated list of customer entity IDs.
