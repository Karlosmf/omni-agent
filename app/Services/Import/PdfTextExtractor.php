<?php

namespace App\Services\Import;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Turns a PDF into plain text, so providers that cannot read PDFs (Groq) can still import a trip offer.
 *
 * Works on shared hosting: it uses `pdftotext` (poppler) when the server has it and otherwise a small
 * pure-PHP reader (no extensions or packages) that handles the text PDFs agencies normally receive.
 * Scanned PDFs (pictures of text) have nothing to read and need a provider that sees PDFs (Gemini or OpenAI).
 */
class PdfTextExtractor
{
    public const MAX_BYTES = 25_000_000;

    /**
     * @throws RuntimeException With a message meant for the person using the tool.
     */
    public function extract(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('No se pudo abrir el archivo PDF.');
        }

        if (filesize($path) > self::MAX_BYTES) {
            throw new RuntimeException('El PDF es demasiado grande (máximo 25 MB).');
        }

        $text = $this->clean($this->withPdftotext($path) ?? '');

        if (! $this->looksReadable($text)) {
            $text = $this->clean($this->parse((string) file_get_contents($path)));
        }

        if (! $this->looksReadable($text)) {
            throw new RuntimeException('El PDF no tiene texto que se pueda leer: probablemente es una imagen escaneada. Usá Gemini u OpenAI en Ajustes → IA (leen PDFs directamente) o copiá el texto a mano.');
        }

