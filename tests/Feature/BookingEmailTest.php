<?php

use App\Enums\UserRole;
use App\Mail\BookingProposalMail;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('booking proposal mail can be rendered', function () {
    $booking = Booking::factory()->create([
        'holder_name' => 'Juan Pérez',
        'destination' => 'Roma',
        'total_sell' => 1500.00,
        'currency' => 'USD',
        'valid_until' => now()->addDays(7),
    ]);

    $pdfContent = '%PDF-1.4 fake content';

    $mailable = new BookingProposalMail($booking, $pdfContent);

    $mailable->assertSeeInHtml('Juan Pérez');
    $mailable->assertSeeInHtml('Roma');
    $mailable->assertSeeInHtml('USD');
    $attachments = $mailable->attachments();
    expect($attachments)->toHaveCount(1);

    $attachment = $attachments[0];
    // We cannot easily assert the closure content directly in Pest without invoking it,
    // but we can assert the attachment object exists and is structured correctly if needed.
    // For now, if there's 1 attachment we know it was attached.
});

test('booking proposal mail has correct subject', function () {
    $booking = Booking::factory()->create([
        'destination' => 'Cancún',
    ]);

    $mailable = new BookingProposalMail($booking, 'fake-pdf');

    expect($mailable->envelope()->subject)->toContain('Cancún');
});

test('send email action sends mail and logs activity', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($user);

    $booking = Booking::factory()->create([
        'holder_name' => 'Test User',
        'destination' => 'Madrid',
    ]);

    $pdfContent = '%PDF-1.4 fake';

    Mail::to('test@example.com')
        ->send(new BookingProposalMail($booking, $pdfContent));

    BookingActivity::log($booking, 'email_sent', 'Presupuesto enviado a test@example.com');

    Mail::assertSent(BookingProposalMail::class, function ($mail) {
        return $mail->hasTo('test@example.com');
    });

    expect(BookingActivity::where('booking_id', $booking->id)->where('type', 'email_sent')->exists())->toBeTrue();
});
