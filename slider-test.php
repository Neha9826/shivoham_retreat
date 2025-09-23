<?php
include_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Slider Test</title>

  <!-- jQuery -->
  <script src="<?= BASE_URL ?>js/vendor/jquery-1.12.4.min.js"></script>

  <!-- Owl Carousel CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>css/owl.carousel.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/owl.theme.default.min.css">

  <!-- Owl Carousel JS -->
  <script src="<?= BASE_URL ?>js/owl.carousel.min.js"></script>

  <style>
    body { margin:0; padding:0; font-family: Arial, sans-serif; }
    .slider_active .item {
      height: 400px;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
    }
    .slide1 { background: url('<?= BASE_URL ?>img/banner/banner.png') center/cover no-repeat; }
    .slide2 { background: url('<?= BASE_URL ?>img/banner/banner2.png') center/cover no-repeat; }
  </style>
</head>
<body>

<div class="slider_area">
  <div class="slider_active owl-carousel">
    <div class="item slide1">Slide 1</div>
    <div class="item slide2">Slide 2</div>
  </div>
</div>

<script>
$(document).ready(function(){
  console.log("Testing OwlCarousel init...");

  if (typeof $.fn.owlCarousel === "undefined") {
    console.error("❌ OwlCarousel not loaded!");
    return;
  }

  $('.slider_active').owlCarousel({
    loop: true,
    margin: 0,
    items: 1,
    autoplay: true,
    autoplayTimeout: 3000,
    autoplaySpeed: 800,
    dots: true,
    nav: true,
    navText: [
      '<i class="fa fa-angle-left"></i>',
      '<i class="fa fa-angle-right"></i>'
    ]
  });

  console.log("✅ OwlCarousel initialized.");
});
</script>

<!-- FontAwesome for arrows -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</body>
</html>
