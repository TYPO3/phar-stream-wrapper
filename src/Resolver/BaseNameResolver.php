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

class BaseNameResolver implements Resolvable
{
    const RESOLVE_REALPATH = 1;
    const RESOLVE_ALIAS = 2;

    /**
     * @var AliasMap
     */
    private $aliasMap = [];

    /**
     * @param null|AliasMap $aliasMap
     */
    public function __construct(AliasMap $aliasMap = null)
    {
        if ($aliasMap === null) {
            $aliasMap = new AliasMap();
        }
        $this->aliasMap = $aliasMap;
    }

    /**
     * @param string $path
     * @param int|null $flags
     * @return null|string
     */
    public function resolveBaseName(string $path, int $flags = null)
    {
        $hasPharPrefix = Helper::hasPharPrefix($path);
        $flags = $flags ?? static::RESOLVE_REALPATH | static::RESOLVE_ALIAS;

        if ($hasPharPrefix && $flags & static::RESOLVE_ALIAS) {
            $baseNameFromAliasMap = $this->resolveBaseNameFromAliasMap($path);
            if ($baseNameFromAliasMap !== null) {
                return $baseNameFromAliasMap->getBaseName();
            }
        }

        $baseName = Helper::determineBaseFile($path);
        if ($baseName !== null && $flags & static::RESOLVE_REALPATH) {
            $baseName = realpath($baseName);
        }

        return $baseName;
    }

    /**
     * @param string $baseName
     * @return bool
     * @todo Enhance design, wrapping does not make much sense in this class
     */
    public function purgeBaseName(string $baseName): bool
    {
        return $this->aliasMap->purgeByBaseName($baseName);
    }

    /**
     * Learns (possible) Phar alias for given $baseName.
     *
     * Phar aliases are intended to be used only inside Phar archives, however
     * PharStreamWrapper needs this information exposed outside of Phar as well
     *
     * It is possible that same alias is used for different $baseName values.
     * That's why AliasMap behaves like a stack when resolving base-name for a
     * given alias. On the other hand it is not possible that one $baseName is
     * referring to multiple aliases.
     *
     * @param string $path
     * @param int|null $flags
     * @return bool
     *
     * @see https://secure.php.net/manual/en/phar.setalias.php
     * @see https://secure.php.net/manual/en/phar.mapphar.php
     */
    public function learnAlias(string $path, int $flags = null): bool
    {
        $flags = $flags ?? static::RESOLVE_REALPATH;
        $baseName = Helper::determineBaseFile($path);
        if ($baseName === null) {
            return false;
        }
        if ($this->aliasMap->findFirstByBaseName($baseName) !== null) {
            return false;
        }

        if ($flags & static::RESOLVE_REALPATH) {
            $baseName = realpath($baseName);
        }

        $alias = (new Reader($baseName))->resolveContainer()->getAlias();
        return $this->aliasMap->append($baseName, $alias);
    }

    /**
     * @return null|AliasReference
     */
    private function resolveBaseNameFromAliasMap(string $path)
    {
        $normalizedPath = Helper::normalizePath($path);
        $possibleAlias = strstr($normalizedPath, '/', true);
        return $this->aliasMap->findLastByAlias($possibleAlias ?: '');
    }
}
