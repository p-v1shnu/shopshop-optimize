<form class="p-4 space-y-4" wire:submit.prevent="save">

  <header class="space-y-1">
    <div class="font-bold">
      {{
        auth()->user()->had_complete_profile
          ? 'ແກ້ໄຂຂໍ້ມູນສ່ວນຕົວຂອງທ່ານ'
          : (auth()->user()->name !== null
            ? 'ແກ້ໄຂຂໍ້ມູນສ່ວນຕົວຂອງທ່ານ'
            : 'ເພີ່ມຂໍ້ມູນສ່ວນຕົວຂອງທ່ານ')
      }}
    </div>
    <div class="text-sm text-zinc-600">ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ ແລະ ຖືກຕ້ອງ</div>
  </header>

  <div class="space-y-1">
    <label>ຊື່ ແລະ ນາມສະກຸນ</label>
    <input type="text" class="form-control" wire:model="name">
    @error("name") <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="space-y-1">
    <label>ວັນເດືອນປີເກີດ</label>
    <div class="flex space-x-2">
      <select
        class="form-control"
        style="text-align-last: center;"
        wire:model="dobDay">
        <option value="">ວັນ</option>
        @foreach (\App\Utils\FormUtil::getDobDays() as $day)
          <option value="{{ $day }}" wire:key="profile-dob-day-{{ $day }}">{{ $day }}</option>
        @endforeach
      </select>

      <select
        class="form-control"
        style="text-align-last: center;"
        wire:model="dobMonth">
        <option value="">ເດືອນ</option>
        @foreach (\App\Utils\FormUtil::getDobMonths() as $key => $month)
          <option value="{{ $key }}" wire:key="profile-dob-month-{{ $key }}">{{ $month }}</option>
        @endforeach
      </select>

      <select
        class="form-control"
        style="text-align-last: center;"
        wire:model="dobYear">
        <option value="">ປີ</option>
        @foreach (\App\Utils\FormUtil::getDobYears(18, 90) as $year)
          <option value="{{ $year }}" wire:key="profile-dob-year-{{ $year }}">{{ $year }}</option>
        @endforeach
      </select>
    </div>
    @error("dobDay") <div class="form-error">{{ $message }}</div> @enderror
    @error("dobMonth") <div class="form-error">{{ $message }}</div> @enderror
    @error("dobYear") <div class="form-error">{{ $message }}</div> @enderror
    @error("dob") <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="space-y-1">
    <label>ເພດ</label>
    <select class="form-control" wire:model="gender">
      <option value="">ເລືອກ</option>
      @foreach (\App\Utils\FormUtil::getGenders() as $key => $gender)
        <option value="{{ $key }}" wire:key="profile-gender-{{ $key }}">{{ $gender }}</option>
      @endforeach
    </select>
    @error("gender") <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="space-y-1">
    <label>ແຂວງຢູ່ປະຈຸບັນ</label>
    <select class="form-control" wire:model.live="provinceCode" wire:change="handleProvinceChange">
      <option value="">ເລືອກ</option>
      @foreach (\App\Utils\FormUtil::getProvinces() as $province)
        <option value="{{ $province['id'] }}" wire:key="profile-province-{{ $province['id'] }}">{{ $province['name'] }}</option>
      @endforeach
    </select>
    @error("province_code") <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="space-y-1">
    <label>ເມືອງຢູ່ປະຈຸບັນ</label>
    <select class="form-control" wire:model="district">
      <option value="">ເລືອກ</option>
      @foreach ($districts as $index => $district)
        <option value="{{ $district }}" wire:key="profile-district-{{ $index }}">{{ $district }}</option>
      @endforeach
    </select>
    @error("district") <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="space-y-1">
    <label>ບ້ານຢູ່ປະຈຸບັນ</label>
    <input type="text" class="form-control" wire:model="village">
    @error("village") <div class="form-error">{{ $message }}</div> @enderror
  </div>

  @if (!auth()->user()->had_complete_profile)
    <div class="flex items-center justify-center space-x-4 !my-8 w-max mx-auto">
      <div wire:click.prevent="handleTermCheck">
        <input type="checkbox" class="form-control size-6 grow-0 shrink-0 rounded p-0 text-primary hover:cursor-pointer" wire:model="acceptTerm">
      </div>
      <label class="text-sm w-max">ຍອມຮັບ <span class="underline text-[#637D92] hover:cursor-pointer" wire:click.prevent="showTermModal">ເງື່ອນໄຂ ແລະ ຂໍ້ກຳນົດ</span> ລວມເຖິງ<br><span class="underline text-slate-500 hover:cursor-pointer" wire:click.prevent="showTermModal">ນະໂຍບາຍຄວາມເປັນສ່ວນຕົວ</span></label>
    </div>
  @endif

  <button type="submit" class="btn btn-primary mx-auto disabled:bg-zinc-400 disabled:text-zinc-600 disabled:opacity-100" {{ $acceptTerm ? '' : 'disabled btn-gray-primary' }}>ບັນທຶກ</button>

  @script
    <script>
      // Pushed by ProfileEditPage::save(): 'user-registered' the first time a profile is completed
      // (= registration), 'user-updated' on every later edit. Both feed GTM's dataLayer.
      function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^|; )' + name + '=([^;]*)'))
        return match ? decodeURIComponent(match[2]) : null
      }

      function getFbp() {
        return getCookie('_fbp')
      }

      function getFbc() {
        let fbc = getCookie('_fbc')
        if (!fbc) {
          const fbclid = new URLSearchParams(window.location.search).get('fbclid')
          if (fbclid) {
            fbc = 'fb.1.' + Date.now() + '.' + fbclid
          }
        }
        return fbc
      }

      function parseEvent(event) {
        const data = event || {}
        return { user: data.user || {}, redirectUrl: data.redirectUrl }
      }

      // Push the event to GTM's dataLayer and log whether it reached Google.
      // GTM's container script loads ASYNC, so window.google_tag_manager may still be undefined at
      // this moment even when a container is configured — do NOT gate on it (that would redirect away
      // before GTM loads and drop the queued push). Always push with eventCallback: GTM replays the
      // dataLayer queue once it loads, then invokes the callback after firing the tags (our "sent"
      // signal). The setTimeout is the absolute fallback so onComplete (the redirect) still runs if
      // GTM is genuinely absent (non-prod / tenant without a GTM id) or never calls back.
      function pushProfileEvent(payload, onComplete) {
        onComplete = onComplete || function () {}
        window.dataLayer = window.dataLayer || []

        let done = false
        const finish = function (sent) {
          if (done) return
          done = true
          if (sent) {
            console.log('[GTM] "user_profile_submit / ' + payload.profile_action + '" sent to Google successfully (event_id ' + payload.event_id + ').', payload)
          } else if (!window.google_tag_manager) {
            console.warn('[GTM] No container detected — "' + payload.profile_action + '" was pushed to dataLayer but may not have reached Google.', payload)
          }
          onComplete()
        }

        payload.eventTimeout = 1000
        payload.eventCallback = function () { finish(true) }
        window.dataLayer.push(payload)

        // Absolute fallback so the redirect never hangs if GTM is absent or never calls back.
        setTimeout(function () { finish(false) }, 1500)
      }

      $wire.on('user-registered', function (event) {
        const { user, redirectUrl } = parseEvent(event)
        pushProfileEvent({
          event: 'user_profile_submit',
          profile_action: 'register',
          user_id: user.id,
          created_at: user.created_at,
          gender: user.gender,
          first_name: user.first_name,
          last_name: user.last_name,
          ph: user.phone,
          user_province: user.province,
          campaign_code: user.campaign_code || null,
          event_id: Date.now().toString(),
          fbc: getFbc(),
          fbp: getFbp()
        }, function () {
          if (redirectUrl) {
            window.location.href = redirectUrl
          }
        })
      })

      $wire.on('user-updated', function (event) {
        const { user } = parseEvent(event)
        pushProfileEvent({
          event: 'user_profile_submit',
          profile_action: 'update',
          user_id: user.id,
          updated_at: user.updated_at,
          gender: user.gender,
          first_name: user.first_name,
          last_name: user.last_name,
          ph: user.phone,
          user_province: user.province,
          event_id: Date.now().toString(),
          fbc: getFbc(),
          fbp: getFbp()
        })
      })
    </script>
  @endscript

</form>
