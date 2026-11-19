/**
 * "Tentang Kami" di menu navigasi cuma berfungsi buka dropdown-nya,
 * bukan link ke halaman. Submenu (Staf Pengajar, Kurikulum & Silabus)
 * tetap bisa diklik normal.
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('a').forEach(function (link) {
    var text = (link.textContent || '').trim().toLowerCase();
    if (text !== 'tentang kami') return;

    var li = link.closest('li');
    var hasSubmenu = li && li.querySelector('ul, .sub-menu, .dropdown-menu, .dropdown');
    if (!hasSubmenu) return;

    link.addEventListener('click', function (e) {
      e.preventDefault();
    });
    link.style.cursor = 'pointer';
    link.setAttribute('aria-haspopup', 'true');
  });
});
