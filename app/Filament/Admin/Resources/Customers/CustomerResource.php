<?php

namespace App\Filament\Admin\Resources\Customers;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Admin\Resources\Customers\Pages\EditCustomer;
use App\Filament\Admin\Resources\Customers\Pages\ListCustomers;
use App\Filament\Admin\Resources\Customers\RelationManagers\BookingsRelationManager;
use App\Filament\Admin\Resources\Customers\RelationManagers\LeadsRelationManager;
use App\Filament\Admin\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Admin\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static UnitEnum|string|null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $slug = 'clientes';

    /**
     * Customers share the User model with staff accounts, whose policy is admin-only.
     * The sales team needs them, so this resource decides its own access.
     */
    private static function canManageCustomers(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_customers', 'manage_leads', 'manage_bookings']) ?? false;
    }

    public static function canAccess(): bool
    {
        return self::canManageCustomers();
    }

    public static function canViewAny(): bool
    {
        return self::canManageCustomers();
    }

    public static function canCreate(): bool
    {
        return self::canManageCustomers();
    }

    public static function canView(Model $record): bool
    {
        return self::canManageCustomers();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageCustomers();
    }

    public static function canDelete(Model $record): bool
    {
        return self::canManageCustomers();
    }

    public static function canDeleteAny(): bool
    {
        return self::canManageCustomers();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Customer);
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LeadsRelationManager::class,
            BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
