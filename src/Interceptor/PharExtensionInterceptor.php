<?php
declare(strict_types=1);
namespace TYPO3\PharStreamWrapper\Interceptor;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\PharStreamWrapper\Assertable;
use TYPO3\PharStreamWrapper\Helper;
use TYPO3\PharStreamWrapper\Exception;

class PharExtensionInterceptor implements Assertable
{
    /**
     * Determines whether the base file name has a ".phar" suffix.
     *
     * @param string $path
     * @param string $command
     * @return bool
     * @throws Exception
     */
    public function assert(string $path, string $command): bool
    {
        if ($this->baseFileContainsPharExtension($path)) {
            return true;
        }
        throw new Exception(
            sprintf(
                'Unexpected file extension in "%s"',
                $path
            ),
            1535198703
        );
    }

    /**
     * @param string $path
     * @return bool
     */
    private function baseFileContainsPharExtension(string $path): bool
    {
        $baseFile = Helper::determineBaseFile($path);
        if ($baseFile === null) {
            // If we can't determine a base then this is being invoked from
            // inside a Phar archive that is using aliases. In this case we
            // should allow access because the real phar will have already been
            // checked.
            return true;
        }
        // If the stream wrapper is registered by invoking a phar file that does
        // not not have .phar extension then this should be allowed. For
        // example, some CLI tools recommend removing the extension.
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        // Find the last entry in the backtrace containing a 'file' key as
        // sometimes the last caller is executed outside the scope of a file.
        // For example, this occurs with shutdown functions.
        do {
            $caller = array_pop($backtrace);
        } while (empty($caller['file']) && !empty($backtrace));
        if (isset($caller['file'])) {
            if ($baseFile === Helper::determineBaseFile($caller['file'])) {
                return TRUE;
            }
            // Resolve phar aliases.
            if (realpath($baseFile) === $caller['file']) {
                return TRUE;
            }
        }
        $fileExtension = pathinfo($baseFile, PATHINFO_EXTENSION);
        return strtolower($fileExtension) === 'phar';
    }
}
