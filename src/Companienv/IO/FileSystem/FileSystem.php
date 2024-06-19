<?php

namespace Companienv\IO\FileSystem;

interface FileSystem
{
    /**
     * @param string $path
     * @param string|string[] $contents
     * @return bool
     */
    public function write(string $path, string|array $contents): bool;

    public function exists(string $path): bool;

    public function getContents(string $path): string;

    public function realpath(string $path): string;
}
