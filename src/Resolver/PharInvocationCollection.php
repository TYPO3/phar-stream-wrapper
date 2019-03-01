<?php
declare(strict_types=1);
namespace TYPO3\PharStreamWrapper\Resolver;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class PharInvocationCollection
{
    const UNIQUE_INVOCATION = 1;
    const UNIQUE_BASE_NAME = 2;
    const DUPLICATE_ALIAS_WARNING = 32;

    /**
     * @var PharInvocation[]
     */
    private $invocations = [];

    /**
     * @param PharInvocation $invocation
     * @param null|int $flags
     * @return bool
     */
    public function collect(PharInvocation $invocation, int $flags = null): bool
    {
        if ($flags === null) {
            $flags = static::UNIQUE_INVOCATION | static::DUPLICATE_ALIAS_WARNING;
        }
        if ($invocation->getBaseName() === ''
            || $invocation->getAlias() === ''
            || !$this->assertUniqueBaseName($invocation, $flags)
            || !$this->assertUniqueInvocation($invocation, $flags)
        ) {
            return false;
        }
        if ($flags & static::DUPLICATE_ALIAS_WARNING) {
            $this->triggerDuplicateAliasWarning($invocation);
        }

        $this->invocations[] = $invocation;
        return true;
    }

    /**
     * @param string $baseName
     * @param bool $reverse
     * @return null|PharInvocation
     */
    public function findByBaseName(string $baseName, bool $reverse = false)
    {
        if ($baseName === '') {
            return null;
        }
        foreach ($this->getInvocations($reverse) as $invocation) {
            if ($invocation->getBaseName() === $baseName) {
                return $invocation;
            }
        }
        return null;
    }

    /**
     * @param string $alias
     * @param bool $reverse
     * @return null|PharInvocation
     */
    public function findByAlias(string $alias, bool $reverse = false)
    {
        if ($alias === '') {
            return null;
        }
        foreach ($this->getInvocations($reverse) as $invocation) {
            if ($invocation->getAlias() === $alias) {
                return $invocation;
            }
        }
        return null;
    }

    /**
     * @param callable $callback
     * @param bool $reverse
     * @return null|PharInvocation
     */
    public function findByCallback(callable $callback, $reverse = false)
    {
        foreach ($this->getInvocations($reverse) as $invocation) {
            if (call_user_func($callback, $invocation) === true) {
                return $invocation;
            }
        }
        return null;
    }

    /**
     * Asserts that base-name is unique. This disallows having multiple invocations for
     * same base-name but having different alias names.
     *
     * @param PharInvocation $invocation
     * @param int $flags
     * @return bool
     */
    private function assertUniqueBaseName(PharInvocation $invocation, int $flags): bool
    {
        if (!($flags & static::UNIQUE_BASE_NAME)) {
            return true;
        }
        return $this->findByBaseName($invocation->getBaseName()) === null;
    }

    /**
     * Asserts that combination of base-name and alias is unique. This allows having multiple
     * invocations for same base-name but having different alias names (for whatever reason).
     *
     * @param PharInvocation $invocation
     * @param int $flags
     * @return bool
     */
    private function assertUniqueInvocation(PharInvocation $invocation, int $flags): bool
    {
        if (!($flags & static::UNIQUE_INVOCATION)) {
            return true;
        }
        return $this->findByCallback(
            function (PharInvocation $candidate) use ($invocation) {
                return $candidate->equals($invocation);
            }
        ) === null;
    }

    /**
     * @param PharInvocation $invocation
     */
    private function triggerDuplicateAliasWarning(PharInvocation $invocation)
    {
        $sameAliasInvocation = $this->findByAlias($invocation->getAlias(), true);
        if ($sameAliasInvocation !== null) {
            trigger_error(
                sprintf(
                    'Alias %s cannot be used by %s, already used by %s',
                    $invocation->getAlias(),
                    $invocation->getBaseName(),
                    $sameAliasInvocation->getBaseName()
                ),
                E_USER_WARNING
            );
        }
    }

    /**
     * @param bool $reverse
     * @return PharInvocation[]
     */
    private function getInvocations(bool $reverse = false): array
    {
        if ($reverse) {
            return array_reverse($this->invocations);
        }
        return $this->invocations;
    }
}
