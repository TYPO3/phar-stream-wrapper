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
use TYPO3\PharStreamWrapper\Assertable;
use TYPO3\PharStreamWrapper\Behavior;

class BehaviourTest extends TestCase
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var Assertable|ObjectProphecy
     */
    private $allAssertion;

    /**
     * @var Assertable|ObjectProphecy
     */
    private $specificAssertion;


    protected function setUp()
    {
        parent::setUp();
        $this->path = uniqid('path');
        $this->allAssertion = $this->prophesize('\TYPO3\PharStreamWrapper\Assertable');
        $this->specificAssertion = $this->prophesize('\TYPO3\PharStreamWrapper\Assertable');
    }

    protected function tearDown()
    {
        unset($this->path, $this->allAssertion, $this->specificAssertion);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function assertionAssignmentFailsWithUnknownCommand()
    {
        $this->setExpectedException('\LogicException', NULL, 1535189881);
        $behavior = new Behavior();
        $behavior->withAssertion(
            $this->allAssertion->reveal(),
            'UNKNOWN'
        );
    }

    /**
     * @test
     */
    public function assertInvocationFailsWithInvalidCommand()
    {
        $this->setExpectedException('\LogicException', NULL, 1535189882);
        $behavior = new Behavior();
        $subject = $behavior->withAssertion(
            $this->allAssertion->reveal()
        );
        $subject->assert($this->path, 'UNKNOWN');
    }

    /**
     * @test
     */
    public function assertInvocationFailsWithIncompleteAssertions()
    {
        $this->setExpectedException('\LogicException', NULL, 1535189883);
        $behavior = new Behavior();
        $subject = $behavior->withAssertion(
            $this->allAssertion->reveal(),
            Behavior::COMMAND_UNLINK
        );
        $subject->assert($this->path, Behavior::COMMAND_UNLINK);
    }

    /**
     * @test
     */
    public function assertInvocationIsDelegatedWithEmptyCommands()
    {
        $commands = array(
            Behavior::COMMAND_DIR_OPENDIR,
            Behavior::COMMAND_MKDIR,
            Behavior::COMMAND_RENAME,
            Behavior::COMMAND_RMDIR,
            Behavior::COMMAND_STEAM_METADATA,
            Behavior::COMMAND_STREAM_OPEN,
            Behavior::COMMAND_UNLINK,
            Behavior::COMMAND_URL_STAT,
        );
        foreach ($commands as $command) {
            $this->allAssertion->assert($this->path, $command)
                ->willReturn(false)
                ->shouldBeCalled();
        }

        $behavior = new Behavior();
        $subject = $behavior->withAssertion(
            $this->allAssertion->reveal()
        );

        foreach ($commands as $command) {
            static::assertFalse(
                $subject->assert($this->path, $command)
            );
        }
    }

    /**
     * @test
     */
    public function assertionSucceedsWithEmptyAndSingleCommands()
    {
        $this->allAssertion
            ->assert($this->path, Behavior::COMMAND_URL_STAT)
            ->willReturn(false)
            ->shouldBeCalled();
        $this->specificAssertion
            ->assert($this->path, Behavior::COMMAND_UNLINK)
            ->willReturn(false)
            ->shouldBeCalled();

        $behavior = new Behavior();
        $subject = $behavior
            ->withAssertion(
                $this->allAssertion->reveal()
            )
            ->withAssertion(
                $this->specificAssertion->reveal(),
                Behavior::COMMAND_UNLINK
            );

        static::assertFalse(
            $subject->assert($this->path, Behavior::COMMAND_URL_STAT)
        );
        static::assertFalse(
            $subject->assert($this->path, Behavior::COMMAND_UNLINK)
        );
    }
}
