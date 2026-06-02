<?php

namespace App\Actions\Leads;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CaptureLeadAction
{
    /**
     * Handle the creation of a Lead and its associated Customer (User).
     *
     * @param  array  $data
     * @return Lead
     */
    public function execute(array $data): Lead
    {
        $customer = $this->findOrCreateCustomer($data);

        return Lead::create([
            'customer_id'           => $customer->id,
            'travel_package_id'     => $data['travel_package_id'] ?? null,
            'customer_name'         => $customer->name,
            'customer_phone'        => $customer->phone ?? 'Sin teléfono',
            'customer_email'        => $customer->email,
            'customer_budget'       => $data['customer_budget'] ?? null,
            'source'                => $data['source'] ?? 'unknown',
            'raw_message'           => $data['raw_message'] ?? '',
            'status'                => LeadStatus::New,
            'temperature'           => LeadTemperature::Cool,
            'ai_data'               => $data['ai_data'] ?? [],
            'needs_human_attention' => false,
        ]);
    }

    /**
     * Find an existing user by email or phone, or create a new one as a customer.
     */
    private function findOrCreateCustomer(array $data): User
    {
        $email = !empty($data['customer_email']) ? trim($data['customer_email']) : null;
        $phone = !empty($data['customer_phone']) ? trim($data['customer_phone']) : null;
        $name  = !empty($data['customer_name']) ? trim($data['customer_name']) : 'Web Guest';

        $query = User::query()->where('role', UserRole::Customer);

        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        } else {
            // Can't reliably find without email or phone, so we'll just create one below
            // But we add a dummy condition that fails
            $query->whereRaw('1 = 0');
        }

        $customer = $query->first();

        if (!$customer) {
            $customer = User::create([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'role'     => UserRole::Customer,
                'password' => Hash::make(Str::random(16)), // Random secure password
            ]);
        } else {
            // Update name or phone if missing
            $update = [];
            if ($name !== 'Web Guest' && ($customer->name === 'Web Guest' || empty($customer->name))) {
                $update['name'] = $name;
            }
            if ($phone && empty($customer->phone)) {
                $update['phone'] = $phone;
            }
            if (!empty($update)) {
                $customer->update($update);
            }
        }

        return $customer;
    }
}
