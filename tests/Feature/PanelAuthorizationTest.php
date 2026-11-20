<?php

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\HeroSliders\HeroSliderResource;
use App\Filament\Admin\Resources\ServiceTypes\ServiceTypeResource;
use App\Filament\Admin\Resources\Tasks\TaskResource;
use App\Filament\Admin\Resources\TravelPackages\TravelPackageResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

function staffWith(array $permissions = []): User
{
    return User::factory()->create(['role' => UserRole::Staff, 'permissions' => $permissions]);
}

test('staff with manage_users cannot open the users resource', function () {
    $this->actingAs(staffWith(['manage_users']));

    $this->get(UserResource::getUrl('index'))->assertForbidden();
});

test('staff with manage_users cannot update or delete users through the policy', function () {
    $staff = staffWith(['manage_users']);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect(Gate::forUser($staff)->allows('update', $admin))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('delete', $admin))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('create', User::class))->toBeFalse();
});

test('admin manages users but cannot delete their own account', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $other = User::factory()->create(['role' => UserRole::Staff]);

    $this->actingAs($admin);

    $this->get(UserResource::getUrl('index'))->assertSuccessful();

    expect(Gate::forUser($admin)->allows('update', $other))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $other))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $admin))->toBeFalse();
});

test('sales can open and edit customers', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs(User::factory()->create(['role' => UserRole::Sales]));

    $this->get(CustomerResource::getUrl('index'))->assertSuccessful();
    $this->get(CustomerResource::getUrl('edit', ['record' => $customer]))->assertSuccessful();
});

test('staff without permissions cannot open customers', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs(staffWith());

    $this->get(CustomerResource::getUrl('index'))->assertForbidden();
    $this->get(CustomerResource::getUrl('edit', ['record' => $customer]))->assertForbidden();
});

test('staff without permissions cannot open the catalogue resources', function () {
    $this->actingAs(staffWith());

    $this->get(TravelPackageResource::getUrl('index'))->assertForbidden();
    $this->get(HeroSliderResource::getUrl('index'))->assertForbidden();
    $this->get(ServiceTypeResource::getUrl('index'))->assertForbidden();
});

test('staff with manage_packages opens the catalogue but not the service types', function () {
    $this->actingAs(staffWith(['manage_packages']));

    $this->get(TravelPackageResource::getUrl('index'))->assertSuccessful();
    $this->get(HeroSliderResource::getUrl('index'))->assertSuccessful();
    $this->get(ServiceTypeResource::getUrl('index'))->assertForbidden();
});

test('a non-admin can only edit or delete their own tasks', function () {
    $agent = User::factory()->create(['role' => UserRole::Sales]);
    $colleague = User::factory()->create(['role' => UserRole::Sales]);
    $own = Task::create(['agent_id' => $agent->id, 'title' => 'Llamar al cliente']);
    $foreign = Task::create(['agent_id' => $colleague->id, 'title' => 'Enviar presupuesto']);

    $this->actingAs($agent);

    expect(TaskResource::canAccess())->toBeTrue()
        ->and(TaskResource::canEdit($own))->toBeTrue()
        ->and(TaskResource::canDelete($own))->toBeTrue()
        ->and(TaskResource::canEdit($foreign))->toBeFalse()
        ->and(TaskResource::canDelete($foreign))->toBeFalse();

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    expect(TaskResource::canEdit($foreign))->toBeTrue();
});