        return $text;
    }

    /**
     * Whether the server has `pdftotext` available.
     */
    public function hasNativeTool(): bool
    {
        return $this->pdftotextBinary() !== null;
    }

    private function pdftotextBinary(): ?string
    {
        if (! function_exists('proc_open')) {
            return null;
        }

        try {
            return (new ExecutableFinder)->find('pdftotext');
        } catch (Throwable) {
            return null;
        }
    }

    private function withPdftotext(string $path): ?string
    {
        $binary = $this->pdftotextBinary();

        if ($binary === null) {
            return null;
        }

        try {
            $process = new Process([$binary, '-layout', '-enc', 'UTF-8', $path, '-']);
            $process->setTimeout(30)->run();

            return $process->isSuccessful() ? $process->getOutput() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function looksReadable(string $text): bool
    {
        if (mb_strlen($text) < 20) {
            return false;
        }

        $words = preg_match_all('/[\p{L}\p{N}]/u', $text);

        return $words / max(1, mb_strlen($text)) > 0.4;
    }

    private function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}", "\f"], ["\n", "\n", ' ', "\n\n"], $text);
        $text = strtr($text, ['ﬁ' => 'fi', 'ﬂ' => 'fl', 'ﬀ' => 'ff', 'ﬃ' => 'ffi', 'ﬄ' => 'ffl']);
        $text = preg_replace('/[\x00-\x08\x0B\x0E-\x1F\x7F]/', '', $text) ?? '';
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? '';
        $text = implode("\n", array_map('trim', explode("\n", $text)));
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? '';

        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        return trim($text);
    }

    // --- Pure PHP reader -------------------------------------------------------------------------

    private function parse(string $pdf): string
    {
        if (! str_contains(substr($pdf, 0, 1024), '%PDF')) {
            throw new RuntimeException('El archivo no es un PDF válido.');
        }

        if (preg_match('#/Encrypt\b#', $pdf)) {
            throw new RuntimeException('El PDF está protegido con contraseña: quitale la protección y probá de nuevo.');
        }

        $objects = $this->readObjects($pdf);
        $fonts = [];
        $pages = [];

        foreach ($this->pageIds($objects) as $pageId) {
            $content = $this->pageContent($objects, $pageId);
            $pageFonts = $this->pageFonts($objects, $pageId, $fonts);

            $pages[] = $this->contentText($content, $pageFonts);
        }

        return implode("\n\n", $pages);
    }

    /**
     * @return array<int, array{dict: string, data: ?string}>
     */
    private function readObjects(string $pdf): array
    {
        $objects = [];
        $objectStreams = [];

        preg_match_all('/(?<![\d])(\d+)\s+(\d+)\s+obj\b/', $pdf, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $i => [$id]) {
            $bodyStart = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $endobj = strpos($pdf, 'endobj', $bodyStart);
            $streamAt = strpos($pdf, 'stream', $bodyStart);

            if ($streamAt !== false && ($endobj === false || $streamAt < $endobj)) {
                $dict = substr($pdf, $bodyStart, $streamAt - $bodyStart);
                $dataStart = $streamAt + 6;
                $dataStart += substr($pdf, $dataStart, 2) === "\r\n" ? 2 : (($pdf[$dataStart] ?? '') === "\n" || ($pdf[$dataStart] ?? '') === "\r" ? 1 : 0);
                $dataEnd = strpos($pdf, 'endstream', $dataStart);
                $data = $dataEnd === false ? null : $this->decodeStream($dict, substr($pdf, $dataStart, $dataEnd - $dataStart));

                $objects[(int) $id] = ['dict' => $dict, 'data' => $data];

                if ($data !== null && preg_match('#/Type\s*/ObjStm#', $dict)) {
                    $objectStreams[] = [$dict, $data];
                }
            } else {
                $objects[(int) $id] = ['dict' => substr($pdf, $bodyStart, ($endobj === false ? strlen($pdf) : $endobj) - $bodyStart), 'data' => null];
            }
        }

        foreach ($objectStreams as [$dict, $data]) {
            $count = (int) ($this->number($dict, 'N') ?? 0);
            $first = (int) ($this->number($dict, 'First') ?? 0);

            preg_match_all('/(\d+)\s+(\d+)/', substr($data, 0, $first), $pairs, PREG_SET_ORDER);
            $pairs = array_slice($pairs, 0, $count);

            foreach ($pairs as $n => $pair) {
                $start = $first + (int) $pair[2];
                $end = isset($pairs[$n + 1]) ? $first + (int) $pairs[$n + 1][2] : strlen($data);

                $objects[(int) $pair[1]] ??= ['dict' => substr($data, $start, $end - $start), 'data' => null];
            }
        }

        return $objects;
    }

    private function decodeStream(string $dict, string $data): ?string
    {
        if (! preg_match('#/Filter\s*(\[[^\]]*\]|/\w+)#', $dict, $filters)) {
            return $data;
        }

        preg_match_all('#/(\w+)#', $filters[1], $names);

        foreach ($names[1] as $filter) {
            $data = match ($filter) {
                'FlateDecode', 'Fl' => $this->inflate($data),
                'ASCIIHexDecode', 'AHx' => (string) hex2bin(preg_replace('/[^0-9A-Fa-f]/', '', rtrim($data, '>')).(strlen(preg_replace('/[^0-9A-Fa-f]/', '', rtrim($data, '>'))) % 2 ? '0' : '')),
                'ASCII85Decode', 'A85' => $this->ascii85($data),
                default => null, // images and rare codecs: not text
            };

            if ($data === null) {
                return null;
            }
        }

        return $data;
    }

    private function inflate(string $data): ?string
    {
        $out = @zlib_decode($data);

        if ($out === false) {
            $out = @gzinflate(substr($data, 2));
        }

        return $out === false ? null : $out;
    }

    private function ascii85(string $data): ?string
    {
        $data = preg_replace('/\s+/', '', $data) ?? '';
        $data = str_ends_with($data, '~>') ? substr($data, 0, -2) : $data;
        $data = str_starts_with($data, '<~') ? substr($data, 2) : $data;
        $out = '';

        foreach (str_split($data, 5) as $group) {
            $size = strlen($group);

            if ($group === 'z') {
                $out .= "\0\0\0\0";

                continue;
            }

            $group = str_pad($group, 5, 'u');
            $value = 0;

            foreach (str_split($group) as $char) {
                $value = $value * 85 + (ord($char) - 33);
            }

            $out .= substr(pack('N', $value & 0xFFFFFFFF), 0, max(0, $size - 1));
        }

        return $out;
    }

    /**
     * Page object ids in reading order (following the page tree; falling back to object order).
     *
     * @param  array<int, array{dict: string, data: ?string}>  $objects
     * @return list<int>
     */
    private function pageIds(array $objects): array
    {
        $root = null;

        foreach ($objects as $id => $object) {
            if (preg_match('#/Type\s*/Catalog\b#', $object['dict']) && preg_match('#/Pages\s+(\d+)\s+\d+\s+R#', $object['dict'], $m)) {
                $root = (int) $m[1];

                break;
            }
        }

        $pages = [];

        if ($root !== null) {
            $this->walkPages($objects, $root, $pages, []);
        }

        if ($pages === []) {
            foreach ($objects as $id => $object) {
                if (preg_match('#/Type\s*/Page\b(?!s)#', $object['dict'])) {
                    $pages[] = $id;
                }
            }
        }

        return $pages;
    }

    /**
     * @param  array<int, array{dict: string, data: ?string}>  $objects
     * @param  list<int>  $pages
     * @param  list<int>  $seen
     */
    private function walkPages(array $objects, int $id, array &$pages, array $seen): void
    {
        if (in_array($id, $seen, true) || ! isset($objects[$id])) {
            return;
        }

        $dict = $objects[$id]['dict'];

        if (preg_match('#/Kids\s*\[([^\]]*)\]#', $dict, $kids)) {
            preg_match_all('/(\d+)\s+\d+\s+R/', $kids[1], $refs);

            foreach ($refs[1] as $kid) {
                $this->walkPages($objects, (int) $kid, $pages, [...$seen, $id]);
            }

            return;
        }

        $pages[] = $id;
    }

    /**
     * @param  array<int, array{dict: string, data: ?string}>  $objects
     */
    private function pageContent(array $objects, int $pageId): string
    {
        $dict = $objects[$pageId]['dict'];

        if (! preg_match('#/Contents\s*(\[[^\]]*\]|\d+\s+\d+\s+R)#', $dict, $m)) {
            return '';
        }

        preg_match_all('/(\d+)\s+\d+\s+R/', $m[1], $refs);

        return implode("\n", array_map(fn ($ref) => (string) ($objects[(int) $ref]['data'] ?? ''), $refs[1]));
    }

    /**
     * Fonts available to a page, keyed by the resource name used in its content (/F1).
     *
     * @param  array<int, array{dict: string, data: ?string}>  $objects
     * @param  array<int, array<string, mixed>>  $cache
     * @return array<string, array<string, mixed>>
     */
    private function pageFonts(array $objects, int $pageId, array &$cache): array
    {
        $resources = $this->inherited($objects, $pageId, 'Resources');
        $fontRefs = [];

        if ($resources !== null) {
            $fontDict = $this->valueOf($objects, $resources, 'Font');

            if ($fontDict !== null) {
                preg_match_all('#/([^\s/<>\[\]()]+)\s+(\d+)\s+\d+\s+R#', $fontDict, $pairs, PREG_SET_ORDER);

                foreach ($pairs as $pair) {
                    $fontRefs[$pair[1]] = (int) $pair[2];
                }
            }
        }

        $fonts = [];

        foreach ($fontRefs as $name => $id) {
            $cache[$id] ??= $this->loadFont($objects, $id);
            $fonts[$name] = $cache[$id];
        }

        return $fonts;
    }

    /**
     * The inline dictionary of a key in the page or its ancestors (Resources can be inherited).
     *
     * @param  array<int, array{dict: string, data: ?string}>  $objects
     */
    private function inherited(array $objects, int $id, string $key, int $depth = 0): ?string
    {
        $dict = $objects[$id]['dict'] ?? '';
        $value = $this->valueOf($objects, $dict, $key);

        if ($value !== null || $depth > 8) {
            return $value;
        }

        return preg_match('#/Parent\s+(\d+)\s+\d+\s+R#', $dict, $m) ? $this->inherited($objects, (int) $m[1], $key, $depth + 1) : null;
    }

    /**
     * The value of /Key inside a dictionary as an inline dictionary text, following one indirect reference.
     *
     * @param  array<int, array{dict: string, data: ?string}>  $objects
     */
    private function valueOf(array $objects, string $dict, string $key): ?string
    {
        if (! preg_match('#/'.preg_quote($key, '#').'\s*(<<|\d+\s+\d+\s+R)#', $dict, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        if ($m[1][0] === '<<') {
            return $this->balanced($dict, $m[1][1]);
        }

        preg_match('/(\d+)/', $m[1][0], $id);

        return isset($objects[(int) $id[1]]) ? $this->balanced($objects[(int) $id[1]]['dict'], strpos($objects[(int) $id[1]]['dict'], '<<')) : null;
    }

    private function balanced(string $text, int|false $start): ?string
    {
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $length = strlen($text);

        for ($i = $start; $i < $length - 1; $i++) {
            $pair = $text[$i].$text[$i + 1];

            if ($pair === '<<') {
                $depth++;
                $i++;
            } elseif ($pair === '>>') {
                $depth--;
                $i++;

                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return substr($text, $start);
    }

    /**
     * @param  array<int, array{dict: string, data: ?string}>  $objects
     * @return array{map: array<int, string>, bytes: int}
     */
    private function loadFont(array $objects, int $id): array
    {
        $dict = $objects[$id]['dict'] ?? '';
        $type0 = (bool) preg_match('#/Subtype\s*/Type0#', $dict);
        $font = ['map' => [], 'bytes' => $type0 ? 2 : 1];

        if (preg_match('#/ToUnicode\s+(\d+)\s+\d+\s+R#', $dict, $m) && isset($objects[(int) $m[1]]['data'])) {
            $font = $this->parseCMap($objects[(int) $m[1]]['data'], $font['bytes']);
        }

        return $font;
    }

    /**
     * @return array{map: array<int, string>, bytes: int}
     */
    private function parseCMap(string $cmap, int $defaultBytes): array
    {
        $map = [];
        $bytes = $defaultBytes;

        if (preg_match('/begincodespacerange\s*<([0-9A-Fa-f]+)>/', $cmap, $m)) {
            $bytes = max(1, intdiv(strlen($m[1]), 2));
        }

        if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $cmap, $blocks)) {
            foreach ($blocks[1] as $block) {
                preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $block, $pairs, PREG_SET_ORDER);

                foreach ($pairs as $pair) {
                    $map[hexdec($pair[1])] = $this->utf16($pair[2]);
                }
            }
        }

        if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $cmap, $blocks)) {
            foreach ($blocks[1] as $block) {
                preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*(<[0-9A-Fa-f]+>|\[[^\]]*\])/', $block, $ranges, PREG_SET_ORDER);

                foreach ($ranges as $range) {
                    $low = hexdec($range[1]);
                    $high = min(hexdec($range[2]), $low + 65535);

                    if ($range[3][0] === '[') {
                        preg_match_all('/<([0-9A-Fa-f]+)>/', $range[3], $targets);

                        foreach ($targets[1] as $offset => $target) {
                            $map[$low + $offset] = $this->utf16($target);
                        }

                        continue;
                    }

                    $base = trim($range[3], '<>');

                    for ($code = $low; $code <= $high; $code++) {
                        $map[$code] = $this->utf16(substr($base, 0, -4).str_pad(dechex(hexdec(substr($base, -4)) + ($code - $low)), 4, '0', STR_PAD_LEFT));
                    }
                }
            }
        }

        return ['map' => $map, 'bytes' => $bytes];
    }

    private function utf16(string $hex): string
    {
        $hex = strlen($hex) % 4 === 0 ? $hex : str_pad($hex, (int) (ceil(strlen($hex) / 4) * 4), '0', STR_PAD_LEFT);

        return (string) mb_convert_encoding((string) hex2bin($hex), 'UTF-8', 'UTF-16BE');
    }

    /**
     * @param  array<string, array{map: array<int, string>, bytes: int}>  $fonts
     */
    private function contentText(string $content, array $fonts): string
    {
        $out = '';
        $stack = [];
        $font = null;
        $lastY = null;
        $length = strlen($content);

        $newline = function () use (&$out): void {
            if ($out !== '' && ! str_ends_with($out, "\n")) {
                $out .= "\n";
            }
        };
        $space = function () use (&$out): void {
            if ($out !== '' && ! preg_match('/\s$/', $out)) {
                $out .= ' ';
            }
        };

        for ($i = 0; $i < $length;) {
            $char = $content[$i];

            if (ctype_space($char) || $char === "\0") {
                $i++;
            } elseif ($char === '%') {
                $i = $this->skipLine($content, $i);
            } elseif ($char === '(') {
                $stack[] = ['s', $this->literal($content, $i)];
            } elseif ($char === '<' && ($content[$i + 1] ?? '') === '<') {
                $end = strpos($content, '>>', $i);
                $i = $end === false ? $length : $end + 2;
                $stack[] = ['d', null];
            } elseif ($char === '<') {
                $end = strpos($content, '>', $i);
                $hex = preg_replace('/[^0-9A-Fa-f]/', '', substr($content, $i + 1, ($end === false ? $length : $end) - $i - 1)) ?? '';
                $i = $end === false ? $length : $end + 1;
                $stack[] = ['s', (string) hex2bin(strlen($hex) % 2 ? $hex.'0' : $hex)];
            } elseif ($char === '[') {
                $stack[] = ['a', $this->array($content, $i)];
            } elseif ($char === '/') {
                preg_match('#/([^\s/<>\[\]()%]*)#', $content, $m, 0, $i);
                $i += strlen($m[0]);
                $stack[] = ['n', $m[1]];
            } elseif (preg_match('/[+\-.\d]/', $char)) {
                preg_match('/[+\-]?(?:\d+\.?\d*|\.\d+)/', $content, $m, 0, $i);
                $i += max(1, strlen($m[0] ?? ''));
                $stack[] = ['v', (float) ($m[0] ?? 0)];
            } else {
                preg_match('/[A-Za-z\'"*]+/', $content, $m, 0, $i);
                $operator = $m[0] ?? $char;
                $i += max(1, strlen($operator));

                switch ($operator) {
                    case 'BI':
                        $end = strpos($content, 'EI', $i);
                        $i = $end === false ? $length : $end + 2;
                        break;
                    case 'Tf':
                        $name = $stack[count($stack) - 2][1] ?? null;
                        $font = is_string($name) ? ($fonts[$name] ?? null) : null;
                        break;
                    case 'Tj':
                        $out .= $this->decode((string) ($stack[count($stack) - 1][1] ?? ''), $font);
                        break;
                    case "'":
                    case '"':
                        $newline();
                        $out .= $this->decode((string) ($stack[count($stack) - 1][1] ?? ''), $font);
                        break;
                    case 'TJ':
                        foreach ((array) ($stack[count($stack) - 1][1] ?? []) as $item) {
                            if (is_string($item)) {
                                $out .= $this->decode($item, $font);
                            } elseif ($item < -200) {
                                $space();
                            }
                        }
                        break;
                    case 'Td':
                    case 'TD':
                        $tx = $stack[count($stack) - 2][1] ?? 0;
                        $ty = $stack[count($stack) - 1][1] ?? 0;
                        $ty != 0 ? $newline() : ($tx > 0 ? $space() : null);
                        break;
                    case 'T*':
                        $newline();
                        break;
                    case 'Tm':
                        $y = (float) ($stack[count($stack) - 1][1] ?? 0);
                        $lastY !== null && abs($y - $lastY) > 0.5 ? $newline() : ($lastY !== null ? $space() : null);
                        $lastY = $y;
                        break;
                    case 'ET':
                        $newline();
                        break;
                }

                $stack = [];
            }
        }

        return $out;
    }

    private function skipLine(string $content, int $i): int
    {
        $end = strcspn($content, "\r\n", $i);

        return $i + max(1, $end);
    }

    /**
     * Reads a (literal string) starting at $i and moves $i past it.
     */
    private function literal(string $content, int &$i): string
    {
        $depth = 0;
        $out = '';
        $length = strlen($content);

        for (; $i < $length; $i++) {
            $char = $content[$i];

            if ($char === '\\') {
                $next = $content[++$i] ?? '';

                if (ctype_digit($next)) {
                    $octal = $next;

                    while (strlen($octal) < 3 && ctype_digit($content[$i + 1] ?? '') && ($content[$i + 1] ?? '9') < '8') {
                        $octal .= $content[++$i];
                    }

                    $out .= chr(octdec($octal) & 0xFF);
                } else {
                    $out .= match ($next) {
                        'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\f",
                        "\n" => '', "\r" => '',
                        default => $next,
                    };
                }

                continue;
            }

            if ($char === '(') {
                if ($depth++ === 0) {
                    continue;
                }
            } elseif ($char === ')' && --$depth === 0) {
                $i++;

                break;
            }

            $out .= $char;
        }

        return $out;
    }

    /**
     * Reads a [ ... ] array of strings and numbers starting at $i and moves $i past it.
     *
     * @return list<string|float>
     */
    private function array(string $content, int &$i): array
    {
        $items = [];
        $length = strlen($content);
        $i++;

        while ($i < $length && $content[$i] !== ']') {
            $char = $content[$i];

            if ($char === '(') {
                $items[] = $this->literal($content, $i);
            } elseif ($char === '<' && ($content[$i + 1] ?? '') !== '<') {
                $end = strpos($content, '>', $i);
                $hex = preg_replace('/[^0-9A-Fa-f]/', '', substr($content, $i + 1, ($end === false ? $length : $end) - $i - 1)) ?? '';
                $items[] = (string) hex2bin(strlen($hex) % 2 ? $hex.'0' : $hex);
                $i = $end === false ? $length : $end + 1;
            } elseif (preg_match('/[+\-.\d]/', $char)) {
                preg_match('/[+\-]?(?:\d+\.?\d*|\.\d+)/', $content, $m, 0, $i);
                $items[] = (float) ($m[0] ?? 0);
                $i += max(1, strlen($m[0] ?? ''));
            } else {
                $i++;
            }
        }

        $i++;

        return $items;
    }

    /**
     * @param  array{map: array<int, string>, bytes: int}|null  $font
     */
    private function decode(string $bytes, ?array $font): string
    {
        if ($font !== null && $font['map'] !== []) {
            $text = '';

            foreach (str_split($bytes, $font['bytes']) as $chunk) {
                $code = 0;

                foreach (str_split($chunk) as $byte) {
                    $code = ($code << 8) | ord($byte);
                }

                $text .= $font['map'][$code] ?? ($font['bytes'] === 1 && $code >= 32 ? (string) mb_convert_encoding($chunk, 'UTF-8', 'Windows-1252') : '');
            }

            return $text;
        }

        if ($font !== null && $font['bytes'] === 2) {
            return ''; // Two-byte glyph ids without a ToUnicode table cannot be mapped to letters.
        }

        return (string) mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
    }

    private function number(string $dict, string $key): ?float
    {
        return preg_match('#/'.preg_quote($key, '#').'\s+(\d+)#', $dict, $m) ? (float) $m[1] : null;
    }
}
