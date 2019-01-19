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

use TYPO3\PharStreamWrapper\Interceptor\PharMetaDataInterceptor;
use TYPO3\PharStreamWrapper\Manager;

class PharMetaDataInterceptorTest extends AbstractTestCase
{
    /**
     * @var int
     */
    const EXPECTED_EXCEPTION_CODE = 1539632368;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->allowedPaths = array(
            __DIR__ . '/../Fixtures/bundle.phar',
            __DIR__ . '/../Fixtures/bundle.phar.png',
        );
        $this->allowedAliasedPaths = array(
            __DIR__ . '/../Fixtures/geoip2.phar',
        );
        $this->deniedPaths = array(
            __DIR__ . '/../Fixtures/serialized.phar',
            __DIR__ . '/../Fixtures/serialized.phar.gz',
            __DIR__ . '/../Fixtures/serialized.phar.bz2',
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
            $behavior->withAssertion(new PharMetaDataInterceptor())
        );
    }

    protected function tearDown()
    {
        stream_wrapper_restore('phar');
        Manager::destroy();
        parent::tearDown();
    }
}
