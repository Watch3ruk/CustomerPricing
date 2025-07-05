<?php
spl_autoload_register(function ($class) {
    $prefixes = [
        'TR\\CustomerPricing\\' => [__DIR__ . '/../', __DIR__ . '/Stubs/TR/CustomerPricing/'],
        'Magento\\Framework\\' => [__DIR__ . '/Stubs/Magento/Framework/'],
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        foreach ((array)$baseDir as $dir) {
            $file = $dir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});
?>
