<!-- ========== FOOTER ========== -->

<footer class="position-absolute start-0 end-0 bottom-0">
    <div class="container pb-5 content-space-b-sm-1">
      <div class="row justify-content-between align-items-center">
        <div class="col-sm mb-3 mb-sm-0">
          <p class="small mb-0">&copy; {{ config('app.name') }} . {{date('Y')}} . All rights reserved.</p>
        </div>

        <div class="col-sm-auto">
          <!-- Socials -->
          <ul class="list-inline mb-0">
           <li class="list-inline-item">
            <a class="btn btn-soft-secondary btn-xs btn-icon" href="https://facebook.com/{{config('app.name')}}">
              <i class="bi-facebook"></i>
            </a>
          </li>

          <li class="list-inline-item">
            <a class="btn btn-soft-secondary btn-xs btn-icon" href="https://twitter.com/{{config('app.name')}}">
              <i class="bi-twitter"></i>
            </a>
          </li>

          <li class="list-inline-item">
            <a class="btn btn-soft-secondary btn-xs btn-icon" href="https://github.com/skimmfish">
              <i class="bi-github"></i>
            </a>
          </li>
		  </ul>
          <!-- End Socials -->
        </div>
      </div>
    </div>
  </footer>
  <!-- ========== END FOOTER ========== -->
