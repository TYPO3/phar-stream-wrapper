<?php
declare(strict_types=1);
namespace TYPO3\PharStreamWrapper\Phar;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * @internal Experimental implementation of Phar archive internals
 */
class Reader
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $fileType;

    /**
     * @param string $fileName
     */
    public function __construct(string $fileName)
    {
        if (strpos($fileName, '://') !== false) {
            throw new \UnexpectedValueException(
                'File name must not contain stream prefix',
                1539623708
            );
        }

        $this->fileName = $fileName;
        $this->fileType = $this->determineFileType();
    }

    /**
     * @return Manifest
     */
    public function resolveManifest(): Manifest
    {
        $stream = '';
        if ($this->fileType === 'application/x-gzip') {
            $stream = 'compress.zlib://';
        } elseif ($this->fileType === 'application/x-bzip2') {
            $stream = 'compress.bzip2://';
        }

        $content = null;
        $manifestLength = null;
        $resource = fopen($stream . $this->fileName, 'r');
        while (!feof($resource)) {
            $line = fgets($resource);
            // stop reading file when manifest can be extracted
            if ($manifestLength !== null && $content !== null && strlen($content) >= $manifestLength) {
                break;
            }
            if ($content !== null) {
                $content .= $line;
                $manifestLength = $this->resolveManifestLength($content);
            } elseif (strpos($line, '__HALT_COMPILER()') !== false) {
                $content = preg_replace('#^.*__HALT_COMPILER\(\)[^>]*\?>(\r|\n)*#', '', $line);
                $manifestLength = $this->resolveManifestLength($content);
            }
        }
        fclose($resource);

        if ($content === null || $manifestLength === null || strlen($content) < $manifestLength) {
            throw new \UnexpectedValueException('Cannot resolve manifest');
        }

        return Manifest::fromContent($content);
    }

    /**
     * @return string
     */
    private function determineFileType()
    {
        $fileInfo = new \finfo();
        return $fileInfo->file($this->fileName, FILEINFO_MIME_TYPE);
    }

    /**
     * @param string $content
     * @return int|null
     */
    private function resolveManifestLength(string $content): ?int
    {
        if (strlen($content) < 4) {
            return null;
        }
        return static::resolveFourByteLittleEndian($content, 0);
    }

    /**
     * @param string $content
     * @param int $start
     * @return int
     */
    public static function resolveFourByteLittleEndian(string $content, int $start): int
    {
        $payload = substr($content, $start, 4);
        if (!is_string($payload)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot resolve value at offset %d', $start),
                1539614260
            );
        }

        $value = unpack('V', $payload);
        if (!isset($value[1])) {
            throw new \UnexpectedValueException(
                sprintf('Cannot resolve value at offset %d', $start),
                1539614261
            );
        }
        return $value[1];
    }

    /**
     * @param string $content
     * @param int $start
     * @return int
     */
    public static function resolveTwoByteBigEndian(string $content, int $start): int
    {
        $payload = substr($content, $start, 2);
        if (!is_string($payload)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot resolve value at offset %d', $start),
                1539614263
            );
        }

        $value = unpack('n', $payload);
        if (!isset($value[1])) {
            throw new \UnexpectedValueException(
                sprintf('Cannot resolve value at offset %d', $start),
                1539614264
            );
        }
        return $value[1];
    }
}
