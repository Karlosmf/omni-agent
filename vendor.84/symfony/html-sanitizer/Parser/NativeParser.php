<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HtmlSanitizer\Parser;

use Dom\HTMLDocument;
use Dom\Node;

/**
 * Parser using PHP 8.4's new Dom API.
 */
final class NativeParser implements ParserInterface
{
    public function parse(string $html, string $context = 'body'): ?Node
    {
        $document = @HTMLDocument::createFromString(\sprintf('<!DOCTYPE html><%s>%s</%1$s>', $context, $html));
        $element = $document->getElementsByTagName($context)->item(0);

        return $element->hasChildNodes() ? $element : null;
    }
}
