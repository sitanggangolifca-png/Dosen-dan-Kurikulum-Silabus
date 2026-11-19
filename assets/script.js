/* TPPU Content v2 — front-end script
   - Search realtime dosen (Staf Pengajar)
   - Search realtime mata kuliah (Kurikulum & Silabus)
   - Scroll-spy navigasi silabus (sidebar kiri)
*/
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        tppuInitDosenSearch();
        tppuInitMatkulSearch();
        tppuInitSilabusNav();
    });

    /* ---------- 1. Search realtime dosen ---------- */
    function tppuInitDosenSearch() {
        var input = document.getElementById('tppuSearchDosen');
        var grid = document.getElementById('tppuDosenGrid');
        if (!input || !grid) return;
        var noResult = document.getElementById('tppuDosenNoResult');
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.tppu-dosen-card'));

        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var visibleCount = 0;
            cards.forEach(function (card) {
                var name = card.getAttribute('data-name') || '';
                var match = name.indexOf(q) !== -1;
                card.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            if (noResult) noResult.style.display = visibleCount === 0 ? '' : 'none';
        });
    }

    /* ---------- 2. Search realtime mata kuliah (nama + kode) ---------- */
    function tppuInitMatkulSearch() {
        var input = document.getElementById('tppuSearchMatkul');
        var list = document.getElementById('tppuKurikulumList');
        if (!input || !list) return;
        var noResult = document.getElementById('tppuMatkulNoResult');
        var rows = Array.prototype.slice.call(list.querySelectorAll('[data-matkul-row]'));
        var semesterCards = Array.prototype.slice.call(list.querySelectorAll('[data-semester-card]'));

        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var totalVisible = 0;

            semesterCards.forEach(function (card) {
                var cardRows = Array.prototype.slice.call(card.querySelectorAll('[data-matkul-row]'));
                var visibleInCard = 0;
                cardRows.forEach(function (row) {
                    var hay = row.getAttribute('data-search') || '';
                    var match = q === '' || hay.indexOf(q) !== -1;
                    row.style.display = match ? '' : 'none';
                    if (match) { visibleInCard++; totalVisible++; }
                });
                card.style.display = visibleInCard > 0 ? '' : 'none';
            });

            if (noResult) noResult.style.display = totalVisible === 0 ? '' : 'none';
        });
    }

    /* ---------- 3. Scroll-spy navigasi silabus (sidebar kiri) ---------- */
    function tppuInitSilabusNav() {
        var nav = document.querySelector('.tppu-silabus-nav');
        if (!nav) return;
        var links = Array.prototype.slice.call(nav.querySelectorAll('.tppu-silabus-nav-link'));
        if (!links.length) return;

        // Klik: scroll halus ke section terkait.
        links.forEach(function (link) {
            link.addEventListener('click', function (e) {
                var targetId = link.getAttribute('data-target');
                var targetEl = document.getElementById(targetId);
                if (targetEl) {
                    e.preventDefault();
                    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    targetEl.scrollIntoView({ behavior: prefersReduced ? 'auto' : 'smooth', block: 'start' });
                    history.replaceState(null, '', '#' + targetId);
                }
            });
        });

        // Scroll-spy: ubah link aktif otomatis sesuai section yang terlihat.
        var sections = links
            .map(function (link) { return document.getElementById(link.getAttribute('data-target')); })
            .filter(Boolean);

        if (!sections.length) return;

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        setActiveLink(entry.target.id);
                    }
                });
            }, { rootMargin: '-35% 0px -55% 0px', threshold: 0 });

            sections.forEach(function (sec) { observer.observe(sec); });
        }

        function setActiveLink(id) {
            links.forEach(function (link) {
                link.classList.toggle('is-active', link.getAttribute('data-target') === id);
            });
        }
    }
})();
