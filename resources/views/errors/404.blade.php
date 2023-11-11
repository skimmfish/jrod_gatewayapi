@extends('layouts.plain_header')
@section('content')

  <!-- ========== MAIN CONTENT ========== -->
  <main id="content" role="main">
    <!-- Content -->
    <div class="container text-center">
      <div class="mb-3">
        <img class="img-fluid" src="{{ asset('img/error.png') }}" alt="404 page" style="width: 30rem;">
      </div>

      <div class="mb-4">
        <p class="fs-4 mb-0">Oops! Looks like you are in the desert here! You probably followed a bad link!</p></p>
      </div>

      <a class="btn btn-primary" href="{{ route('home_base') }}" style="background-color:#0d2345;border-radius:50px;border:0;height:auto;padding:20px 35px 20px 35px"> <i class="bi-chevron-left small ms-1"></i> Go back home</a>
    </div>
    <!-- End Content -->
  </main>

@endsection
