<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ScreenshotTest extends DuskTestCase
{
    public function test_take_screenshots()
    {
        $this->browse(function (Browser $browser) {
            $user = User::first();
            if (! $user) {
                $user = User::factory()->create();
            }

            $browser->loginAs($user)
                ->visit('http://127.0.0.1:8000/admin')
                ->pause(2000)
                ->screenshot('real_dashboard_ui')
                ->visit('http://127.0.0.1:8000/admin/consultas')
                ->pause(2000)
                ->screenshot('real_leads_pipeline')
                ->visit('http://127.0.0.1:8000/admin/presupuestos-files')
                ->pause(2000)
                ->screenshot('real_budget_ui')
                ->visit('http://127.0.0.1:8000/admin/caja')
                ->pause(2000)
                ->screenshot('real_treasury_ui');
        });
    }
}
