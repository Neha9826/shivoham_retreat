<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>3D Circular Carousel — Standalone Demo</title>
  <style>
    *{box-sizing:border-box}
    body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#f3f4f6;padding:30px}

    /* container + perspective */
    .small-slider{
      width:100%;
      max-width:980px;
      height:420px;
      margin:0 auto;
      position:relative;
      perspective:1400px;
      -webkit-perspective:1400px;
    }

    /* the ring that we rotate */
    .carousel-ring{
      width:100%;
      height:100%;
      position:absolute;
      top:0;left:0;
      transform-style:preserve-3d;
      transition:transform 1000ms cubic-bezier(.2,.8,.2,1);
      will-change:transform;
    }

    /* each item (absolute centered) */
    .carousel-item{
      position:absolute;
      top:50%;
      left:50%;
      transform-style:preserve-3d;
      transition:transform 900ms cubic-bezier(.2,.8,.2,1), opacity 700ms;
      will-change:transform,opacity;
    }

    .carousel-item img{
      display:block;
      width:260px;
      height:160px;
      object-fit:cover;
      border-radius:12px;
      box-shadow:0 18px 40px rgba(0,0,0,0.18);
      backface-visibility:hidden;
      -webkit-backface-visibility:hidden;
      border:6px solid rgba(255,255,255,0.85);
    }

    /* nav buttons (centered vertically, left/right) */
    .carousel-nav{
      position:absolute;
      top:50%;
      left:0;right:0;
      transform:translateY(-50%);
      display:flex;justify-content:space-between;align-items:center;
      pointer-events:none; /* container ignores clicks */
      z-index:50;
    }
    .carousel-nav button{
      pointer-events:auto; /* enable click on buttons */
      border:0;background:rgba(255,255,255,0.95);width:44px;height:44px;border-radius:50%;
      display:inline-flex;align-items:center;justify-content:center;font-size:20px;cursor:pointer;
      box-shadow:0 10px 25px rgba(0,0,0,0.12);transition:transform .18s ease,background .18s;
    }
    .carousel-nav button:hover{transform:scale(1.06)}

    /* responsive tweak */
    @media (max-width:720px){
      .small-slider{height:360px}
      .carousel-item img{width:210px;height:130px}
    }

    @media (max-width:420px){
      .small-slider{height:300px}
      .carousel-item img{width:180px;height:110px}
    }

    /* small helper so the page shows a hint if JS is disabled */
    .no-js-hint{max-width:980px;margin:12px auto;color:#444;text-align:center}
  </style>
</head>
<body>

  <div class="no-js-hint">If you see only arrows and no images, make sure JavaScript is enabled. This demo uses inline SVG placeholders — replace with your images by editing <code>data-src</code> attributes on <code>.carousel-item</code>.</div>

  <div class="small-slider" id="slider1" aria-label="3D circular carousel">
    <div class="carousel-ring">
      <!-- add your own images by putting data-src="path/to/your.jpg" on carousel-item OR keep placeholders below -->
      <?php while ($row = $result->fetch_assoc()): ?>
            <div class="carousel-item" data-src="admin/<?= htmlspecialchars($row['image']) ?>">
                <img src="admin/<?= htmlspecialchars($row['image']) ?>" alt="">
            </div>
        <?php endwhile; ?>
    </div>

    <div class="carousel-nav" aria-hidden="false">
      <button class="prev" title="Previous" aria-label="Previous">◀</button>
      <button class="next" title="Next" aria-label="Next">▶</button>
    </div>
  </div>

  <script>
document.addEventListener('DOMContentLoaded', function () {
  const slider = document.getElementById('slider1');
  if (!slider) return;
  const ring = slider.querySelector('.carousel-ring');
  const itemNodes = Array.from(ring.querySelectorAll('.carousel-item'));
  let itemCount = itemNodes.length;
  if (itemCount === 0) {
    console.warn('Carousel: no .carousel-item found');
    return;
  }

  // Helper: placeholder SVG when an image can't load
  function makePlaceholderSVG(i, w = 260, h = 160) {
    const bg = ['#f59e0b','#ef4444','#10b981','#3b82f6','#8b5cf6','#ef6ab4','#374151'][i % 7];
    const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='${w}' height='${h}' viewBox='0 0 ${w} ${h}'><rect width='100%' height='100%' fill='${bg}' rx='12'/><text x='50%' y='50%' font-family='Segoe UI,Roboto,Arial' font-size='28' dominant-baseline='middle' text-anchor='middle' fill='rgba(255,255,255,0.95)'>Slide ${i+1}</text></svg>`;
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
  }

  // Preload/validate all images (use data-src or existing img.src)
  const loadPromises = itemNodes.map((it, i) => {
    let img = it.querySelector('img');
    const dataSrc = it.getAttribute('data-src') || (img && img.getAttribute('src')) || '';

    if (!img) { img = document.createElement('img'); it.appendChild(img); }

    img.alt = 'Slide ' + (i + 1);

    return new Promise(resolve => {
      if (!dataSrc) {
        // no source provided — use placeholder
        img.src = makePlaceholderSVG(i);
        console.info('Carousel: no data-src for item', i + 1);
        return resolve({ ok: false, index: i });
      }

      // test load with a temporary Image object so we don't show broken images
      const tester = new Image();
      tester.onload = function () {
        img.src = dataSrc;
        // small delay to ensure computed size updates before measuring
        setTimeout(() => resolve({ ok: true, index: i }), 30);
      };
      tester.onerror = function () {
        console.warn('Carousel: failed to load', dataSrc, ' — using placeholder');
        img.src = makePlaceholderSVG(i);
        setTimeout(() => resolve({ ok: false, index: i }), 30);
      };
      tester.src = dataSrc;
    });
  });

  Promise.all(loadPromises).then(results => {
    console.info('Carousel: images ready:', results);

    // now safe to compute geometry and position items
    let angleStep = 360 / itemCount;
    let radius = 0;
    let currentIndex = 0;
    let autoTimer = null;
    const rotationInterval = 3000;

    function computeRadius() {
      // measure the visible width (fallback to CSS size if measurement fails)
      const firstImg = itemNodes[0].querySelector('img');
      let itemW = 260; // fallback (matches CSS)
      try {
        const r = firstImg.getBoundingClientRect();
        if (r && r.width > 0) itemW = r.width;
      } catch (e) { /* ignore */ }

      const theta = Math.PI / itemCount;
      const raw = (itemW / 2) / Math.tan(theta);
      radius = Math.round(raw) + 20;
      if (!isFinite(radius) || radius <= 0) radius = 400;
      console.info('Carousel: computed radius =', radius, 'itemW =', itemW, 'angleStep =', angleStep);
    }

    function positionItems() {
      computeRadius();
      itemNodes.forEach((it, i) => {
        const angle = i * angleStep;
        // rotate around Y, push out by radius, then center the element
        // note: keep translateX(-50%) translateY(-50%) so element centers correctly
        it.style.transform = `rotateY(${angle}deg) translateZ(${radius}px) translateX(-50%) translateY(-50%)`;
        it.style.opacity = '1';
      });
      ring.style.transform = `translateZ(-${radius}px) rotateY(0deg)`;
    }

    function rotateToIndex(index) {
      currentIndex = ((index % itemCount) + itemCount) % itemCount;
      const angle = currentIndex * angleStep;
      ring.style.transform = `translateZ(-${radius}px) rotateY(-${angle}deg)`;
    }

    function next() { rotateToIndex(currentIndex + 1); }
    function prev() { rotateToIndex(currentIndex - 1); }

    function startAuto() { if (autoTimer) return; autoTimer = setInterval(next, rotationInterval); }
    function stopAuto() { if (autoTimer) { clearInterval(autoTimer); autoTimer = null; } }

    // bind controls
    const btnNext = slider.querySelector('.next');
    const btnPrev = slider.querySelector('.prev');
    if (btnNext) btnNext.addEventListener('click', e => { e.preventDefault(); next(); stopAuto(); });
    if (btnPrev) btnPrev.addEventListener('click', e => { e.preventDefault(); prev(); stopAuto(); });

    slider.addEventListener('mouseenter', stopAuto);
    slider.addEventListener('mouseleave', startAuto);
    slider.addEventListener('touchstart', stopAuto, { passive: true });
    slider.addEventListener('touchend', startAuto, { passive: true });

    window.addEventListener('resize', () => {
      setTimeout(() => { positionItems(); rotateToIndex(currentIndex); }, 120);
    });

    // final init
    positionItems();
    rotateToIndex(0);
    startAuto();

    // expose for debugging
    window._carousel = { positionItems, rotateToIndex, next, prev };
  });
});
</script>
</body>
</html>
