<?php

namespace App\Livewire;

use App\Models\Lead;
use Livewire\Attributes\Rule;
use Livewire\Component;

class PublicLeadForm extends Component
{
    #[Rule('required|min:3')]
    public $name = '';

    #[Rule('required|email')]
    public $email = '';

    #[Rule('nullable|numeric')]
    public $phone = '';

    #[Rule('required|min:10')]
    public $message = '';

    public $success = false;

    public function submit()
    {
        $this->validate();

        Lead::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            'status' => 'new',
            'source' => 'web_form',
        ]);

        $this->reset(['name', 'email', 'phone', 'message']);
        $this->success = true;
    }

    public function render()
    {
        return view('livewire.public-lead-form');
    }
}
