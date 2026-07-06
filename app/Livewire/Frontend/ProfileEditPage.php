<?php

namespace App\Livewire\Frontend;

use App\Utils\FormUtil;
use App\Utils\ShopUtil;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;

class ProfileEditPage extends Component
{
    #[Rule('required', message: 'ກະລຸນາປ້ອນຊື່ ແລະ ນາມສະກຸນ')]
    public ?string $name = null;

    #[Rule('required', message: 'ກະລຸນາເລືອກປີເກີດ')]
    public ?string $dobYear = null;

    #[Rule('required', message: 'ກະລຸນາເລືອກເດືອນເກີດ')]
    public ?string $dobMonth = null;

    #[Rule('required', message: 'ກະລຸນາເລືອກວັນເກີດ')]
    public ?string $dobDay = null;

    #[Rule('required', message: 'ກະລຸນາເລືອກເພດ')]
    public ?string $gender = null;

    #[Rule('required', message: 'ກະລຸນາເລືອກແຂວງ')]
    public ?string $provinceCode = null;

    #[Rule('required', message: 'ກະລຸນາເລືອກເມືອງ')]
    public ?string $district = null;

    #[Rule('required', message: 'ກະລຸນາປ້ອນບ້ານ')]
    public ?string $village = null;

    public ?bool $acceptTerm = false;

    #[Locked]
    public array $districts = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->gender = $user->gender;
        $this->provinceCode = $user->province;
        $this->district = $user->district;
        $this->village = $user->village;

        if ($user->dob) {
            $this->dobYear = $user->dob->format('Y');
            $this->dobMonth = $user->dob->format('m');
            $this->dobDay = $user->dob->format('d');
        }

        $this->districts = FormUtil::getDistricts($this->provinceCode);

        if ($user->had_complete_profile) {
            $this->acceptTerm = true;
        }
    }

    public function render()
    {
        return view('frontend.livewire.profile-edit-page')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => true,
                'backUrl'    => ShopUtil::getHomeUrl(),
            ])
            ->title('ໂປຣຟາຍ');
    }

    public function save(): void
    {
        $validated = $this->validate();

        try {
            $dob = $validated['dobYear'] . '-' . $validated['dobMonth'] . '-' . $validated['dobDay'];
            if (!CarbonImmutable::parse($dob)->isValid()) {
                $this->addError('dob', 'ວັນເດືອນປີເກີດບໍ່ຖືກຕ້ອງ');
                return;
            }

            if (!$this->acceptTerm) {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ກະລຸນາຍອມຮັບເງື່ອນໄຂ ແລະ ຂໍ້ກຳນົດ',
                ]);
                return;
            }

            $user = auth()->user();
            $isNewUser = $user->had_complete_profile;

            Log::info('User before update', $user->toArray());

            $user->update([
                'name'     => trim($validated['name']),
                'gender'   => trim($validated['gender']),
                'dob'      => trim($dob),
                'province' => trim($validated['provinceCode']),
                'district' => trim($validated['district']),
                'village'  => trim($validated['village']),
            ]);

            Log::info('User after update', $user->toArray());

            // ================================================================================

            if (!$isNewUser) {
                // First-time profile completion = a successful registration.
                // Hand the freshly-saved user to GTM (dataLayer) on the client, then redirect home.
                $this->dispatch('user-registered',
                    user: [
                        'id'            => $user->id,
                        'created_at'    => $user->created_at?->toIso8601String(),
                        'gender'        => $user->gender,
                        'first_name'    => $user->name,
                        'last_name'     => null,
                        'phone'         => $user->phone,
                        'province'      => $user->province,
                        'campaign_code' => null,
                    ],
                    redirectUrl: ShopUtil::getHomeUrl(),
                );
                return;
            }

            // Returning user edited their (already-complete) profile.
            $this->dispatch('user-updated',
                user: [
                    'id'         => $user->id,
                    'updated_at' => $user->updated_at?->format('Y-m-d H:i:s'),
                    'gender'     => $user->gender,
                    'first_name' => $user->name,
                    'last_name'  => null,
                    'phone'      => $user->phone,
                    'province'   => $user->province,
                ],
            );

            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'success',
                'message' => 'ບັນທຶກສຳເລັດ',
            ]);

        } catch (Exception $e) {

            Log::info('Edit profile exception', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ບໍ່ສາມາດບັນທືກຂໍ້ມູນໄດ້ ກະລຸນາລອງໃໝ່',
            ]);
        }
    }

    public function handleProvinceChange(): void
    {
        $this->district = null;
        $this->districts = FormUtil::getDistricts($this->provinceCode);
    }

    public function handleTermCheck(): void
    {
        if ($this->acceptTerm) {
            $this->acceptTerm = false;
            return;
        }
        $this->showTermModal();
    }

    public function showTermModal(): void
    {
        $this->dispatch('openModal', 'frontend.accept-term-modal', [
            'isAccept' => $this->acceptTerm,
        ]);
    }

    #[On('acceptTerm')]
    public function handleAcceptTerm(array $args): void
    {
        $this->acceptTerm = $args['isAccept'];
    }
}
