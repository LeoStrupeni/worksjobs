@section('link_by_page')
@endsection

@section('style_by_page')
  <style>
    .img-carousel {
      height: 100vh!important;
    }
    @media (max-width: 576px) {
      .img-carousel {
        height: 50vh !important;
      }
    }
    
    .select2-selection--single, .select2-selection__arrow, .select2-selection__clear{
      height: 37.6px!important;
    }
    .select2-selection__rendered{
      padding-top: 4px;
    }
    .ir-arriba {
      display:none;
      /* padding:5px; */
      background:#ffffff;
      font-size:15px;
      color:#000000;
      cursor:pointer;
      position: fixed;
      bottom:20px;
      right:80px;
      border-radius: 50% !important;
      z-index: 2;
    }
  </style>
@endsection