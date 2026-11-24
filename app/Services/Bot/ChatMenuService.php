<?php

namespace App\Services\Bot;

use App\Enums\ChatMenuSource;
use App\Models\ChatMenu;
use App\Models\TravelPackage;
use App\Support\KeywordMatcher;
use App\Support\ListMessage;
use Illuminate\Support\Str;

/**
 * Builds the options of a chat menu and reads back what the customer tapped. It sends nothing: the conversation service does.
 *
 * Row ids carry everything a tap needs, so nothing has to be remembered between the menu and the tap:
 * "menu:{id}:p:{value}" for an option and "menu:{id}:m:{offset}:{destination}" for "Ver más".
 */
class ChatMenuService
{
    public const ID_PREFIX = 'menu:';

    /**
     * The active menu whose keyword the customer wrote. The longest keyword wins; on a tie, the oldest menu.
     */
    public function forText(string $text): ?ChatMenu
    {
        $found = null;
        $foundLength = 0;

        foreach (ChatMenu::active()->whereNotNull('keywords')->orderBy('id')->get() as $menu) {
            $keyword = KeywordMatcher::longestMatch($text, $menu->keywordList());
            $length = $keyword === null ? 0 : mb_strlen(KeywordMatcher::normalize($keyword));

            if ($length > $foundLength) {
                $found = $menu;
                $foundLength = $length;
            }
        }

        return $found;
    }

    /**
     * The rows to send, at most ten: when more options are left, the last row is "Ver más".
     *
     * @param  ?string  $destination  The slug of the destination chosen in the previous menu (narrows a menu of packages).
     * @return list<array{id: string, title: string, description?: string}>
     */
    public function rows(ChatMenu $menu, ?string $destination = null, int $offset = 0): array
    {
        $choices = $this->choices($menu, $destination);
        $offset = max(0, $offset);
        $fits = count($choices) - $offset <= ListMessage::MAX_ROWS ? ListMessage::MAX_ROWS : ListMessage::MAX_ROWS - 1;
        $page = array_slice($choices, $offset, $fits);

        $rows = array_map(fn (array $choice) => array_filter([
            'id' => self::ID_PREFIX.$menu->id.':p:'.$choice['value'],
            'title' => ListMessage::cut($choice['title'], ListMessage::MAX_ROW_TITLE),
            'description' => filled($choice['description']) ? ListMessage::cut($choice['description'], ListMessage::MAX_ROW_DESCRIPTION) : null,
        ], fn ($value) => $value !== null), $page);

        $next = $offset + count($page);

        if ($next < count($choices)) {
            $rows[] = ['id' => self::ID_PREFIX.$menu->id.':m:'.$next.':'.($destination ?? ''), 'title' => 'Ver más…', 'description' => 'Siguientes opciones'];
        }

        return $rows;
    }

    /**
     * What a tapped row means. Null when it is not a row of an active menu (deleted or switched off since it was sent).
     * An option that no longer exists (a package taken down) comes back with a null title.
     *
     * @return array{menu: ChatMenu, more: bool, offset: int, destination: ?string, value: ?string, title: ?string, package: ?TravelPackage}|null
     */
    public function resolveTap(string $tapId): ?array
    {
        if (! str_starts_with($tapId, self::ID_PREFIX)) {
            return null;
        }

        $parts = explode(':', substr($tapId, strlen(self::ID_PREFIX)), 3);

        if (count($parts) < 3 || ! ctype_digit($parts[0])) {
            return null;
        }

        [$menuId, $kind, $rest] = $parts;
        $menu = ChatMenu::active()->find((int) $menuId);

        if (! $menu) {
            return null;
        }

        $tap = ['menu' => $menu, 'more' => false, 'offset' => 0, 'destination' => null, 'value' => null, 'title' => null, 'package' => null];

        if ($kind === 'm') {
            [$offset, $destination] = array_pad(explode(':', $rest, 2), 2, '');

            return ['more' => true, 'offset' => (int) $offset, 'destination' => $destination !== '' ? $destination : null] + $tap;
        }

        if ($kind !== 'p') {
            return null;
        }

        $tap['value'] = $rest;

        return match ($menu->source) {
            ChatMenuSource::Destinations => ['title' => $this->destinationsWithSlug($rest)[0] ?? null] + $tap,
            ChatMenuSource::Packages => $this->packageTap($tap, $rest),
            ChatMenuSource::Options => ['title' => $this->optionTitle($menu, $rest)] + $tap,
        };
    }

