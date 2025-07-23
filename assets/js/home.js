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