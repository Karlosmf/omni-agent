<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class FinancialCalculatorWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.financial-calculator-widget';
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    public $grossAmount = 0;
    public $taxBankPercent = 1.2;
    public $taxIibbPercent = 3.5;
    public $platformFeePercent = 0.0;
    public $surchargePercent = 0.0;

    public $taxBankAmount = 0;
    public $taxIibbAmount = 0;
    public $platformFeeAmount = 0;
    public $netAmount = 0;

    public function updated($propertyName)
    {
        $this->calculate();
    }

    public function calculate()
    {
        $gross = floatval($this->grossAmount);

        $this->taxBankAmount = $gross * ($this->taxBankPercent / 100);
        $this->taxIibbAmount = $gross * ($this->taxIibbPercent / 100);
        $this->platformFeeAmount = $gross * ($this->platformFeePercent / 100);

        // Surcharge might be added to gross or calculated differently, assuming standard deduction logic here
        // If surcharge is "recargo", it usually adds to the total. But if we are calculating "Neto from Bruto" (Cobro),
        // we usually deduct costs.
        // Let's assume standard "Descuentos" logic for now as per req: "Neto Real" descontando impuestos.

        $this->netAmount = $gross - $this->taxBankAmount - $this->taxIibbAmount - $this->platformFeeAmount;
    }
}
