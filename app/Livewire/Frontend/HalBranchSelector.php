<?php

namespace App\Livewire\Frontend;

use Exception;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class HalBranchSelector extends Component
{
    private array $branchResponseData = [];

    #[Locked]
    public array $provinces = [];

    #[Locked]
    public array $districts = [];

    #[Locked]
    public array $branches = [];

    public function mount(): void
    {
        $this->fetchHALBranches();

        // ================================================================================

        foreach ($this->branchResponseData as $branch) {
            if (!in_array($branch['province']['id'], array_column($this->provinces, 'id'))) {
                $this->provinces[] = $branch['province'];
            }
        }

        // Sort by name
        usort($this->provinces, fn($a, $b) => strcmp($a['name'], $b['name']));

        // ================================================================================

        foreach ($this->branchResponseData as $branch) {
            if (!in_array($branch['district']['id'], array_column($this->districts, 'id'))) {
                $this->districts[] = $branch['district'];
            }
        }

        // Sort by name
        usort($this->districts, fn($a, $b) => strcmp($a['name'], $b['name']));

        // ================================================================================

        foreach ($this->branchResponseData as $branch) {
            if (!in_array($branch['id'], array_column($this->branches, 'id'))) {
                $this->branches[] = $branch;
            }
        }

        // Sort by name
        usort($this->branches, fn($a, $b) => strcmp($a['name'], $b['name']));
    }

    public function render()
    {
        return view('frontend.livewire.hal-branch-selector');
    }

    public function placeholder(array $params = []): View
    {
        return view('frontend.livewire.hal-branch-selector-placeholder', $params);
    }

    public function fetchHALBranches(): void
    {
        try {

            // Cache for 60 minutes
            $this->branchResponseData = Cache::tags(['HAL'])->remember('HAL_BRANCHES_API_RESPONSE', 3600, function () {
                $requestInfo = [
                    'url'    => 'https://hal.hal-logistics.la/api/v1/listing/branches?is_within=true&is_active=true',
                    'method' => 'GET',
                ];

                $responseTime = 0;

                $response = Http::withOptions([
                    'http_errors' => false,
                    'on_stats'    => function (TransferStats $stats) use (&$responseTime) {
                        $responseTime = $stats->getTransferTime();
                    }
                ])
                ->acceptJson()
                ->get($requestInfo['url']);

                return $response->json();
            });

        } catch (Exception $e) {

            Log::error('Fetch HAL branches error', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ບໍ່ສາມາດດຶງຂໍ້ມູນສາຂາຮຸ່ງອາລຸນ',
            ]);

        }
    }
}
