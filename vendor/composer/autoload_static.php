<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3fe85f86ff701f6bd7fd7d7a118b5533
{
    public static $files = array (
        '1fcaacb5a22154c13db6129480b5e77c' => __DIR__ . '/..' . '/shoppingfeed/php-feed-generator/src/functions.php',
        'f33272d63e9a2d09c99203a9a97f54b1' => __DIR__ . '/..' . '/prefixed/guzzlehttp/guzzle/src/functions_include.php',
        '22c9b56222bf22d246f83bcbd185cd63' => __DIR__ . '/..' . '/prefixed/guzzlehttp/psr7/src/functions_include.php',
        '3e2b664baaaecea236641ee09d13e255' => __DIR__ . '/..' . '/prefixed/guzzlehttp/promises/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'ShoppingfeedClasslib\\' => 21,
            'ShoppingfeedAddon\\' => 18,
            'ShoppingFeed\\Sdk\\' => 17,
            'ShoppingFeed\\Feed\\' => 18,
            'SfGuzzle\\GuzzleHttp\\Psr7\\' => 25,
            'SfGuzzle\\GuzzleHttp\\Promise\\' => 28,
            'SfGuzzle\\GuzzleHttp\\' => 20,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ShoppingfeedClasslib\\' => 
        array (
            0 => __DIR__ . '/..' . '/totpsclasslib/src',
        ),
        'ShoppingfeedAddon\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'ShoppingFeed\\Sdk\\' => 
        array (
            0 => __DIR__ . '/..' . '/prefixed/shoppingfeed/php-sdk/src',
        ),
        'ShoppingFeed\\Feed\\' => 
        array (
            0 => __DIR__ . '/..' . '/shoppingfeed/php-feed-generator/src',
        ),
        'SfGuzzle\\GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/prefixed/guzzlehttp/psr7/src',
        ),
        'SfGuzzle\\GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/prefixed/guzzlehttp/promises/src',
        ),
        'SfGuzzle\\GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/prefixed/guzzlehttp/guzzle/src',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
    );

    public static $classMap = array (
        'AdminShoppingfeedAccountSettingsController' => __DIR__ . '/../..' . '/controllers/admin/AdminShoppingfeedAccountSettings.php',
        'AdminShoppingfeedFaqController' => __DIR__ . '/../..' . '/controllers/admin/AdminShoppingfeedFaq.php',
        'AdminShoppingfeedGeneralSettingsController' => __DIR__ . '/../..' . '/controllers/admin/AdminShoppingfeedGeneralSettings.php',
        'AdminShoppingfeedOrderImportRulesController' => __DIR__ . '/../..' . '/controllers/admin/AdminShoppingfeedOrderImportRules.php',
        'AdminShoppingfeedOrdersController' => __DIR__ . '/../..' . '/controllers/admin/AdminShoppingfeedOrders.php',
        'AdminShoppingfeedProcessLoggerController' => __DIR__ . '/../..' . '/controllers/admin/AdminShoppingfeedProcessLogger.php',
        'AdminShoppingfeedProcessMonitorController' => __DIR__ . '/../..' . '/controllers/admin/AdminShoppingfeedProcessMonitor.php',
        'ShoppingfeedAdminController' => __DIR__ . '/../..' . '/classes/ShoppingfeedAdminController.php',
        'ShoppingfeedApi' => __DIR__ . '/../..' . '/classes/ShoppingfeedApi.php',
        'ShoppingfeedCarrier' => __DIR__ . '/../..' . '/classes/ShoppingfeedCarrier.php',
        'ShoppingfeedCronController' => __DIR__ . '/../..' . '/classes/ShoppingfeedCronController.php',
        'ShoppingfeedOrder' => __DIR__ . '/../..' . '/classes/ShoppingfeedOrder.php',
        'ShoppingfeedOrderImportActions' => __DIR__ . '/../..' . '/classes/actions/ShoppingfeedOrderImportActions.php',
        'ShoppingfeedOrderSyncActions' => __DIR__ . '/../..' . '/classes/actions/ShoppingfeedOrderSyncActions.php',
        'ShoppingfeedPaymentModule' => __DIR__ . '/../..' . '/classes/ShoppingfeedPaymentModule.php',
        'ShoppingfeedPreloading' => __DIR__ . '/../..' . '/classes/ShoppingfeedPreloading.php',
        'ShoppingfeedProduct' => __DIR__ . '/../..' . '/classes/ShoppingfeedProduct.php',
        'ShoppingfeedProductModuleFrontController' => __DIR__ . '/../..' . '/controllers/front/product.php',
        'ShoppingfeedProductSyncActions' => __DIR__ . '/../..' . '/classes/actions/ShoppingfeedProductSyncActions.php',
        'ShoppingfeedProductSyncPreloadingActions' => __DIR__ . '/../..' . '/classes/actions/ShoppingfeedProductSyncPreloadingActions.php',
        'ShoppingfeedProductSyncPriceActions' => __DIR__ . '/../..' . '/classes/actions/ShoppingfeedProductSyncPriceActions.php',
        'ShoppingfeedProductSyncStockActions' => __DIR__ . '/../..' . '/classes/actions/ShoppingfeedProductSyncStockActions.php',
        'ShoppingfeedSyncAllModuleFrontController' => __DIR__ . '/../..' . '/controllers/front/syncAll.php',
        'ShoppingfeedSyncOrderModuleFrontController' => __DIR__ . '/../..' . '/controllers/front/syncOrder.php',
        'ShoppingfeedSyncProductModuleFrontController' => __DIR__ . '/../..' . '/controllers/front/syncProduct.php',
        'ShoppingfeedTaskOrder' => __DIR__ . '/../..' . '/classes/ShoppingfeedTaskOrder.php',
        'ShoppingfeedToken' => __DIR__ . '/../..' . '/classes/ShoppingfeedToken.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3fe85f86ff701f6bd7fd7d7a118b5533::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3fe85f86ff701f6bd7fd7d7a118b5533::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3fe85f86ff701f6bd7fd7d7a118b5533::$classMap;

        }, null, ClassLoader::class);
    }
}
