<?php
/**
 * Template halaman detail Mata Kuliah.
 * Tetap memakai header/footer tema aktif (get_header/get_footer),
 * jadi navbar & desain situs kamu tidak berubah.
 */
get_header();

while (have_posts()) : the_post();
    $post_id = get_the_ID();
    $kode = get_post_meta($post_id, 'tppu_kode', true);
    $nama_en = get_post_meta($post_id, 'tppu_nama_en', true);
    $sks = get_post_meta($post_id, 'tppu_sks', true);
    $sks_detail = get_post_meta($post_id, 'tppu_sks_detail', true);
    $prasyarat = get_post_meta($post_id, 'tppu_prasyarat', true);

    $terms = get_the_terms($post_id, 'semester');
    $semester_label = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';

    $cpmk = tppu_parse_lines(get_post_meta($post_id, 'tppu_cpmk', true));
    $subcpmk = tppu_parse_subcpmk(get_post_meta($post_id, 'tppu_subcpmk', true));
    $materi = tppu_parse_materi(get_post_meta($post_id, 'tppu_materi', true));
    $pustaka_utama = tppu_parse_lines(get_post_meta($post_id, 'tppu_pustaka_utama', true));
    $pustaka_pendukung = tppu_parse_lines(get_post_meta($post_id, 'tppu_pustaka_pendukung', true));
    $deskripsi_ada = (bool) get_the_content();

    // Nav sidebar hanya untuk section yang datanya benar-benar ada
    $nav = [];
    if ($deskripsi_ada) $nav['deskripsi'] = 'Deskripsi Mata Kuliah';
    if (!empty($cpmk)) $nav['cpmk'] = 'Capaian Pembelajaran (CPMK)';
    if (!empty($subcpmk)) $nav['subcpmk'] = 'Sub-Capaian Pembelajaran';
    if (!empty($materi)) $nav['materi'] = 'Materi Pembelajaran';
    if (!empty($pustaka_utama) || !empty($pustaka_pendukung)) $nav['pustaka'] = 'Pustaka';
    ?>

    <div class="tppu-page tppu-syllabus-page">
    <div class="tppu-wrap" style="padding-top:28px; padding-bottom:56px;">

        <a class="tppu-back-link" href="<?= esc_url(home_url('/kurikulum-silabus/')) ?>">&larr; Kembali</a>

        <?php echo tppu_breadcrumb([
            ['label' => 'Beranda', 'url' => home_url('/beranda/')],
            ['label' => 'Kurikulum & Silabus', 'url' => home_url('/kurikulum-silabus/')],
            ['label' => get_the_title()],
        ]); ?>

        <div class="tppu-head-card">
            <p class="tppu-eyebrow"><?= esc_html($semester_label) ?></p>
            <h1><?= esc_html(get_the_title()) ?></h1>
            <?php if ($nama_en): ?><p class="tppu-subtitle"><em><?= esc_html($nama_en) ?></em></p><?php endif; ?>

            <div class="tppu-info-cards">
                <div class="tppu-info-card">
                    <span class="tppu-info-card-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 7h10M7 12h10M7 17h6"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg></span>
                    <div><span class="tppu-fact-label">Kode Mata Kuliah</span><span class="tppu-fact-value"><?= esc_html($kode) ?></span></div>
                </div>
                <div class="tppu-info-card">
                    <span class="tppu-info-card-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
                    <div><span class="tppu-fact-label">Mata Kuliah</span><span class="tppu-fact-value"><?= esc_html(get_the_title()) ?></span></div>
                </div>
                <div class="tppu-info-card">
                    <span class="tppu-info-card-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.1 2.7 3 6 3s6-1.9 6-3v-5"/></svg></span>
                    <div><span class="tppu-fact-label">Jumlah SKS</span><span class="tppu-fact-value"><?= esc_html($sks) ?> SKS<?= $sks_detail ? ' (' . esc_html($sks_detail) . ')' : '' ?></span></div>
                </div>
                <?php if ($prasyarat): ?>
                <div class="tppu-info-card">
                    <span class="tppu-info-card-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></span>
                    <div><span class="tppu-fact-label">Prasyarat</span><span class="tppu-fact-value"><?= esc_html($prasyarat) ?></span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($nav)): ?>
        <div class="tppu-silabus-layout">
            <nav class="tppu-silabus-nav" aria-label="Navigasi bagian silabus">
                <?php $first = true; foreach ($nav as $anchor => $label): ?>
                    <a href="#<?= esc_attr($anchor) ?>" class="tppu-silabus-nav-link<?= $first ? ' is-active' : '' ?>" data-target="<?= esc_attr($anchor) ?>"><?= esc_html($label) ?></a>
                <?php $first = false; endforeach; ?>
            </nav>

            <div class="tppu-silabus-content">
                <?php if ($deskripsi_ada): ?>
                <section class="tppu-section" id="deskripsi">
                    <h2 class="tppu-section-title">Deskripsi Mata Kuliah</h2>
                    <div class="tppu-prose"><?php the_content(); ?></div>
                </section>
                <?php endif; ?>

                <?php if (!empty($cpmk)): ?>
                <section class="tppu-section" id="cpmk">
                    <h2 class="tppu-section-title">Capaian Pembelajaran Mata Kuliah (CPMK)</h2>
                    <table class="tppu-table">
                        <thead><tr><th style="width:56px;">No</th><th>Capaian Pembelajaran</th></tr></thead>
                        <tbody>
                        <?php foreach ($cpmk as $i => $c): ?>
                            <tr><td><?= $i + 1 ?></td><td><?= esc_html($c) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
                <?php endif; ?>

                <?php if (!empty($subcpmk)): ?>
                <section class="tppu-section" id="subcpmk">
                    <h2 class="tppu-section-title">Sub-Capaian Pembelajaran Mata Kuliah (Sub-CPMK)</h2>
                    <table class="tppu-table">
                        <thead><tr><th style="width:80px;">Kode</th><th>Uraian</th></tr></thead>
                        <tbody>
                        <?php foreach ($subcpmk as $kd => $isi): ?>
                            <tr><td><?= esc_html($kd) ?></td><td><?= esc_html($isi) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
                <?php endif; ?>

                <?php if (!empty($materi)): ?>
                <section class="tppu-section" id="materi">
                    <h2 class="tppu-section-title">Materi Pembelajaran</h2>
                    <?php $no = 1; foreach ($materi as $judul => $poinList): ?>
                        <h3 class="tppu-materi-title"><?= $no++ ?>. <?= esc_html($judul) ?></h3>
                        <table class="tppu-table">
                            <thead><tr><th style="width:56px;">No</th><th>Uraian Materi</th></tr></thead>
                            <tbody>
                            <?php foreach ($poinList as $j => $poin): ?>
                                <tr><td><?= $j + 1 ?></td><td><?= esc_html($poin) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <?php if (!empty($pustaka_utama) || !empty($pustaka_pendukung)): ?>
                <section class="tppu-section" id="pustaka">
                    <h2 class="tppu-section-title">Pustaka</h2>
                    <?php if (!empty($pustaka_utama)): ?>
                        <h3 class="tppu-materi-title">Pustaka Utama</h3>
                        <table class="tppu-table">
                            <thead><tr><th style="width:56px;">No</th><th>Referensi</th></tr></thead>
                            <tbody>
                            <?php foreach ($pustaka_utama as $i => $p): ?>
                                <tr><td><?= $i + 1 ?></td><td><?= esc_html($p) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    <?php if (!empty($pustaka_pendukung)): ?>
                        <h3 class="tppu-materi-title">Pustaka Pendukung</h3>
                        <table class="tppu-table">
                            <thead><tr><th style="width:56px;">No</th><th>Referensi</th></tr></thead>
                            <tbody>
                            <?php foreach ($pustaka_pendukung as $i => $p): ?>
                                <tr><td><?= $i + 1 ?></td><td><?= esc_html($p) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var navLinks = document.querySelectorAll('.tppu-silabus-nav-link');
        var sections = Array.prototype.slice.call(navLinks).map(function (l) {
            return document.getElementById(l.dataset.target);
        }).filter(Boolean);
        if (!navLinks.length || !sections.length) return;

        navLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var target = document.getElementById(this.dataset.target);
                if (target) {
                    var offset = 90;
                    var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
            });
        });

        var setActive = function (id) {
            navLinks.forEach(function (l) {
                l.classList.toggle('is-active', l.dataset.target === id);
            });
        };

        var onScroll = function () {
            var scrollPos = window.scrollY + 110;
            var current = sections[0].id;
            sections.forEach(function (sec) {
                if (sec.offsetTop <= scrollPos) current = sec.id;
            });
            setActive(current);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    });
    </script>

<?php
endwhile;
get_footer();
