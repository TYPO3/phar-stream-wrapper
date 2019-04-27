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
    const STUB_CONTENT_FLAG = 'stub.containerFlag';
    const MANIFEST_ALIAS = 'manifest.alias';

    public function pharAliasDataProvider()
    {
        $fixturesPath = dirname(__DIR__) . '/Fixtures/';

        return array(
            'bundle.phar' => array(
                $fixturesPath . 'bundle.phar',
                array(
                    self::STUB_CONTENT_FLAG => '<?php',
                    self::CONTAINER_ALIAS => 'bndl.phar',
                    self::STUB_MAPPED_ALIAS => '',
                    self::MANIFEST_ALIAS => 'bndl.phar',
                ),
            ),
            'alias-special.phar' => array(
                $fixturesPath . 'alias-special.phar',
                array(
                    self::STUB_CONTENT_FLAG => '<c3d4371ab0014b4e777cd450347bd20182a1dae3>',
                    // actually PHP would throw an error when having different alias names
                    self::CONTAINER_ALIAS => 'spcl.phar',
                    self::STUB_MAPPED_ALIAS => 'alias.special.phar',
                    self::MANIFEST_ALIAS => 'spcl.phar',
                ),
            ),
            'compromised.phar' => array(
                $fixturesPath . 'compromised.phar',
                array(
                    self::STUB_CONTENT_FLAG => '<?php',
                    self::CONTAINER_ALIAS => 'cmprmsd.phar',
                    self::STUB_MAPPED_ALIAS => '',
                    self::MANIFEST_ALIAS => 'cmprmsd.phar',
                ),
            ),
            'geoip2.phar' => array(
                $fixturesPath . 'geoip2.phar',
                array(
                    self::STUB_CONTENT_FLAG => '@link https://github.com/herrera-io/php-box/',
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
    public function pharStubContentFlagCanBeResolved($path, array $expectations)
    {
        $reader = new Reader($path);
        $this->assertContains(
            $expectations[self::STUB_CONTENT_FLAG],
            $reader->resolveContainer()->getStub()->getContent()
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
