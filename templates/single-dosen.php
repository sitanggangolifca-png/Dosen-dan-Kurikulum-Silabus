<?php
get_header();

while (have_posts()) : the_post();
    $post_id = get_the_ID();
    $jabatan = get_post_meta($post_id, 'tppu_jabatan', true);
    $pendidikan = tppu_parse_lines(get_post_meta($post_id, 'tppu_pendidikan', true));
    $karya = tppu_parse_lines(get_post_meta($post_id, 'tppu_penelitian_pkm', true));
    ?>

    <div class="tppu-page tppu-staff-page">
    <div class="tppu-wrap" style="padding-top:28px; padding-bottom:56px;">

        <a class="tppu-back-link" href="<?= esc_url(home_url('/staf-pengajar/')) ?>">&larr; Kembali</a>

        <?php echo tppu_breadcrumb([
            ['label' => 'Beranda', 'url' => home_url('/beranda/')],
            ['label' => 'Staf Pengajar', 'url' => home_url('/staf-pengajar/')],
            ['label' => get_the_title()],
        ]); ?>

        <div class="tppu-profile-head">
            <?php if (has_post_thumbnail()): ?>
                <div class="tppu-profile-photo"><?php the_post_thumbnail('medium'); ?></div>
            <?php else: ?>
                <div class="tppu-profile-photo tppu-avatar-fallback" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="40%" height="40%" fill="none"><circle cx="12" cy="8" r="4" stroke="#FFFFFF" stroke-width="1.6"/><path d="M4 20c0-3.5 3.5-6 8-6s8 2.5 8 6" stroke="#FFFFFF" stroke-width="1.6" stroke-linecap="round"/></svg>
                </div>
            <?php endif; ?>
            <div class="tppu-profile-info">
                <h1><?= esc_html(get_the_title()) ?></h1>
                <?php if ($jabatan): ?><span class="tppu-tag"><?= esc_html($jabatan) ?></span><?php endif; ?>

                <?php if (!empty($pendidikan)): ?>
                    <div class="tppu-fact" style="margin-top:18px;">
                        <span class="tppu-fact-label">Pendidikan</span>
                        <ul>
                            <?php foreach ($pendidikan as $p): ?>
                                <li><?= esc_html($p) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (get_the_content()): ?>
        <section class="tppu-section">
            <h2 class="tppu-section-title">Tentang</h2>
            <div class="tppu-prose"><?php the_content(); ?></div>
        </section>
        <?php endif; ?>

        <?php if (!empty($karya)): ?>
        <section class="tppu-section">
            <h2 class="tppu-section-title">Penelitian &amp; Pengabdian Kepada Masyarakat (PKM)</h2>
            <ol class="tppu-karya-list">
                <?php foreach ($karya as $k): ?>
                    <li><?= esc_html($k) ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
        <?php endif; ?>

    </div>
    </div>

<?php
endwhile;
get_footer();