    /**
     * @param  array<string, mixed>  $tap
     * @return array<string, mixed>
     */
    private function packageTap(array $tap, string $value): array
    {
        $package = ctype_digit($value) ? TravelPackage::where('is_active', true)->find((int) $value) : null;

        return ['package' => $package, 'title' => $package?->title] + $tap;
    }

    /**
     * Every option of the menu, in order, before splitting into pages.
     *
     * @return list<array{value: string, title: string, description: ?string}>
     */
    private function choices(ChatMenu $menu, ?string $destination): array
    {
        return match ($menu->source) {
            ChatMenuSource::Destinations => TravelPackage::query()
                ->where('is_active', true)
                ->whereNotNull('destination')
                ->where('destination', '!=', '')
                ->selectRaw('destination, count(*) as packages_count')
                ->groupBy('destination')
                ->orderBy('destination')
                ->get()
                // "Punta Cana" and "punta cana" are one destination: the first spelling names it, the counts add up.
                ->groupBy(fn (TravelPackage $row) => Str::slug($row->destination))
                ->map(function ($rows, string $slug) {
                    $count = (int) $rows->sum('packages_count');

                    return [
                        'value' => $slug,
                        'title' => (string) $rows->first()->destination,
                        'description' => $count === 1 ? '1 paquete' : $count.' paquetes',
                    ];
                })
                ->values()
                ->all(),
            ChatMenuSource::Packages => TravelPackage::query()
                ->where('is_active', true)
                ->when($destination !== null, fn ($query) => $query->whereIn('destination', $this->destinationsWithSlug($destination)))
                ->orderBy('title')
                ->get()
                ->map(fn (TravelPackage $package) => [
                    'value' => (string) $package->id,
                    'title' => (string) $package->title,
                    'description' => collect([$package->nights ? $package->nights.' noches' : null, $package->summary ? strip_tags((string) $package->summary) : null])->filter()->implode(' · ') ?: null,
                ])
                ->all(),
            ChatMenuSource::Options => collect($menu->typedOptions())
                ->map(fn (array $option, int $index) => ['value' => $index.'-'.self::optionKey($option['title'])] + $option)
                ->values()
                ->all(),
        };
    }

    /**
     * The option a tapped row stands for. Rows carry the option's position and a key of its title, so a tap on a menu
     * sent before the options were edited never lands on a different option: a removed or renamed one gives null.
     * Rows sent before the key existed carry only the position.
     */
    private function optionTitle(ChatMenu $menu, string $value): ?string
    {
        [$index, $key] = array_pad(explode('-', $value, 2), 2, null);
        $options = $menu->typedOptions();

        if ($key === null) {
            return ctype_digit((string) $index) ? ($options[(int) $index]['title'] ?? null) : null;
        }

        foreach ($options as $option) {
            if (self::optionKey($option['title']) === $key) {
                return $option['title'];
            }
        }

        return null;
    }

    private static function optionKey(string $title): string
    {
        return substr(md5(KeywordMatcher::normalize($title)), 0, 8);
    }

    /**
     * The destinations of active packages written in a way that gives this slug ("Punta Cana" and "punta cana" are one).
     *
     * @return list<string>
     */
    private function destinationsWithSlug(string $slug): array
    {
        return TravelPackage::where('is_active', true)
            ->whereNotNull('destination')
            ->distinct()
            ->pluck('destination')
            ->filter(fn ($destination) => Str::slug((string) $destination) === $slug)
            ->values()
            ->all();
    }
}
