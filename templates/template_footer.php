
<footer class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">Study Sphere</h3>
                <p class="text-gray-400">Empower your future with our expert-led courses in technology, business, and beyond.</p>
            </div>
            <div>
                <h4 class="font-bold mb-4">Company</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Careers</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Blog</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">Resources</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white">Help Center</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Tutorials</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Community</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Webinars</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">Legal</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Cookie Policy</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Disclaimer</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<script>
  const btn = document.getElementById('menu-btn');
  const menu = document.getElementById('mobile-menu');
  const links = menu.querySelectorAll('a');

  btn.addEventListener('click', () => {
    const isOpen = menu.classList.contains('dropdown-active');

    if (isOpen) {

      menu.classList.remove('dropdown-active', 'mb-5', 'px-3', 'py-3');
      links.forEach(link => link.classList.add('hidden'));
    } else {

      menu.classList.add('dropdown-active', 'mb-5', 'px-3', 'py-3');
      links.forEach(link => link.classList.remove('hidden'));
    }
  });
</script>