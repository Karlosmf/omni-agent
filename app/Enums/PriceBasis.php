<?php

namespace App\Enums;

enum PriceBasis: string
{
    case PorPersona = 'por_persona';
    case BaseDoble = 'base_doble';
    case PorPersonaBaseDoble = 'por_persona_base_doble';
    case BaseTriple = 'base_triple';
    case BaseCuadruple = 'base_cuadruple';
    case PrecioFijo = 'precio_fijo';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::PorPersona => 'Por persona',
            self::BaseDoble => 'En base doble',
            self::PorPersonaBaseDoble => 'Por persona, en base doble',
            self::BaseTriple => 'En base triple',
            self::BaseCuadruple => 'En base cuádruple',
            self::PrecioFijo => 'Precio fijo (sin importar pasajeros)',
        };
    }

    /**
     * Short label for use inside service item badges.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::PorPersona => 'x pax',
            self::BaseDoble => 'base doble',
            self::PorPersonaBaseDoble => 'x pax (base 2)',
            self::BaseTriple => 'base triple',
            self::BaseCuadruple => 'base cuádruple',
            self::PrecioFijo => 'precio fijo',
        };
    }

    /**
     * Whether this basis produces a fixed total regardless of passenger count.
     */
    public function isFixed(): bool
    {
        return $this === self::PrecioFijo;
    }

    /**
     * The occupancy unit size used in the ceil-based formula.
     * Only meaningful for non-fixed bases.
     *
     * Formula: total = price × ceil(passengers / basisSize()) × basisSize()
     *
     * Example — base_doble ($1000/pax):
     *   1 pax  → ceil(1/2)=1 unit × 2 slots = 2 billed → $2 000
     *   2 pax  → ceil(2/2)=1 unit × 2 slots = 2 billed → $2 000
     *   3 pax  → ceil(3/2)=2 units × 2 slots = 4 billed → $4 000
     *   4 pax  → ceil(4/2)=2 units × 2 slots = 4 billed → $4 000
     */
    public function basisSize(): int
    {
        return match ($this) {
            self::PorPersona => 1,
            self::BaseDoble, self::PorPersonaBaseDoble => 2,
            self::BaseTriple => 3,
            self::BaseCuadruple => 4,
            self::PrecioFijo => 1, // irrelevant; isFixed() short-circuits before using this
        };
    }

    /**
     * Calculates the multiplier to apply to a unit price given a passenger count.
     * For fixed-price services, always returns 1 (price doesn't scale).
     */
    public function multiplierFor(int $passengers): int
    {
        if ($this->isFixed()) {
            return 1;
        }

        $basisSize = $this->basisSize();

        return (int) ceil($passengers / $basisSize) * $basisSize;
    }

    /**
     * Kept for backwards compatibility.
     */
    public function minimumPassengers(): int
    {
        return $this->basisSize();
    }

    /**
     * Options array suitable for Filament Select — package-level (no fixed price).
     *
     * @return array<string, string>
     */
    public static function toOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $case) => $case === self::PrecioFijo)
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /**
     * Options array for service-level selects (includes fixed price).
     *
     * @return array<string, string>
     */
    public static function toServiceOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
