@extends('layouts.plain_header')
@section('content')

  <!-- ========== MAIN CONTENT ========== -->
  <main id="content" role="main">
    <!-- Content -->
    <div class="container text-center" style="padding:50px 0">
      <div class="mb-3">
        <img class="img-fluid" src="{{ asset('img/presenter.webp') }}" lazyloading alt="Index page" style="width:400px;height:400px;">
      </div>

      <div class="mb-4">
        <p class="fs-4 mb-0">Oops! you are at the end of the road here... Kindly revert</p></p>
      </div>

    {{--      <a class="btn btn-primary" href="{{ route('home_base') }}" style="background-color:#0d2345;border-radius:50px;border:0;height:auto;padding:20px 35px 20px 35px"> <i class="bi-chevron-left small ms-1"></i> </a> --}}
    </div>
    <!-- End Content -->
  </main>

@endsection
