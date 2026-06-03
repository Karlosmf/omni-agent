<?php

declare(strict_types=1);

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;

class StaticVar extends NodeAbstract
{
    /** @var Expr\Variable Variable */
    public Expr\Variable $var;

    /** @var null|Expr Default value */
    public ?Expr $default;

    /**
     * Constructs a static variable node.
     *
     * @param  Expr\Variable  $var  Name
     * @param  null|Expr  $default  Default value
     * @param  array<string, mixed>  $attributes  Additional attributes
     */
    public function __construct(
        Expr\Variable $var, ?Expr $default = null, array $attributes = []
    ) {
        $this->attributes = $attributes;
        $this->var = $var;
        $this->default = $default;
    }

    public function getSubNodeNames(): array
    {
        return ['var', 'default'];
    }

    public function getType(): string
    {
        return 'StaticVar';
    }
}

// @deprecated compatibility alias
class_alias(StaticVar::class, Stmt\StaticVar::class);
