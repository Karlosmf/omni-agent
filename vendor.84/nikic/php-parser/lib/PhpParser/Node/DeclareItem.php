<?php

declare(strict_types=1);

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;

class DeclareItem extends NodeAbstract
{
    /** @var Identifier Key */
    public Identifier $key;

    /** @var Expr Value */
    public Expr $value;

    /**
     * Constructs a declare key=>value pair node.
     *
     * @param  string|Identifier  $key  Key
     * @param  Expr  $value  Value
     * @param  array<string, mixed>  $attributes  Additional attributes
     */
    public function __construct($key, Expr $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->key = \is_string($key) ? new Identifier($key) : $key;
        $this->value = $value;
    }

    public function getSubNodeNames(): array
    {
        return ['key', 'value'];
    }

    public function getType(): string
    {
        return 'DeclareItem';
    }
}

// @deprecated compatibility alias
class_alias(DeclareItem::class, Stmt\DeclareDeclare::class);
