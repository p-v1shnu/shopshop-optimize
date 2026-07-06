<?php

namespace App\Livewire\Frontend;

use App\Models\OtpLog;
use App\Models\User;
use App\Utils\OtpUtil;
use App\Utils\ShopUtil;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use LivewireUI\Modal\ModalComponent;
use Tzsk\Otp\Facades\Otp;

class OtpLoginModal extends ModalComponent
{
    #[Url]
    public string $redirect = ''; // Redirect URI

    #[Locked]
    public string $step = 'phone';

    public string $phone = '';

    public string $otp = '';

    #[Locked]
    public User | null $user = null;

    public string | null $backUrl = null;

    public function render()
    {
        return view('frontend.livewire.otp-login-modal');
    }

    public function sendOtp(): void
    {
        $this->phone = str_replace(' ', '', $this->phone);

        if ($this->phone === '') {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ກະລຸນາປ້ອນເບີໂທລະສັບ',
            ]);
            return;
        }

        if (!preg_match('/^(20[25789]\d{7}|30[59]\d{6})$/', $this->phone)) {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ເບີໂທລະສັບບໍ່ຖືກຕ້ອງ',
            ]);
            return;
        }

        // ================================================================================
        // Check rate limit
        // ================================================================================

        $key = 'otp-login:phone-' . $this->phone;
        $perMinute = 5; // Allow 5 attempts per minute

        if (RateLimiter::tooManyAttempts($key, $perMinute)) {
            // $seconds = RateLimiter::availableIn($key);
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ທ່ານຮ້ອງຂໍ OTP ຫຼາຍຄັ້ງຕິດຕໍ່ກັນ. ກະລຸນາລໍຖ້າ ແລ້ວລອງໃໝ່ພາຍຫຼັງ',
            ]);
            return;
        }

        RateLimiter::hit($key, 300); // Key expires in 5 minutes (300 seconds)

        // ================================================================================
        // Send OTP
        // ================================================================================

        $hash = hash('sha256', $this->phone);
        $otpCode = Otp::digits(6)->expiry(30)->generate($hash);
        $createdAt = CarbonImmutable::now();

        Log::info('Create hash for Login by Phone', [
            'phone' => $this->phone,
            'hash'  => $hash,
            'otp'   => $otpCode,
        ]);

        try {
            if (app()->environment(['production'])) {
                $message = 'ທ່ານໄດ້ຮັບລະຫັດ: ' . $otpCode . ' ຈາກ ' . tenant('otp_site_name');
                $otpResult = OtpUtil::sendSMS($this->phone, $message);
                OtpLog::create([
                    'provider'           => $otpResult['provider'],
                    'provider_reference' => $otpResult['id'],
                    'msisdn'             => $this->phone,
                    'otp'                => $otpCode,
                    'data'               => $otpResult['responseData'],
                    'expired_at'         => $createdAt->addMinutes(30),
                ]);
            } else {
                Log::info('OTP: ' . $otpCode);
            }

            $this->step = 'otp';

        } catch (Exception $e) {

            Log::error('Send OTP error', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ບໍ່ສາມາດສົ່ງ OTP ໄດ້',
            ]);
        }
    }

    public function backToPhoneStep(): void
    {
        $this->step = 'phone';
    }

    public function verifyOtp(): void
    {
        $this->otp = str_replace(' ', '', $this->otp);

        if ($this->otp === '') {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ກະລຸນາປ້ອນລະຫັດ OTP',
            ]);
            return;
        }

        // Verify OTP
        $hash = hash('sha256', $this->phone);

        Log::info('Check hash for Login by Phone', [
            'msisdn' => $this->phone,
            'hash'   => $hash,
            'otp'    => $this->otp,
        ]);

        // Check OTP
        $isValid = Otp::digits(6)
            ->expiry(30)
            ->check($this->otp, $hash);

        if (!$isValid) {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ລະຫັດ OTP ບໍ່ຖືກຕ້ອງ',
            ]);
            return;
        }

        $this->processLogin();
    }

    private function processLogin(): void
    {
        // Check if user already register
        $this->user = User::query()
            ->where('phone', '=', $this->phone)
            ->limit(1)
            ->first();

        // Login or Register
        if (!$this->user) {
            $this->user = User::create([
                'type'   => 'phone',
                'role'   => 'user',
                'phone'  => $this->phone,
                'status' => 'active',
            ]);
            Log::info('Register user', $this->user->toArray());
        } else {
            Log::info('Login user', $this->user->toArray());
        }

        if ($this->user->banned_at !== null) {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ທ່ານບໍ່ໄດ້ຮັບອະນຸຍາດໃຫ້ເຂົ້າສູ່ລະບົບຖາວອນ',
            ]);
            return;
        }

        if ($this->user->status !== 'active') {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ທ່ານບໍ່ໄດ້ຮັບອະນຸຍາດໃຫ້ເຂົ້າສູ່ລະບົບ',
            ]);
            return;
        }

        request()->session()->regenerate();

        auth()->login($this->user, true);

        $redirectUrl = $this->redirect !== ''
            ? $this->redirect
            : ShopUtil::getHomeUrl();

        if ($this->backUrl) {
            $redirectUrl = $this->backUrl;
        }

        Log::info('Login redirect url', ['url' => $redirectUrl]);

        $this->redirect($redirectUrl);
    }
}
