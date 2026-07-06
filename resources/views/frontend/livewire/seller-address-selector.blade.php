<div class="border border-zinc-300 bg-white rounded-2xl space-y-4 p-4">

  <div class="font-semibold text-gray-900">ທີ່ຢູ່ໃນການຈັດສົ່ງ</div>

  <div class="text-gray-600 space-x-2">
    <span>ຜູ້ຮັບເຄື່ອງ:</span>
    <span class="text-gray-900 font-semibold">{{ auth()->user()->name }}</span>
    <span>{{ auth()->user()->phone }}</span>
  </div>

  <select class="form-control" wire:model.live="provinceCode" wire:change="handleProvinceChange">
    <option value="">ເລືອກແຂວງ</option>
    @foreach (\App\Utils\FormUtil::getProvinces() as $province)
      <option value="{{ $province['id'] }}" wire:key="seller-province-{{ $province['id'] }}">{{ $province['name'] }}</option>
    @endforeach
  </select>

  <select class="form-control" wire:model.live="district">
    <option value="">ເລືອກເມືອງ</option>
    @foreach ($districts as $index => $district)
      <option value="{{ $district }}" wire:key="seller-district-{{ $index }}">{{ $district }}</option>
    @endforeach
  </select>

  <input type="text" class="form-control" wire:model.live.debounce.300ms="village" placeholder="ບ້ານຢູ່ປະຈຸບັນ">

</div>
