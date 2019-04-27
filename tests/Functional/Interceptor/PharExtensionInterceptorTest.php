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

use TYPO3\PharStreamWrapper\Helper;
use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\Phar\Reader;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

class PharExtensionInterceptorTest extends AbstractTestCase
{
    /**
     * @var string[]
     */
    protected $allowedPaths = [
        __DIR__ . '/../Fixtures/bundle.phar',
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
        __DIR__ . '/../Fixtures/bundle.phar.png',
        __DIR__ . '/../Fixtures/compromised.phar.png',
    ];

    /**
     * @var int
     */
    const EXPECTED_EXCEPTION_CODE = 1535198703;

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

    /**
     * @return array
     */
    public function cliToolCommandDataProvider(): array
    {
        $fixtureDirectory = dirname(__DIR__) . '/Fixtures';
        return $this->inflateDataSet([
            // add ' --plain' in order to disable PharStreamWrapper in CLI tool
            $fixtureDirectory . '/cli-tool.phar',
            $fixtureDirectory . '/cli-tool',
        ]);
    }

    /**
     * @param string $command
     *
     * @test
     * @dataProvider cliToolCommandDataProvider
     */
    public function cliToolIsExecuted(string $command)
    {
        $descriptorSpecifications = [
            ['pipe', 'r'], // STDIN -> process
            ['pipe', 'w'], // STDOUT <- process
            ['pipe', 'a'], // STDERR
        ];
        $process = proc_open('php ' . $command, $descriptorSpecifications, $pipes);
        static::assertInternalType('resource', $process);

        $read = [$pipes[1], $pipes[2]]; // reading from process' STDOUT & STDERR
        $write = null;
        $except = null;
        // there must be some response at least after 3 seconds
        $events = stream_select($read, $write, $except, 3);
        static::assertGreaterThan(0, $events);

        $response = stream_get_contents($pipes[1]);
        if (stripos($response, 'error')) {
            static::fail($response);
        }

        static::assertSame([
            '__wrapped' => true,
            '__self' => 'TYPO3 demo text file.',
            '__alias' => 'TYPO3 demo text file.',
            'bundle.phar' => 'TYPO3 demo text file.',
        ], json_decode($response, true));
    }

    /**
     * @return array
     */
    public function isFileSystemInvocationAcceptableDataProvider(): array
    {
        $fixturePath = __DIR__ . '/../Fixtures';

        return [
            'include phar' => [
                $fixturePath . '/geoip2.phar',
                // Reader invocations: jise one for alias
                [Helper::class . '::determineBaseFile' => 1, Reader::class . '->resolveContainer' => 1]
            ],
            'include autoloader' => [
                'phar://' . $fixturePath . '/geoip2.phar/vendor/autoload.php',
                // Reader invocations: jise one for alias
                [Helper::class . '::determineBaseFile' => 1, Reader::class . '->resolveContainer' => 1]
            ],
        ];
    }
}
