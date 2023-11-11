<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Favicon -->
  <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">


    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@if(!isset($title)) Service Maintenance @else {{$title}} @endif - {{config('app.name')}}	</title>

    <!-- Styles -->


  <!-- Font -->
  <link href="https://fonts.googleapis.com/css2?family=Spartan:wght@200;300;400;600&amp;display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">

  <!-- CSS Implementing Plugins -->

  <!-- CSS Implementing Plugins -->
  <link rel="stylesheet" href="{{ asset('css/vendor.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/aos/dist/aos.css') }}">

  <!-- CSS Front Template -->
  <link rel="stylesheet" href="{{ asset('css/theme.minc619.css?v=1.0') }}">

<!--custom styling for fonts and other elements-->
<link rel="stylesheet" href="{{asset('css/custom.css') }}" >


<style>
body{
font-family:'Spartan','GD Sherpa Regular','Brandon Grotesque' !important;	font-size:14px;
}
.event_li{
	color:#000;
}
h1{
  font-size:40px !important;
}
</style>
</head>
<body>

  <!-- ========== END HEADER ========== -->

   <!-- ========== END HEADER ========== -->

       @yield('content')


	@include('layouts.plain_footer')


      </div>
    </div>
  </div>

  <!-- Go To -->
  <a class="js-go-to go-to position-fixed" href="javascript:;" style="visibility: hidden;"
     data-hs-go-to-options='{
       "offsetTop": 700,
       "position": {
         "init": {
           "right": "2rem"
         },
         "show": {
           "bottom": "2rem"
         },
         "hide": {
           "bottom": "-2rem"
         }
       }
     }'>
    <i class="bi-chevron-up"></i>
  </a>
  <!-- ========== END SECONDARY CONTENTS ========== -->


  <!-- JS Implementing Plugins -->
  <script src="{{ asset('js/vendor.min.js') }}"></script>
  <script src="{{ asset('vendor/aos/dist/aos.js') }}"></script>

  <!-- JS Front -->
  <script src="{{ asset('js/theme.min.js') }}"></script>

  <!-- JS Plugins Init. -->
  <script>
    (function() {
      // INITIALIZATION OF HEADER
      // =======================================================
      new HSHeader('#header').init()


      // INITIALIZATION OF MEGA MENU
      // =======================================================
      new HSMegaMenu('.js-mega-menu', {
          desktop: {
            position: 'left'
          }
        })


      // INITIALIZATION OF SHOW ANIMATIONS
      // =======================================================
      new HSShowAnimation('.js-animation-link')


      // INITIALIZATION OF BOOTSTRAP VALIDATION
      // =======================================================
      HSBsValidation.init('.js-validate', {
        onSubmit: data => {
          data.event.preventDefault()
          alert('Submited')
        }
      })


      // INITIALIZATION OF BOOTSTRAP DROPDOWN
      // =======================================================
      HSBsDropdown.init()


      // INITIALIZATION OF GO TO
      // =======================================================
      new HSGoTo('.js-go-to')


      // INITIALIZATION OF AOS
      // =======================================================
      AOS.init({
        duration: 650,
        once: true
      });


      // INITIALIZATION OF TEXT ANIMATION (TYPING)
      // =======================================================
      HSCore.components.HSTyped.init('.js-typedjs')
    })()
  </script>




<!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>

</body>
</html>

