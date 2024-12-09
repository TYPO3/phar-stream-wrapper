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

use TYPO3\PharStreamWrapper\Behavior;
use TYPO3\PharStreamWrapper\Helper;
use TYPO3\PharStreamWrapper\Interceptor\PharMetaDataInterceptor;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\Phar\Reader;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

class PharMetaDataInterceptorTest extends AbstractTestCase
{
    /**
     * @var string[]
     */
    protected $allowedPaths = [
        __DIR__ . '/../Fixtures/bundle.phar',
        __DIR__ . '/../Fixtures/bundle.phar.gz',
        __DIR__ . '/../Fixtures/bundle.phar.bz2',
        __DIR__ . '/../Fixtures/bundle.phar.png',
        __DIR__ . '/../Fixtures/Source/../bundle.phar',
    ];

    /**
     * @var string[]
     */
    protected $allowedAliasedPaths = [
        __DIR__ . '/../Fixtures/geoip2.phar',
        __DIR__ . '/../Fixtures/alias-no-path.phar',
        __DIR__ . '/../Fixtures/alias-with-path.phar',
    ];

    /**
     * @var string[]
     */
    protected $deniedPaths = [
        __DIR__ . '/../Fixtures/compromised.phar',
        __DIR__ . '/../Fixtures/compromised.phar.gz',
        __DIR__ . '/../Fixtures/compromised.phar.bz2',
        __DIR__ . '/../Fixtures/compromised.phar.png',
        __DIR__ . '/../Fixtures/compromised.phar.gz.png',
        __DIR__ . '/../Fixtures/compromised.phar.bz2.png',
        __DIR__ . '/../Fixtures/compromised.phar/../bundle.phar',
    ];

    /**
     * @var int
     */
    const EXPECTED_EXCEPTION_CODE = 1539632368;

    protected function setUp()
    {
        if (!in_array('phar', stream_get_wrappers())) {
            $this->markTestSkipped('Phar stream wrapper is not registered');
        }

        stream_wrapper_unregister('phar');
        stream_wrapper_register('phar', PharStreamWrapper::class);

        Manager::initialize(
            (new Behavior())
                ->withAssertion(new PharMetaDataInterceptor())
        );
    }

    protected function tearDown()
    {
        stream_wrapper_restore('phar');
        Manager::destroy();
    }

    public function isFileSystemInvocationAcceptableDataProvider(): array
    {
        $fixturePath = __DIR__ . '/../Fixtures';

        return [
            'include phar' => [
                $fixturePath . '/geoip2.phar',
                // Reader invocations: one for alias, one for meta-data
                [Helper::class . '::determineBaseFile' => 1, Reader::class . '->resolveContainer' => 2]
            ],
            'include autoloader' => [
                'phar://' . $fixturePath . '/geoip2.phar/vendor/autoload.php',
                // Reader invocations: one for alias, one for meta-data
                [Helper::class . '::determineBaseFile' => 1, Reader::class . '->resolveContainer' => 2]
            ],
        ];
    }
}
