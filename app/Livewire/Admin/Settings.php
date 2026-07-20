<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public string $companyName = '';

    public $logo;

    public ?string $currentLogoUrl = null;

    public ?string $pageTitle = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public bool $passwordSaved = false;

    public function mount(): void
    {
        $this->companyName = Setting::get('company_name', 'GiftCardBot');
        $this->currentLogoUrl = Setting::get('logo_path')
            ? asset('storage/'.Setting::get('logo_path'))
            : null;
        $this->pageTitle = Setting::get('page_title', '');
    }

    public function save(): void
    {
        $this->validate([
            'companyName' => 'required|string|max:255',
            'logo' => 'nullable|image|max:1024',
            'pageTitle' => 'nullable|string|max:255',
        ]);

        Setting::set('company_name', $this->companyName);
        Setting::set('page_title', $this->pageTitle);

        if ($this->logo) {
            $path = $this->logo->store('logos', 'public');
            Setting::set('logo_path', $path);
            $this->currentLogoUrl = asset('storage/'.$path);
            $this->logo = null;
        }

        session()->flash('saved', true);
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'The current password is incorrect.');

            return;
        }

        $user->password = $this->newPassword;
        $user->save();

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->passwordSaved = true;
    }

    public function removeLogo(): void
    {
        Setting::set('logo_path', null);
        $this->currentLogoUrl = null;
    }

    public function render()
    {
        return view('livewire.admin.settings')->layout('layouts.admin', ['title' => 'Settings']);
    }
}
