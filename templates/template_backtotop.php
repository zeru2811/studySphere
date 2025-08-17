<style>
  #back-to-top {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    transform: translateY(20px) translateZ(0);
    opacity: 0;
    visibility: hidden;
    width: 50px;
    height: 50px;
    box-shadow: 
      0 4px 6px -1px rgba(0, 0, 0, 0.1),
      0 2px 4px -1px rgba(0, 0, 0, 0.06),
      0 0 0 0 rgba(124, 58, 237, 0.5);
    perspective: 1000px;
  }
  
  #back-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) translateZ(0);
  }
  
  #back-to-top:hover {
    transform: translateY(-3px) translateZ(10px);
    box-shadow: 
      0 10px 15px -3px rgba(0, 0, 0, 0.1),
      0 4px 6px -2px rgba(0, 0, 0, 0.05),
      0 0 20px 5px rgba(124, 58, 237, 0.3);
  }
  
  #back-to-top:active {
    transform: translateY(1px) translateZ(5px);
  }
  
  #back-to-top i {
    transition: transform 0.2s ease;
  }
  
  #back-to-top:hover i {
    transform: translateY(-2px);
  }
</style>

<button id="back-to-top" class="fixed z-[100] bottom-8 right-8 p-3 bg-gradient-to-br from-purple-600 to-purple-800 text-white rounded-xl shadow-lg transition-all duration-300 flex items-center justify-center">
  <i class="fas fa-arrow-up text-lg"></i>
  <span class="absolute inset-0 rounded-xl border-2 border-white/20"></span>
</button>

<script>
const backToTopButton = document.getElementById('back-to-top');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTopButton.classList.add('visible');
    } else {
        backToTopButton.classList.remove('visible');
    }
});

backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
</script>