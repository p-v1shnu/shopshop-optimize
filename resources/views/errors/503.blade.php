@extends('errors.components.layout')

@section('content')
  <div class="flex-1 py-16 px-8 space-y-4 text-center">
    <h1 class="text-2xl font-bold leading-relaxed">ກຳລັງປັບປຸງລະບົບ</h1>
    <div class="text-lg text-zinc-600">ກະລຸນາລອງໃໝ່ພາຍຫຼັງ</div>
    <a href="{{ tenant('contact_url') }}" target="_blank" rel="nofollow" class="btn btn-primary w-max mx-auto !mt-16">ຕິດຕໍ່ພວກເຮົາ</a>
  </div>
@endsection
