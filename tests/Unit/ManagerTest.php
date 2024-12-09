<?php
declare(strict_types = 1);
namespace TYPO3\PharStreamWrapper\Tests\Unit;

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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\PharStreamWrapper\Behavior;
use TYPO3\PharStreamWrapper\Manager;

class ManagerTest extends TestCase
{
    /**
     * @var ObjectProphecy|Behavior
     */
    private $behaviorMock;

    protected function setUp(): void
    {
        $this->behaviorMock = $this->createMock(Behavior::class);
    }

    protected function tearDown(): void
    {
        unset($this->behaviorMock);
    }

    /**
     * @test
     */
    public function multipleInitializationFails(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189871);
        Manager::initialize($this->behaviorMock);
        Manager::initialize($this->behaviorMock);
    }

    /**
     * @test
     */
    public function instanceFailsIfNotInitialized(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189872);
        Manager::instance();
    }

    /**
     * @test
     */
    public function instanceFailsIfDestroyed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189872);
        Manager::initialize($this->behaviorMock);
        Manager::destroy();
        Manager::instance();
    }

    /**
     * @test
     */
    public function instanceResolvesIfCalledTwice(): void
    {
        Manager::initialize($this->behaviorMock);
        $firstInstance = Manager::instance();
        $secondInstance = Manager::instance();
        static::assertSame($firstInstance, $secondInstance);
    }

    /**
     * @test
     */
    public function destroyReturnsTrueIfInitialized(): void
    {
        Manager::initialize($this->behaviorMock);
        static::assertTrue(Manager::destroy());
    }

    /**
     * @test
     */
    public function destroyReturnsFalseIfNotInitialized(): void
    {
        static::assertFalse(Manager::destroy());
    }

    /**
     * @test
     */
    public function assertInvocationIsDelegatedToBehavior(): void
    {
        $testPath = uniqid('path');
        $testCommand = uniqid('command');
        $this->behaviorMock
            ->expects($this->once())
            ->method('assert')
            ->with($testPath, $testCommand)
            ->willReturn(false);

        Manager::initialize($this->behaviorMock);

        static::assertFalse(
            Manager::instance()->assert($testPath, $testCommand)
        );
    }
}
