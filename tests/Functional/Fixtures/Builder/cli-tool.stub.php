#!/usr/bin/php
<?php
use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager;

// to be written to tests/Functional/Fixtures
require_once(__DIR__ . '/../../../vendor/autoload.php');

$options = getopt('', array('plain'));

if (!isset($options['plain'])) {
    $wrapped = true;
    stream_wrapper_unregister('phar');
    stream_wrapper_register('phar', 'TYPO3\\PharStreamWrapper\\PharStreamWrapper');

    $behavior = new \TYPO3\PharStreamWrapper\Behavior();
    Manager::initialize(
        $behavior->withAssertion(new PharExtensionInterceptor())
    );
}

\Phar::mapPhar('self');
echo json_encode(array(
    '__wrapped' => !empty($wrapped),
    '__self' => @file_get_contents('phar://' . __FILE__ . '/Resources/content.txt'),
    '__alias' => @file_get_contents('phar://self/Resources/content.txt'),
    'bundle.phar' => @file_get_contents('phar://' . __DIR__ . '/bundle.phar/Resources/content.txt'),
));
__HALT_COMPILER();