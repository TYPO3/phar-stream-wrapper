<?php
declare(strict_types=1);
namespace TYPO3\PharStreamWrapper;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\PharStreamWrapper\Resolver\BaseNameResolver;

class Manager implements Assertable, Resolvable
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var Behavior
     */
    private $behavior;

    /**
     * @var Resolvable
     */
    private $resolver;

    /**
     * @param Behavior $behaviour
     * @param Resolvable $resolver
     * @return self
     */
    public static function initialize(Behavior $behaviour, Resolvable $resolver = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($behaviour, $resolver);
            return self::$instance;
        }
        throw new \LogicException(
            'Manager can only be initialized once',
            1535189871
        );
    }

    /**
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        throw new \LogicException(
            'Manager needs to be initialized first',
            1535189872
        );
    }

    /**
     * @return bool
     */
    public static function destroy(): bool
    {
        if (self::$instance === null) {
            return false;
        }
        self::$instance = null;
        return true;
    }

    /**
     * @param Behavior $behaviour
     * @param Resolvable $resolver
     */
    private function __construct(Behavior $behaviour, Resolvable $resolver = null)
    {
        $this->behavior = $behaviour;
        $this->resolver = $resolver ?? new BaseNameResolver();
    }

    /**
     * @param string $path
     * @param string $command
     * @return bool
     */
    public function assert(string $path, string $command): bool
    {
        return $this->behavior->assert($path, $command);
    }

    /**
     * @param string $path
     * @param null|int $flags
     * @return string|null
     */
    public function resolveBaseName(string $path, int $flags = null)
    {
        return $this->resolver->resolveBaseName($path, $flags);
    }
}
