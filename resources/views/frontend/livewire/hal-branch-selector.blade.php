<div
  class="border border-zinc-300 bg-white rounded-2xl space-y-4 p-4"
  x-data="{
    provinceData: @js($provinces ?? []),
    districtData: @js($districts ?? []),
    branchData: @js($branches ?? []),
    selectedProvinceId: '',
    selectedDistrictId: '',
    selectedBranchId: '',
    get provinces() {
      return this.provinceData
    },
    get districts() {
      const provinceId = parseInt(this.selectedProvinceId);
      const districts = this.districtData.filter(district => district.province_id === provinceId);
      return districts;
    },
    get branches() {
      const provinceId = parseInt(this.selectedProvinceId);
      const districtId = parseInt(this.selectedDistrictId);
      const branches = this.branchData.filter(branch => branch.province_id = provinceId && branch.district_id === districtId);
      return branches;
    }
  }">

  <div class="text-gray-600 space-x-2">
    <span>ຜູ້ຮັບເຄື່ອງ:</span>
    <span class="text-gray-900 font-semibold">{{ auth()->user()->name }}</span>
    <span>{{ auth()->user()->phone }}</span>
  </div>

  <div class="flex items-center space-x-2">
    <img src="{{ \App\Utils\AppUtil::asset('resources/images/hal-logo.png') }}" class="size-8"/>
    <span class="text-gray-900 font-semibold">ຈັດສົ່ງໂດຍ ຮຸ່ງອາລຸນ ຂົນສົ່ງດ່ວນ</span>
  </div>

  <select
    class="form-control"
    x-model="selectedProvinceId"
    x-on:change="
      selectedDistrictId = ''
      selectedBranch = ''
      $wire.dispatch('halBranchSelected', {
        provinceId: selectedProvinceId,
        districtId: selectedDistrictId,
        branchId: selectedBranchId
      })
    ">
    <option value="">ເລືອກແຂວງ</option>
    <template x-for="province in provinces" :key="province.id">
      <option :value="province.id" x-text="province.name"></option>
    </template>
  </select>

  <select
    class="form-control"
    x-model="selectedDistrictId"
    x-on:change="
      selectedBranchId = ''
      $wire.dispatch('halBranchSelected', {
        provinceId: selectedProvinceId,
        districtId: selectedDistrictId,
        branchId: selectedBranchId
      })
    ">
    <option value="">ເລືອກເມືອງ</option>
    <template x-for="district in districts" :key="district.id">
      <option :value="district.id" x-text="district.name"></option>
    </template>
  </select>

  <select
    class="form-control"
    x-model="selectedBranchId"
    x-on:change="$wire.dispatch('halBranchSelected', {
      provinceId: selectedProvinceId,
      districtId: selectedDistrictId,
      branchId: selectedBranchId
    })">
    <option value="">ເລືອກສາຂາ</option>
    <template x-for="branch in branches" :key="branch.id">
      <option :value="branch.id" x-text="branch.name"></option>
    </template>
  </select>

</div>
