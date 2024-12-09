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


    protected function setUp(): void
    {
        $this->path = uniqid('path');
        $this->allAssertion = $this->createMock(Assertable::class);
        $this->specificAssertion = $this->createMock(Assertable::class);
    }

    protected function tearDown(): void
    {
        unset($this->path, $this->allAssertion, $this->specificAssertion);
    }

    /**
     * @test
     */
    public function assertionAssignmentFailsWithUnknownCommand(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189881);
        (new Behavior())->withAssertion(
            $this->allAssertion,
            'UNKNOWN'
        );
    }

    /**
     * @test
     */
    public function assertInvocationFailsWithInvalidCommand(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189882);
        $subject = (new Behavior())->withAssertion(
            $this->allAssertion
        );
        $subject->assert($this->path, 'UNKNOWN');
    }

    /**
     * @test
     */
    public function assertInvocationFailsWithIncompleteAssertions(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189883);
        $subject = (new Behavior())->withAssertion(
            $this->allAssertion,
            Behavior::COMMAND_UNLINK
        );
        $subject->assert($this->path, Behavior::COMMAND_UNLINK);
    }

    /**
     * @test
     */
    public function assertInvocationIsDelegatedWithEmptyCommands(): void
    {
        $subject = new Behavior();

        $commands = [
            Behavior::COMMAND_DIR_OPENDIR,
            Behavior::COMMAND_MKDIR,
            Behavior::COMMAND_RENAME,
            Behavior::COMMAND_RMDIR,
            Behavior::COMMAND_STEAM_METADATA,
            Behavior::COMMAND_STREAM_OPEN,
            Behavior::COMMAND_UNLINK,
            Behavior::COMMAND_URL_STAT,
        ];

        foreach ($commands as $command) {
            $assertion = $this->createMock(Assertable::class);
            $assertion
                ->expects($this->once())
                ->method('assert')
                ->with($this->path, $command)
                ->willReturn(false);
            $subject = $subject->withAssertion($assertion, $command);
        }

        foreach ($commands as $command) {
            static::assertFalse(
                $subject->assert($this->path, $command)
            );
        }
    }

    /**
     * @test
     */
    public function assertionSucceedsWithEmptyAndSingleCommands(): void
    {
        $this->allAssertion
            ->expects($this->once())
            ->method('assert')
            ->with($this->path, Behavior::COMMAND_URL_STAT)
            ->willReturn(false);
        $this->specificAssertion
            ->expects($this->once())
            ->method('assert')
            ->with($this->path, Behavior::COMMAND_UNLINK)
            ->willReturn(false);

        $subject = (new Behavior())
            ->withAssertion(
                $this->allAssertion
            )
            ->withAssertion(
                $this->specificAssertion,
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
