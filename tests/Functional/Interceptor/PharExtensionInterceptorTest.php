<?php
declare(strict_types = 1);
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

use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

class PharExtensionInterceptorTest extends AbstractTestCase
{
    /**
     * @var string[]
     */
    protected const ALLOWED_PATHS = [
        __DIR__ . '/../Fixtures/bundle.phar',
    ];

    /**
     * @var string[]
     */
    protected const DENIED_PATHS = [
        __DIR__ . '/../Fixtures/bundle.phar.png',
    ];

    /**
     * @var int
     */
    protected const EXPECTED_EXCEPTION_CODE = 1535198703;

    protected function setUp()
    {
        parent::setUp();

        if (!in_array('phar', stream_get_wrappers())) {
            $this->markTestSkipped('Phar stream wrapper is not registered');
        }

        stream_wrapper_unregister('phar');
        stream_wrapper_register('phar', PharStreamWrapper::class);

        Manager::initialize(
            (new \TYPO3\PharStreamWrapper\Behavior())
                ->withAssertion(new PharExtensionInterceptor())
        );
    }

    protected function tearDown()
    {
        stream_wrapper_restore('phar');
        Manager::destroy();
        parent::tearDown();
    }
}
