@extends('errors.components.layout')

@section('content')
  <div class="flex-1 py-16 px-8 space-y-4 text-center">
    {{--<h1 class="text-2xl font-bold leading-relaxed">{{ $exception ? $exception->getMessage() : 'ບໍ່ພົບຂໍ້ມູນທີ່ທ່ານຄົ້ນຫາ' }}</h1>--}}
    <h1 class="text-2xl font-bold leading-relaxed">ບໍ່ພົບຂໍ້ມູນທີ່ທ່ານຄົ້ນຫາ</h1>
    <div class="text-lg text-zinc-600">ກະລຸນາກວດສອບຂໍ້ມູນຄືນ</div>
    <a href="{{ \App\Utils\ShopUtil::getHomeUrl() }}" class="btn btn-primary w-max mx-auto !mt-16">ກັບໜ້າຫຼັກ</a>
  </div>
@endsection
