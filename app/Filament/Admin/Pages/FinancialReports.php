<?php

namespace App\Filament\Admin\Pages;

use App\Enums\TransactionType;
use App\Filament\Admin\Widgets\MonthlyBudgetChart;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;

class FinancialReports extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.admin.pages.financial-reports';

    protected static \UnitEnum|string|null $navigationGroup = 'Tesorería';

    protected static ?string $title = 'Reportes Financieros';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermission('view_financial_reports');
    }

    public $year;

    public $currency;

    public function mount()
    {
        $this->year = now()->year;
        $this->currency = 'USD';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MonthlyBudgetChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Descargar Balance (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    return response()->streamDownload(function () {
                        echo Pdf::loadView('reports.balance-sheet-pdf', $this->getReportData())
                            ->output();
                    }, "Balance_Anual_{$this->year}.pdf");
                }),
        ];
    }

    protected function getReportData(): array
    {
        $monthlyData = [];
        $totals = ['income' => 0, 'expense' => 0, 'balance' => 0];
        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        for ($i = 1; $i <= 12; $i++) {
            $income = Transaction::whereYear('created_at', $this->year)
                ->whereMonth('created_at', $i)
                ->where('type', TransactionType::Cobro)
                ->sum('amount');

            $expense = Transaction::whereYear('created_at', $this->year)
                ->whereMonth('created_at', $i)
                ->where('type', TransactionType::Pago)
                ->sum('amount');

            $balance = $income - $expense;

            $monthlyData[$i] = [
                'label' => $months[$i - 1],
                'income' => $income,
                'expense' => $expense,
                'balance' => $balance,
            ];

            $totals['income'] += $income;
            $totals['expense'] += $expense;
            $totals['balance'] += $balance;
        }

        return [
            'year' => $this->year,
            'currency' => $this->currency,
            'monthlyData' => $monthlyData,
            'totals' => $totals,
        ];
    }
}
