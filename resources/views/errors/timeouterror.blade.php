@extends('layouts.plain_header')
@section('content')

  <!-- ========== MAIN CONTENT ========== -->
  <main id="content" role="main">
    <!-- Content -->
    <div class="container text-center">
      <div class="mb-3">
        <img class="img-fluid" src="{{ asset('svg/illustrations/oc-error.svg') }}" alt="404 page" style="width: 20rem;">
      </div>

      <div class="mb-4">
        <p class="fs-4 mb-0" style="line-height:45px !important;padding:20px 35px 10px 35px;font-size:25px !important;font-weight:600;color:#000;">@if(isset($error)){{ $error }} @elseif(isset($error_msg))
        {{ $error_msg }}        
        @else
          <i>Oops!</i> Looks like you ran into a problem, please check your internet, if the problem persists, please check back later!
           @endif </p>
        <p class="fs-4 text-black">You can as well <a class="link" href="{{ route('contact-us') }}">Reach out to us</a> here!.</p>
      </div>

      <a class="btn btn-primary" href="{{ route('login') }}" style="background-color:#0d2345;border-radius:50px;border:0;height:auto;padding:20px 35px 20px 35px"> <i class="bi-chevron-left small ms-1"></i> Go back home</a>
    </div>
    <!-- End Content -->
  </main>

@endsection