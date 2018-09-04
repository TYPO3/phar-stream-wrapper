<?php

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
    private $behaviorProphecy;

    protected function setUp()
    {
        parent::setUp();
        $this->behaviorProphecy = $this->prophesize('\TYPO3\PharStreamWrapper\Behavior');
    }

    protected function tearDown()
    {
        unset($this->behaviorProphecy);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function multipleInitializationFails()
    {
        $this->setExpectedException('\LogicException', NULL, 1535189871);
        Manager::initialize($this->behaviorProphecy->reveal());
        Manager::initialize($this->behaviorProphecy->reveal());
    }

    /**
     * @test
     */
    public function instanceFailsIfNotInitialized()
    {
        $this->setExpectedException('\LogicException', NULL, 1535189872);
        Manager::instance();
    }

    /**
     * @test
     */
    public function instanceFailsIfDestroyed()
    {
        $this->setExpectedException('\LogicException', NULL, 1535189872);
        Manager::initialize($this->behaviorProphecy->reveal());
        Manager::destroy();
        Manager::instance();
    }

    /**
     * @test
     */
    public function instanceResolvesIfCalledTwice()
    {
        Manager::initialize($this->behaviorProphecy->reveal());
        $firstInstance = Manager::instance();
        $secondInstance = Manager::instance();
        static::assertSame($firstInstance, $secondInstance);
    }

    /**
     * @test
     */
    public function destroyReturnsTrueIfInitialized()
    {
        Manager::initialize($this->behaviorProphecy->reveal());
        static::assertTrue(Manager::destroy());
    }

    /**
     * @test
     */
    public function destroyReturnsFalseIfNotInitialized()
    {
        static::assertFalse(Manager::destroy());
    }

    /**
     * @test
     */
    public function assertInvocationIsDelegatedToBehavior()
    {
        $testPath = uniqid('path');
        $testCommand = uniqid('command');
        $this->behaviorProphecy->assert($testPath, $testCommand)
            ->willReturn(false)
            ->shouldBeCalled();

        Manager::initialize($this->behaviorProphecy->reveal());

        static::assertFalse(
            Manager::instance()->assert($testPath, $testCommand)
        );
    }
}
