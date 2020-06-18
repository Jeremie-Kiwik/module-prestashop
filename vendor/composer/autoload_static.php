<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3fe85f86ff701f6bd7fd7d7a118b5533
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '25072dd6e2470089de65ae7bf11d3109' => __DIR__ . '/..' . '/symfony/polyfill-php72/bootstrap.php',
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        'a0edc8309cc5e1d60e3047b5df6b7052' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/functions_include.php',
        'f598d06aa772fa33d905e87be6398fb1' => __DIR__ . '/..' . '/symfony/polyfill-intl-idn/bootstrap.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
        '1fcaacb5a22154c13db6129480b5e77c' => __DIR__ . '/..' . '/shoppingfeed/php-feed-generator/src/functions.php',
        'f33272d63e9a2d09c99203a9a97f54b1' => __DIR__ . '/..' . '/prefixed/guzzlehttp/guzzle/src/functions_include.php',
        '22c9b56222bf22d246f83bcbd185cd63' => __DIR__ . '/..' . '/prefixed/guzzlehttp/psr7/src/functions_include.php',
        '3e2b664baaaecea236641ee09d13e255' => __DIR__ . '/..' . '/prefixed/guzzlehttp/promises/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Php72\\' => 23,
            'Symfony\\Polyfill\\Mbstring\\' => 26,
            'Symfony\\Polyfill\\Intl\\Idn\\' => 26,
            'ShoppingfeedClasslib\\' => 21,
            'ShoppingfeedAddon\\' => 18,
            'ShoppingFeed\\Sdk\\Test\\' => 22,
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
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Polyfill\\Php72\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-php72',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Symfony\\Polyfill\\Intl\\Idn\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-intl-idn',
        ),
        'ShoppingfeedClasslib\\' => 
        array (
            0 => __DIR__ . '/..' . '/totpsclasslib/src',
        ),
        'ShoppingfeedAddon\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'ShoppingFeed\\Sdk\\Test\\' => 
        array (
            0 => __DIR__ . '/..' . '/shoppingfeed/php-sdk/tests/unit',
        ),
        'ShoppingFeed\\Sdk\\' => 
        array (
            0 => __DIR__ . '/..' . '/prefixed/shoppingfeed/php-sdk/src',
            1 => __DIR__ . '/..' . '/shoppingfeed/php-sdk/src',
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
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3fe85f86ff701f6bd7fd7d7a118b5533::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3fe85f86ff701f6bd7fd7d7a118b5533::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
