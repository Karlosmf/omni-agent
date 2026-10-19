<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Keeps one customer per person across channels. A contact on Instagram or Messenger starts anonymous;
 * once the chat reveals a phone or email we attach it, or fold the anonymous record into the customer we already know.
 */
class CustomerMergeService
{
    /**
     * Use a phone / email given in the chat to complete or merge the lead's customer.
     */
    public function adoptContact(Lead $lead, ?string $phone, ?string $email): void
    {
        $customer = $lead->customer;
        $phone = PhoneNumber::toE164($phone);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL) ?: null;

        if (! $customer || ($phone === null && $email === null)) {
            return;
        }

        if ($phone !== null && blank($customer->phone)) {
            $customer = $this->claim($lead, $customer, User::where('role', UserRole::Customer)->whereIn('phone', PhoneNumber::lookupVariants($phone)), ['phone' => $phone]);
        }

        if ($email !== null && blank($customer->email)) {
            $this->claim($lead, $customer, User::where('role', UserRole::Customer)->where('email', $email), ['email' => $email]);
        }
    }

    /**
     * Move everything of $duplicate to $target and remove the duplicate.
     */
    public function merge(User $duplicate, User $target): void
    {
        if ($duplicate->is($target)) {
            return;
        }

        DB::transaction(function () use ($duplicate, $target) {
            Lead::where('customer_id', $duplicate->id)->update(['customer_id' => $target->id]);
            Booking::where('customer_id', $duplicate->id)->update(['customer_id' => $target->id]);

            $update = [];
            foreach (['phone', 'email'] as $field) {
                if (blank($target->{$field}) && filled($duplicate->{$field})) {
                    $update[$field] = $duplicate->{$field};
                }
            }

            $duplicate->delete();
            $target->update($update);
        });
    }

    /**
     * @param  Builder<User>  $matches
     * @param  array<string, string>  $contact
     */
    private function claim(Lead $lead, User $customer, $matches, array $contact): User
    {
        $existing = $matches->where('id', '!=', $customer->id)->first();

        if ($existing === null) {
            $customer->update($contact);

            return $customer;
        }

        // Only a customer that is still anonymous may be folded into another one.
        if (! $customer->is_guest || filled($customer->phone) || filled($customer->email)) {
            return $customer;
        }

        if (blank($existing->name) || $existing->name === 'Web Guest') {
            $existing->update(['name' => $customer->name]);
        }

        $this->merge($customer, $existing);
        $lead->forceFill(['customer_id' => $existing->id])->saveQuietly();
        $lead->setRelation('customer', $existing);

        return $existing;
    }
}
