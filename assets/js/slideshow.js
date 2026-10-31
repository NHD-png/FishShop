(function () {
// Slideshow 5 ảnh, tự chuyển sau mỗi 5 giây.
const slideshow = document.getElementById('slideshow');
if (!slideshow) return;
const slides = slideshow.querySelectorAll('.hero-slide');
const dots = slideshow.querySelectorAll('.slide-dot');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let currentSlide = 0;
let slideTimer = null;


function showSlide(index, announce) {
  currentSlide = (index + slides.length) % slides.length;
  for (let i = 0; i < slides.length; i++) {
    const active = i === currentSlide;
    slides[i].classList.toggle('is-active', active);
    slides[i].setAttribute('aria-hidden', String(!active));
    slides[i].inert = !active;
    dots[i].classList.toggle('is-active', active);
    dots[i].setAttribute('aria-pressed', String(active));
  }
  if (announce) {
    document.getElementById('slide-status').textContent =
      'Slide ' + (currentSlide + 1) + ' trên ' + slides.length;
  }
}

function updateAutoplay() {
  clearInterval(slideTimer);
  slideTimer = null;
  if (!document.hidden && !reducedMotion.matches) {
    slideTimer = setInterval(function () {
      // Không ẩn liên kết đang được người dùng chọn bằng bàn phím.
      if (!document.activeElement.closest('.hero-slide')) {
        showSlide(currentSlide + 1, false);
      }
    }, 5000);
  }
}

function selectSlide(index) {
  showSlide(index, true);
  updateAutoplay();
}

for (let i = 0; i < dots.length; i++) {
  dots[i].addEventListener('click', function () {
    selectSlide(i);
  });
}
slideshow.addEventListener('keydown', function (event) {
  if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
    event.preventDefault();
    if (event.target.closest('.hero-slide')) {
      dots[currentSlide].focus();
    }
    selectSlide(currentSlide + (event.key === 'ArrowRight' || event.key === 'ArrowDown' ? 1 : -1));
    dots[currentSlide].focus();
  }
});
document.addEventListener('visibilitychange', updateAutoplay);
reducedMotion.addEventListener('change', updateAutoplay);
updateAutoplay();

})();