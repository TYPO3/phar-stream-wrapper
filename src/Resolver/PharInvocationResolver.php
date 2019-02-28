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

use TYPO3\PharStreamWrapper\Helper;
use TYPO3\PharStreamWrapper\Phar\Reader;
use TYPO3\PharStreamWrapper\Resolvable;

class PharInvocationResolver implements Resolvable
{
    const RESOLVE_REALPATH = 1;
    const RESOLVE_ALIAS = 2;

    /**
     * @var PharInvocationStack
     */
    private $stack;

    /**
     * @var string[]
     */
    private $invocationFunctionNames = [
        'include',
        'include_once',
        'require',
        'require_once'
    ];

    /**
     * @param PharInvocationStack $stack
     */
    public function __construct(PharInvocationStack $stack)
    {
        $this->stack = $stack;
    }

    /**
     * Resolves PharInvocation value object (baseName and optional alias).
     *
     * Phar aliases are intended to be used only inside Phar archives, however
     * PharStreamWrapper needs this information exposed outside of Phar as well
     * It is possible that same alias is used for different $baseName values.
     * That's why AliasMap behaves like a stack when resolving base-name for a
     * given alias. On the other hand it is not possible that one $baseName is
     * referring to multiple aliases.
     * @see https://secure.php.net/manual/en/phar.setalias.php
     * @see https://secure.php.net/manual/en/phar.mapphar.php
     *
     * @param string $path
     * @param int|null $flags
     * @return null|PharInvocation
     */
    public function resolve(string $path, int $flags = null)
    {
        $hasPharPrefix = Helper::hasPharPrefix($path);
        $flags = $flags ?? static::RESOLVE_REALPATH | static::RESOLVE_ALIAS;

        if ($hasPharPrefix && $flags & static::RESOLVE_ALIAS) {
            $invocation = $this->findByAlias($path);
            if ($invocation !== null && $this->isInternalInvocation($invocation)) {
                return $invocation;
            } elseif ($invocation !== null) {
                return null;
            }
        }

        $baseName = Helper::determineBaseFile($path);
        if ($baseName === null) {
            return null;
        }

        if ($flags & static::RESOLVE_REALPATH) {
            $baseName = realpath($baseName);
        }
        if ($flags & static::RESOLVE_ALIAS) {
            $alias = (new Reader($baseName))->resolveContainer()->getAlias();
        } else {
            $alias = '';
        }

        return new PharInvocation($baseName, $alias);
    }

    /**
     * @param string $path
     * @return null|PharInvocation
     */
    private function findByAlias(string $path)
    {
        $normalizedPath = Helper::normalizePath($path);
        $possibleAlias = strstr($normalizedPath, '/', true);
        return $this->stack->findLastByAlias($possibleAlias ?: '');
    }

    /**
     * @param PharInvocation $invocation
     * @return bool
     */
    private function isInternalInvocation(PharInvocation $invocation): bool
    {
        $trace = debug_backtrace(0);
        foreach ($trace as $item) {
            if (!isset($item['function']) || !isset($item['args'][0])) {
                continue;
            }
            if ($item['args'][0] === $invocation->getBaseName()
                && in_array($item['function'], $this->invocationFunctionNames, true)
            ) {
                return true;
            }
        }
        return false;
    }
}
