<?php
namespace TYPO3\PharStreamWrapper\Tests\Functional\Resolver;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use PHPUnit\Framework\TestCase;
use TYPO3\PharStreamWrapper\Behavior;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\Resolver\PharInvocation;
use TYPO3\PharStreamWrapper\Resolver\PharInvocationResolver;

class PharInvocationResolverTest extends TestCase
{
    /**
     * @var PharInvocationResolver
     */
    private $subject;

    protected function setUp()
    {
        parent::setUp();
        Manager::initialize(new Behavior());
        $this->subject = new PharInvocationResolver();
    }

    protected function tearDown()
    {
        unset($this->subject);
        Manager::destroy();
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function invocationIsResolvedDataProvider(): array
    {
        $fixtureDirectory = dirname(__DIR__) . '/Fixtures';
        return [
            'bundle.phar (default)' => [
                'phar://' . $fixtureDirectory . '/../Fixtures/bundle.phar/some/resource',
                null,
                [
                    'baseName' => self::normalizeWindowsPath($fixtureDirectory . '/bundle.phar'),
                    'alias' => 'bndl.phar',
                ],
            ],
            'bundle.phar (realpath)' => [
                'phar://' . $fixtureDirectory . '/../Fixtures/bundle.phar/some/resource',
                PharInvocationResolver::RESOLVE_REALPATH,
                [
                    'baseName' => self::normalizeWindowsPath($fixtureDirectory . '/bundle.phar'),
                    'alias' => '',
                ],
            ],
            'bundle.phar (alias)' => [
                'phar://' . $fixtureDirectory . '/../Fixtures/bundle.phar/some/resource',
                PharInvocationResolver::RESOLVE_ALIAS,
                [
                    'baseName' => self::normalizeWindowsPath($fixtureDirectory . '/../Fixtures/bundle.phar'),
                    'alias' => 'bndl.phar',
                ],
            ],
            'bundle.phar (realpath, alias)' => [
                'phar://' . $fixtureDirectory . '/../Fixtures/bundle.phar/some/resource',
                PharInvocationResolver::RESOLVE_REALPATH | PharInvocationResolver::RESOLVE_ALIAS,
                [
                    'baseName' => self::normalizeWindowsPath($fixtureDirectory . '/bundle.phar'),
                    'alias' => 'bndl.phar',
                ],
            ],
        ];
    }

    /**
     * @param string $path
     * @param int|null $flags
     * @param array $expectations
     *
     * @test
     * @dataProvider invocationIsResolvedDataProvider
     */
    public function invocationIsResolved(string $path, ?int $flags, array $expectations)
    {
        $invocation = $this->subject->resolve($path, $flags);
        static::assertSame($invocation->getBaseName(), $expectations['baseName']);
        static::assertSame($invocation->getAlias(), $expectations['alias']);
    }

    /**
     * @return array
     */
    public function invocationIsNotResolvedDataProvider(): array
    {
        $fixtureDirectory = dirname(__DIR__) . '/Fixtures';
        return [
            'invalid' => [
                'phar://' . $fixtureDirectory . '/not-existing',
                null,
            ],
        ];
    }

    /**
     * @param string $path
     * @param int|null $flags
     *
     * @test
     * @dataProvider invocationIsNotResolvedDataProvider
     */
    public function invocationIsNotResolved(string $path, ?int $flags = null)
    {
        $invocation = $this->subject->resolve($path, $flags);
        static::assertNull($invocation);
    }

    /**
     * Duplicate of Helper::normalizeWindowsPath() for this test.
     *
     * @param string $path File path to process
     * @return string
     *
     * @see Helper::normalizePath()
     */
    private static function normalizeWindowsPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
