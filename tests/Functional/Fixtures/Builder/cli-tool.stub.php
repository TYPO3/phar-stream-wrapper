#!/usr/bin/php
<?php
use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

// to be written to tests/Functional/Fixtures
require_once(__DIR__ . '/../../../vendor/autoload.php');

$options = getopt('', ['plain']);

if (!isset($options['plain'])) {
    $wrapped = true;
    stream_wrapper_unregister('phar');
    stream_wrapper_register('phar', PharStreamWrapper::class);

    Manager::initialize(
        (new \TYPO3\PharStreamWrapper\Behavior())
            ->withAssertion(new PharExtensionInterceptor())
    );
}

\Phar::mapPhar('self');
echo json_encode([
    '__wrapped' => !empty($wrapped),
    '__self' => @file_get_contents('phar://' . __FILE__ . '/Resources/content.txt'),
    '__alias' => @file_get_contents('phar://self/Resources/content.txt'),
    'bundle.phar' => @file_get_contents('phar://' . __DIR__ . '/bundle.phar/Resources/content.txt'),
]);
__HALT_COMPILER();