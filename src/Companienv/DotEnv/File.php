<?php

namespace Companienv\DotEnv;

class File
{
    private string $header;

    /**
     * @var Block[]
     */
    private array $blocks;

    /**
     * @param string $header
     * @param Block[] $blocks
     */
    public function __construct(string $header = '', array $blocks = [])
    {
        $this->header = $header;
        $this->blocks = $blocks;
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * @return Block[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @return Variable[]
     */
    public function getAllVariables(): array
    {
        return array_reduce($this->blocks, function (array $carry, Block $block) {
            return array_merge($carry, $block->getVariables());
        }, []);
    }
}
