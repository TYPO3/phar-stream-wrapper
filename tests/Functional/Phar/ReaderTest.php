<?php
declare(strict_types = 1);
namespace TYPO3\PharStreamWrapper\Tests\Functional\Phar;

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
use TYPO3\PharStreamWrapper\Phar\Reader;

class ReaderTest extends TestCase
{
    const CONTAINER_ALIAS = 'container.alias';
    const STUB_MAPPED_ALIAS = 'stub.mappedAlias';
    const MANIFEST_ALIAS = 'manifest.alias';

    public function pharAliasDataProvider(): array
    {
        $fixturesPath = dirname(__DIR__) . '/Fixtures/';

        return [
            'bundle.phar' => [
                $fixturesPath . 'bundle.phar',
                [
                    self::CONTAINER_ALIAS => 'bndl.phar',
                    self::STUB_MAPPED_ALIAS => '',
                    self::MANIFEST_ALIAS => 'bndl.phar',
                ],
            ],
            'serialized.phar' => [
                $fixturesPath . 'serialized.phar',
                [
                    self::CONTAINER_ALIAS => 'srlzd.phar',
                    self::STUB_MAPPED_ALIAS => '',
                    self::MANIFEST_ALIAS => 'srlzd.phar',
                ],
            ],
            'geoip2.phar' => [
                $fixturesPath . 'geoip2.phar',
                [
                    self::CONTAINER_ALIAS => 'geoip2.phar',
                    self::STUB_MAPPED_ALIAS => 'geoip2.phar',
                    self::MANIFEST_ALIAS => '',
                ],
            ],
        ];
    }

    /**
     * @param string $path
     * @param array $expectations
     * @test
     * @dataProvider pharAliasDataProvider
     */
    public function pharStubMappedAliasCanBeResolved(string $path, array $expectations)
    {
        $reader = new Reader($path);
        $this->assertSame(
            $expectations[self::STUB_MAPPED_ALIAS],
            $reader->resolveContainer()->getStub()->getMappedAlias()
        );
    }

    /**
     * @param string $path
     * @param array $expectations
     * @test
     * @dataProvider pharAliasDataProvider
     */
    public function pharManifestAliasCanBeResolved(string $path, array $expectations)
    {
        $reader = new Reader($path);
        $this->assertSame(
            $expectations[self::MANIFEST_ALIAS],
            $reader->resolveContainer()->getManifest()->getAlias()
        );
    }

    /**
     * @param string $path
     * @param array $expectations
     * @test
     * @dataProvider pharAliasDataProvider
     */
    public function pharContainerAliasCanBeResolved(string $path, array $expectations)
    {
        $reader = new Reader($path);
        $this->assertSame(
            $expectations[self::CONTAINER_ALIAS],
            $reader->resolveContainer()->getAlias()
        );
    }
}
