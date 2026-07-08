<?php

namespace App\Livewire\Admin;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class AdminActivityLogsPage extends Component
{
    use WithPagination;

    public string $adminId = '';

    public string $action = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function updatedAdminId(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('admin.admin-activity-logs-page', [
            'activityLogs' => $this->activityLogsQuery()->paginate(15),
            'admins' => Admin::query()->orderBy('email')->get(['id', 'email', 'name']),
            'actions' => AdminActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ])->layout('admin.layout')
            ->title('Admin audit log');
    }

    public function formatJson(mixed $value): string
    {
        return json_encode($value ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function activityLogsQuery()
    {
        return AdminActivityLog::query()
            ->with('admin:id,email,name')
            ->when($this->adminId !== '', fn ($query) => $query->where('admin_id', $this->adminId))
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->dateFrom, fn ($query) => $query->where('created_at', '>=', $this->dateFrom.' 00:00:00'))
            ->when($this->dateTo, fn ($query) => $query->where('created_at', '<=', $this->dateTo.' 23:59:59'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
