/** Auto-split from main.js — testimonios */
(function () {
  function yuniorrojasInit_testimonios() {
    // Testimonios: slider infinito (derecha → izquierda)
    const testimonialsSlider = document.querySelector("[data-testimoniales-slider]");
    
    if (testimonialsSlider && typeof Swiper !== "undefined") {
      const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    
      new Swiper(testimonialsSlider, {
        loop: true,
        speed: reduceMotion ? 700 : 14000,
        grabCursor: true,
        allowTouchMove: true,
        slidesPerView: 1,
        spaceBetween: 28,
        autoplay: reduceMotion
          ? false
          : {
              delay: 0,
              disableOnInteraction: false,
              pauseOnMouseEnter: true,
            },
        breakpoints: {
          900: {
            slidesPerView: 2,
            spaceBetween: 36,
          },
          1200: {
            slidesPerView: 3,
            spaceBetween: 44,
          },
        },
      });
    }
    
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_testimonios);
  } else {
    yuniorrojasInit_testimonios();
  }
})();
