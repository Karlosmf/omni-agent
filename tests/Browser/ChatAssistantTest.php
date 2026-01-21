<?php

use Laravel\Dusk\Browser;

test('guest can open chat and send message', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('Luopan Concierge') // Check header
            ->assertSee('¡Hola! Soy tu asistente virtual') // Check default message
            ->click('button[wire\\:click="toggleChat"]') // Open chat
            ->pause(500)
            ->type('input[wire\\:model="input"]', 'Hola, quiero viajar a Brasil')
            ->press('button[type="submit"]')
            ->waitForText('Hola, quiero viajar a Brasil', 10) // User message appears
            ->pause(2000) // Wait for AI
            ->assertSee('Brasil'); // Expect AI to mention Brasil likely
    });
});
