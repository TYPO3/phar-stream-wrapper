<?php
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

    public function pharAliasDataProvider()
    {
        $fixturesPath = dirname(__DIR__) . '/Fixtures/';

        return array(
            'bundle.phar' => array(
                $fixturesPath . 'bundle.phar',
                array(
                    self::CONTAINER_ALIAS => 'bndl.phar',
                    self::STUB_MAPPED_ALIAS => '',
                    self::MANIFEST_ALIAS => 'bndl.phar',
                ),
            ),
            'compromised.phar' => array(
                $fixturesPath . 'compromised.phar',
                array(
                    self::CONTAINER_ALIAS => 'cmprmsd.phar',
                    self::STUB_MAPPED_ALIAS => '',
                    self::MANIFEST_ALIAS => 'cmprmsd.phar',
                ),
            ),
            'geoip2.phar' => array(
                $fixturesPath . 'geoip2.phar',
                array(
                    self::CONTAINER_ALIAS => 'geoip2.phar',
                    self::STUB_MAPPED_ALIAS => 'geoip2.phar',
                    self::MANIFEST_ALIAS => '',
                ),
            ),
        );
    }

    /**
     * @param string $path
     * @param array $expectations
     * @test
     * @dataProvider pharAliasDataProvider
     */
    public function pharStubMappedAliasCanBeResolved($path, array $expectations)
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
    public function pharManifestAliasCanBeResolved($path, array $expectations)
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
    public function pharContainerAliasCanBeResolved($path, array $expectations)
    {
        $reader = new Reader($path);
        $this->assertSame(
            $expectations[self::CONTAINER_ALIAS],
            $reader->resolveContainer()->getAlias()
        );
    }
}
