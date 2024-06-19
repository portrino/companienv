<?php

namespace Companienv\IO\FileSystem;

class NativePhpFileSystem implements FileSystem
{
    private string $root;

    public function __construct(string $root)
    {
        $this->root = $root;
    }

    /**
     * @param string $path
     * @param string|string[] $contents
     * @return bool
     */
    public function write(string $path, string|array $contents): bool
    {
        return (bool)file_put_contents($this->isRelativePath($path) ? $this->realpath($path) : $path, $contents);
    }

    public function exists(string $path): bool
    {
        return file_exists($this->isRelativePath($path) ? $this->realpath($path) : $path);
    }

    public function getContents(string $path): string
    {
        return (string)file_get_contents($this->isRelativePath($path) ? $this->realpath($path) : $path);
    }

    public function delete(string $path): bool
    {
        return unlink($this->isRelativePath($path) ? $this->realpath($path) : $path);
    }

    public function realpath(string $path): string
    {
        return $this->root . DIRECTORY_SEPARATOR . $path;
    }

    protected function isRelativePath(string $path): bool
    {
        return substr($path, 0, 1) !== '/';
    }
}
