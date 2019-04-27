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

use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager;

class PharExtensionInterceptorTest extends AbstractTestCase
{
    /**
     * @var int
     */
    const EXPECTED_EXCEPTION_CODE = 1535198703;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->allowedPaths = array(
            __DIR__ . '/../Fixtures/bundle.phar',
        );
        $this->allowedAliasedPaths = array(
            __DIR__ . '/../Fixtures/geoip2.phar',
            // __DIR__ . '/../Fixtures/alias-no-path.phar',
            __DIR__ . '/../Fixtures/alias-with-path.phar',
        );
        $this->deniedPaths = array(
            __DIR__ . '/../Fixtures/bundle.phar.png',
            __DIR__ . '/../Fixtures/compromised.phar.png',
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
            $behavior->withAssertion(new PharExtensionInterceptor())
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
    public function cliToolCommandDataProvider()
    {
        $fixtureDirectory = dirname(__DIR__) . '/Fixtures';
        return $this->inflateDataSet(array(
            // add ' --plain' in order to disable PharStreamWrapper in CLI tool
            $fixtureDirectory . '/cli-tool.phar',
            $fixtureDirectory . '/cli-tool',
        ));
    }

    /**
     * @param string $command
     *
     * @test
     * @dataProvider cliToolCommandDataProvider
     */
    public function cliToolIsExecuted($command)
    {
        $descriptorSpecifications = array(
            array('pipe', 'r'), // STDIN -> process
            array('pipe', 'w'), // STDOUT <- process
            array('pipe', 'a'), // STDERR
        );
        $process = proc_open('php ' . $command, $descriptorSpecifications, $pipes);
        static::assertInternalType('resource', $process);

        $read = array($pipes[1], $pipes[2]); // reading from process' STDOUT & STDERR
        $write = null;
        $except = null;
        // there must be some response at least after 3 seconds
        $events = stream_select($read, $write, $except, 3);
        static::assertGreaterThan(0, $events);

        $response = stream_get_contents($pipes[1]);
        if (stripos($response, 'error')) {
            static::fail($response);
        }

        static::assertSame(array(
            '__wrapped' => true,
            '__self' => 'TYPO3 demo text file.',
            '__alias' => 'TYPO3 demo text file.',
            'bundle.phar' => 'TYPO3 demo text file.',
        ), json_decode($response, true));
    }
}
