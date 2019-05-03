<?php
namespace TYPO3\PharStreamWrapper\Tests\Functional\Interceptor;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\PharStreamWrapper\Interceptor\ConjunctionInterceptor;
use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Interceptor\PharMetaDataInterceptor;
use TYPO3\PharStreamWrapper\Manager;

class ConjunctionInterceptorTest extends AbstractTestCase
{
    /**
     * @var int
     */
    const EXPECTED_EXCEPTION_CODE = 1539625084;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->allowedPaths = array(
            __DIR__ . '/../Fixtures/bundle.phar',
            __DIR__ . '/../Fixtures/Source/../bundle.phar',
        );
        $this->allowedAliasedPaths = array(
            __DIR__ . '/../Fixtures/geoip2.phar',
            __DIR__ . '/../Fixtures/alias-no-path.phar',
            __DIR__ . '/../Fixtures/alias-with-path.phar',
        );
        $this->deniedPaths = array(
            __DIR__ . '/../Fixtures/bundle.phar.png',
            __DIR__ . '/../Fixtures/compromised.phar',
            __DIR__ . '/../Fixtures/compromised.phar.gz',
            __DIR__ . '/../Fixtures/compromised.phar.bz2',
            __DIR__ . '/../Fixtures/compromised.phar.png',
            __DIR__ . '/../Fixtures/compromised.phar.gz.png',
            __DIR__ . '/../Fixtures/compromised.phar.bz2.png',
            __DIR__ . '/../Fixtures/bundle.phar.png/../bundle.phar',
            __DIR__ . '/../Fixtures/compromised.phar/../bundle.phar',
            __DIR__ . '/../Fixtures/compromised.phar.png/../bundle.phar',
        );
        parent::__construct($name, $data, $dataName);
    }

    protected function setUp()
    {
        parent::setUp();

        if (!in_array('phar', stream_get_wrappers())) {
            $this->markTestSkipped('Phar stream wrapper is not registered');
        }

        stream_wrapper_unregister('phar');
        stream_wrapper_register('phar', 'TYPO3\\PharStreamWrapper\\PharStreamWrapper');

        $behavior = new \TYPO3\PharStreamWrapper\Behavior();
        Manager::initialize(
            $behavior->withAssertion(new ConjunctionInterceptor(array(
                new PharExtensionInterceptor(),
                new PharMetaDataInterceptor()
            )))
        );
    }

    protected function tearDown()
    {
        stream_wrapper_restore('phar');
        Manager::destroy();
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function isFileSystemInvocationAcceptableDataProvider()
    {
        $fixturePath = __DIR__ . '/../Fixtures';
        return array(
            'include phar' => array(
                $fixturePath . '/geoip2.phar',
                // Reader invocations: one for alias, one for meta-data
                array(
                    'TYPO3\\PharStreamWrapper\\Helper::determineBaseFile' => 1,
                    'TYPO3\\PharStreamWrapper\\Phar\\Reader->resolveContainer' => 2,
                )
            ),
            'include autoloader' => array(
                'phar://' . $fixturePath . '/geoip2.phar/vendor/autoload.php',
                // Reader invocations: one for alias, one for meta-data
                array(
                    'TYPO3\\PharStreamWrapper\\Helper::determineBaseFile' => 1,
                    'TYPO3\\PharStreamWrapper\\Phar\\Reader->resolveContainer' => 2,
                )
            ),
        );
    }
}
