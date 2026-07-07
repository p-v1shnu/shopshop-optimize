<?php

namespace App\Livewire\Admin;

use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class AdminAccountsPage extends Component
{
    public string $createName = '';

    public string $createEmail = '';

    public string $createRole = 'shop';

    public ?string $createTenantId = null;

    public string $createPassword = '';

    public ?int $resetAdminId = null;

    public string $resetPasswordValue = '';

    public function createAdmin(): void
    {
        $validated = $this->validate([
            'createName' => ['required', 'string', 'max:255'],
            'createEmail' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')],
            'createRole' => ['required', Rule::in(['super', 'shop'])],
            'createTenantId' => [
                Rule::requiredIf($this->createRole === 'shop'),
                'nullable',
                'string',
                'exists:tenants,id',
            ],
            'createPassword' => ['required', 'string', 'min:8'],
        ]);

        Admin::query()->create([
            'name' => $validated['createName'],
            'email' => $validated['createEmail'],
            'password' => Hash::make($validated['createPassword']),
            'role' => $validated['createRole'],
            'tenant_id' => $validated['createRole'] === 'shop'
                ? $validated['createTenantId']
                : null,
            'status' => 'active',
        ]);

        $this->reset([
            'createName',
            'createEmail',
            'createTenantId',
            'createPassword',
        ]);

        $this->createRole = 'shop';
    }

    public function resetPassword(): void
    {
        $validated = $this->validate([
            'resetAdminId' => ['required', 'integer', 'exists:admins,id'],
            'resetPasswordValue' => ['required', 'string', 'min:8'],
        ]);

        Admin::query()
            ->whereKey($validated['resetAdminId'])
            ->update([
                'password' => Hash::make($validated['resetPasswordValue']),
            ]);

        $this->reset(['resetAdminId', 'resetPasswordValue']);
    }

    public function setStatus(int $adminId, string $status): void
    {
        validator(
            ['admin_id' => $adminId, 'status' => $status],
            [
                'admin_id' => ['required', 'integer', 'exists:admins,id'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]
        )->validate();

        $admin = Admin::query()->findOrFail($adminId);

        if ($status === 'inactive' && $admin->id === Auth::guard('admin')->id()) {
            $this->addError('status', 'You cannot deactivate your own account.');
            return;
        }

        if (
            $status === 'inactive'
            && $admin->isSuper()
            && $admin->status === 'active'
            && Admin::query()->where('role', 'super')->where('status', 'active')->count() <= 1
        ) {
            $this->addError('status', 'At least one active super admin is required.');
            return;
        }

        $admin->update(['status' => $status]);
    }

    public function render()
    {
        return view('admin.admin-accounts-page', [
            'admins' => Admin::query()
                ->with('tenant:id,name')
                ->orderBy('email')
                ->get(),
            'tenants' => Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ])->layout('admin.layout')
            ->title('Admin Accounts');
    }
}
