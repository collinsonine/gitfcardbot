<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Settings</h1>
            <p class="text-sm text-gray-500 mt-0.5">Customize your admin panel branding</p>
        </div>
    </div>

    @if(session('saved'))
        <div class="mb-6 flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 rounded-xl px-4 py-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Settings saved successfully.
        </div>
    @endif

    <form wire:submit="save" class="max-w-lg bg-surface-card rounded-xl border border-gray-100 shadow-xs p-4 sm:p-6 space-y-5">
        {{-- Company Name --}}
        <div>
            <label for="companyName" class="block text-sm font-medium text-gray-700 mb-1.5">Company Name</label>
            <input wire:model="companyName" id="companyName" type="text"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow"
                placeholder="Your company name">
            @error('companyName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-400 mt-1.5">This appears in the sidebar header and page titles.</p>
        </div>

        {{-- Page Title Suffix --}}
        <div>
            <label for="pageTitle" class="block text-sm font-medium text-gray-700 mb-1.5">Page Title Suffix</label>
            <input wire:model="pageTitle" id="pageTitle" type="text"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow"
                placeholder="GiftCardBot">
            <p class="text-xs text-gray-400 mt-1.5">Appended to each admin page title (e.g. "Dashboard — Your Company").</p>
        </div>

        {{-- Logo Upload --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Company Logo</label>

            @if($currentLogoUrl)
                <div class="flex items-center gap-4 mb-3">
                    <img src="{{ $currentLogoUrl }}" alt="Logo" class="h-10 w-auto rounded-lg border border-gray-200">
                    <button type="button" wire:click="removeLogo"
                        class="text-xs text-red-600 hover:text-red-700 font-medium">Remove</button>
                </div>
            @endif

            <input wire:model="logo" type="file" accept="image/*"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition-colors">
            @error('logo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-400 mt-1.5">Upload a logo (max 1MB). Shown in the sidebar header.</p>
        </div>

        {{-- Submit --}}
        <div class="pt-2">
            <button type="submit" wire:loading.attr="disabled"
                class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 border border-primary-700">
                <span wire:loading.remove wire:target="save">Save Settings</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>

    {{-- Password Change --}}
    <div class="max-w-lg bg-surface-card rounded-xl border border-gray-200 shadow-xs p-4 sm:p-6 mt-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Change Password</h2>

        @if($passwordSaved)
            <div class="mb-4 flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 rounded-xl px-4 py-3 border border-emerald-200">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Password changed successfully.
            </div>
        @endif

        <form wire:submit="changePassword" class="space-y-4">
            <div>
                <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                <input wire:model="currentPassword" id="currentPassword" type="password"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                @error('currentPassword') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                <input wire:model="newPassword" id="newPassword" type="password"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                @error('newPassword') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-400 mt-1.5">Minimum 8 characters.</p>
            </div>
            <div>
                <label for="newPasswordConfirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                <input wire:model="newPasswordConfirmation" id="newPasswordConfirmation" type="password"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                @error('newPasswordConfirmation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="pt-1">
                <button type="submit" wire:loading.attr="disabled"
                    class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 border border-primary-700">
                    <span wire:loading.remove wire:target="changePassword">Change Password</span>
                    <span wire:loading wire:target="changePassword">Changing...</span>
                </button>
            </div>
        </form>
    </div>
</div>
