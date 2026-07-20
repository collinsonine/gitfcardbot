<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public ?string $error = null;

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], false)) {
            $this->redirect(route('admin.dashboard'), navigate: true);

            return;
        }

        $this->error = 'Invalid credentials.';
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
