<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class DashboardPage extends Component
{
    public function render()
    {
        return view('admin.dashboard-page')
            ->layout('admin.layout')
            ->title('Admin Dashboard');
    }
}
