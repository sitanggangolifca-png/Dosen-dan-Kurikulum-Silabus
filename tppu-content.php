<?php
/**
 * Plugin Name: TPPU Content (Dosen & Kurikulum)
 * Description: Menambahkan jenis konten "Dosen" dan "Mata Kuliah" untuk Prodi TPPU, lengkap dengan importer data awal (39 mata kuliah) dan tampilan otomatis (tanpa perlu edit template tema).
 * Version: 1.0.0
 * Author: Prodi TPPU
 * Text Domain: tppu-content
 */

if (!defined('ABSPATH')) { exit; }

define('TPPU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TPPU_PLUGIN_URL', plugin_dir_url(__FILE__));

/* =========================================================
   1. CUSTOM POST TYPES & TAXONOMY
   ========================================================= */

add_action('init', 'tppu_register_post_types');
function tppu_register_post_types() {

    register_post_type('mata_kuliah', [
        'label'        => 'Mata Kuliah',
        'labels'       => [
            'name'          => 'Mata Kuliah',
            'singular_name' => 'Mata Kuliah',
            'add_new_item'  => 'Tambah Mata Kuliah',
            'edit_item'     => 'Edit Mata Kuliah',
            'all_items'     => 'Semua Mata Kuliah',
            'search_items'  => 'Cari Mata Kuliah',
        ],
        'public'       => true,
        'has_archive'  => 'kurikulum',
        'rewrite'      => ['slug' => 'kurikulum'],
        'menu_icon'    => 'dashicons-book-alt',
        'supports'     => ['title', 'editor', 'page-attributes'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('semester', 'mata_kuliah', [
        'label'             => 'Semester',
        'hierarchical'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'semester'],
        'show_in_rest'      => true,
    ]);

    register_post_type('dosen', [
        'label'        => 'Dosen',
        'labels'       => [
            'name'          => 'Dosen',
            'singular_name' => 'Dosen',
            'add_new_item'  => 'Tambah Dosen',
            'edit_item'     => 'Edit Profil Dosen',
            'all_items'     => 'Semua Dosen',
            'search_items'  => 'Cari Dosen',
        ],
        'public'       => true,
        'has_archive'  => false,
        'rewrite'      => ['slug' => 'dosen'],
        'menu_icon'    => 'dashicons-groups',
        'supports'     => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ]);
}

register_activation_hook(__FILE__, 'tppu_on_activate');
function tppu_on_activate() {
    tppu_register_post_types();
    // Buat 6 term semester supaya langsung tersedia di dropdown
    for ($i = 1; $i <= 6; $i++) {
        if (!term_exists('Semester ' . $i, 'semester')) {
            wp_insert_term('Semester ' . $i, 'semester', ['slug' => 'semester-' . $i]);
        }
    }
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});


/* =========================================================
   2. META BOX: MATA KULIAH
   ========================================================= */

add_action('add_meta_boxes', 'tppu_add_matkul_metabox');
function tppu_add_matkul_metabox() {
    add_meta_box(
        'tppu_matkul_detail',
        'Detail Mata Kuliah',
        'tppu_render_matkul_metabox',
        'mata_kuliah',
        'normal',
        'high'
    );
}

function tppu_render_matkul_metabox($post) {
    wp_nonce_field('tppu_save_matkul', 'tppu_matkul_nonce');
    $f = function ($key) use ($post) {
        return esc_attr(get_post_meta($post->ID, $key, true));
    };
    $fa = function ($key) use ($post) {
        return esc_textarea(get_post_meta($post->ID, $key, true));
    };
    ?>
    <style>
        .tppu-field { margin-bottom: 16px; }
        .tppu-field label { display:block; font-weight:600; margin-bottom:4px; }
        .tppu-field input[type=text], .tppu-field input[type=number] { width:100%; max-width:420px; }
        .tppu-field textarea { width:100%; font-family: monospace; }
        .tppu-hint { color:#666; font-size:12px; margin-top:4px; }
        .tppu-row { display:flex; gap:24px; flex-wrap:wrap; }
        .tppu-row .tppu-field { flex: 1 1 200px; }
    </style>

    <div class="tppu-row">
        <div class="tppu-field">
            <label>Kode Mata Kuliah</label>
            <input type="text" name="tppu_kode" value="<?= $f('tppu_kode') ?>" placeholder="Contoh: 5TPUA105">
        </div>
        <div class="tppu-field">
            <label>Nama (Bahasa Inggris)</label>
            <input type="text" name="tppu_nama_en" value="<?= $f('tppu_nama_en') ?>" placeholder="Contoh: Electrical Fundamental">
        </div>
        <div class="tppu-field">
            <label>Jumlah SKS</label>
            <input type="number" name="tppu_sks" value="<?= $f('tppu_sks') ?>" placeholder="Contoh: 4">
        </div>
        <div class="tppu-field">
            <label>Detail SKS</label>
            <input type="text" name="tppu_sks_detail" value="<?= $f('tppu_sks_detail') ?>" placeholder="Contoh: 2 SKS Teori, 2 SKS Praktik">
        </div>
        <div class="tppu-field">
            <label>Prasyarat (jika ada)</label>
            <input type="text" name="tppu_prasyarat" value="<?= $f('tppu_prasyarat') ?>">
        </div>
    </div>

    <p class="tppu-hint">Untuk kolom <strong>Deskripsi Mata Kuliah</strong>, isi di kotak editor utama di atas (bukan di sini).</p>

    <div class="tppu-field">
        <label>CPMK — Capaian Pembelajaran Mata Kuliah</label>
        <textarea name="tppu_cpmk" rows="4"><?= $fa('tppu_cpmk') ?></textarea>
        <p class="tppu-hint">Satu poin CPMK per baris.</p>
    </div>

    <div class="tppu-field">
        <label>Sub-CPMK</label>
        <textarea name="tppu_subcpmk" rows="6"><?= $fa('tppu_subcpmk') ?></textarea>
        <p class="tppu-hint">Format tiap baris: <code>kode | uraian</code> — contoh: <code>1.1 | Mampu menjelaskan ...</code></p>
    </div>

    <div class="tppu-field">
        <label>Materi Pembelajaran</label>
        <textarea name="tppu_materi" rows="8"><?= $fa('tppu_materi') ?></textarea>
        <p class="tppu-hint">Baris diawali <code># </code> untuk judul topik, baris diawali <code>- </code> untuk poin di bawahnya. Contoh:<br><code># Mekanika<br>- Gaya sebagai besaran vektor<br>- Titik berat</code></p>
    </div>

    <div class="tppu-row">
        <div class="tppu-field">
            <label>Pustaka Utama</label>
            <textarea name="tppu_pustaka_utama" rows="4"><?= $fa('tppu_pustaka_utama') ?></textarea>
            <p class="tppu-hint">Satu referensi per baris.</p>
        </div>
        <div class="tppu-field">
            <label>Pustaka Pendukung</label>
            <textarea name="tppu_pustaka_pendukung" rows="4"><?= $fa('tppu_pustaka_pendukung') ?></textarea>
            <p class="tppu-hint">Satu referensi per baris.</p>
        </div>
    </div>
    <?php
}

add_action('save_post_mata_kuliah', 'tppu_save_matkul_metabox');
function tppu_save_matkul_metabox($post_id) {
    if (!isset($_POST['tppu_matkul_nonce']) || !wp_verify_nonce($_POST['tppu_matkul_nonce'], 'tppu_save_matkul')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = ['tppu_kode', 'tppu_nama_en', 'tppu_sks', 'tppu_sks_detail', 'tppu_prasyarat'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    $textareas = ['tppu_cpmk', 'tppu_subcpmk', 'tppu_materi', 'tppu_pustaka_utama', 'tppu_pustaka_pendukung'];
    foreach ($textareas as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
        }
    }
}


/* =========================================================
   3. META BOX: DOSEN
   ========================================================= */

add_action('add_meta_boxes', 'tppu_add_dosen_metabox');
function tppu_add_dosen_metabox() {
    add_meta_box('tppu_dosen_detail', 'Detail Dosen', 'tppu_render_dosen_metabox', 'dosen', 'normal', 'high');
}

function tppu_render_dosen_metabox($post) {
    wp_nonce_field('tppu_save_dosen', 'tppu_dosen_nonce');
    $jabatan = esc_attr(get_post_meta($post->ID, 'tppu_jabatan', true));
    $pendidikan = esc_textarea(get_post_meta($post->ID, 'tppu_pendidikan', true));
    ?>
    <div class="tppu-field" style="margin-bottom:16px;">
        <label style="display:block; font-weight:600; margin-bottom:4px;">Jabatan Fungsional</label>
        <input type="text" name="tppu_jabatan" value="<?= $jabatan ?>" style="width:100%; max-width:420px;" placeholder="Contoh: Lektor Kepala">
    </div>
    <div class="tppu-field" style="margin-bottom:16px;">
        <label style="display:block; font-weight:600; margin-bottom:4px;">Pendidikan</label>
        <textarea name="tppu_pendidikan" rows="4" style="width:100%;"><?= $pendidikan ?></textarea>
        <p style="color:#666; font-size:12px;">Satu jenjang pendidikan per baris, contoh: <code>S1 Teknik Penerbangan - ITB</code></p>
    </div>
    <div class="tppu-field">
        <label style="display:block; font-weight:600; margin-bottom:4px;">Penelitian &amp; Pengabdian Kepada Masyarakat (PKM)</label>
        <textarea name="tppu_penelitian_pkm" rows="8" style="width:100%;"><?= esc_textarea(get_post_meta($post->ID, 'tppu_penelitian_pkm', true)) ?></textarea>
        <p style="color:#666; font-size:12px;">Satu judul penelitian/PKM per baris. Kosongkan kalau datanya belum ada — jangan diisi karangan.</p>
    </div>
    <p style="color:#666;">Foto profil: gunakan kotak <strong>Featured Image</strong> di sidebar kanan.</p>
    <?php
}

add_action('save_post_dosen', 'tppu_save_dosen_metabox');
function tppu_save_dosen_metabox($post_id) {
    if (!isset($_POST['tppu_dosen_nonce']) || !wp_verify_nonce($_POST['tppu_dosen_nonce'], 'tppu_save_dosen')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['tppu_jabatan'])) {
        update_post_meta($post_id, 'tppu_jabatan', sanitize_text_field($_POST['tppu_jabatan']));
    }
    if (isset($_POST['tppu_pendidikan'])) {
        update_post_meta($post_id, 'tppu_pendidikan', sanitize_textarea_field($_POST['tppu_pendidikan']));
    }
    if (isset($_POST['tppu_penelitian_pkm'])) {
        update_post_meta($post_id, 'tppu_penelitian_pkm', sanitize_textarea_field($_POST['tppu_penelitian_pkm']));
    }
}


/* =========================================================
   4. TEMPLATE UNTUK HALAMAN SINGLE (tanpa perlu edit tema)
   ========================================================= */

add_filter('template_include', 'tppu_template_include');
function tppu_template_include($template) {
    if (is_singular('mata_kuliah')) {
        $custom = TPPU_PLUGIN_DIR . 'templates/single-mata_kuliah.php';
        if (file_exists($custom)) return $custom;
    }
    if (is_singular('dosen')) {
        $custom = TPPU_PLUGIN_DIR . 'templates/single-dosen.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}

add_action('wp_enqueue_scripts', 'tppu_enqueue_assets');
function tppu_enqueue_assets() {
    wp_enqueue_style('tppu-content-style', TPPU_PLUGIN_URL . 'assets/style.css', [], '1.0.1');
    wp_enqueue_script('tppu-menu-fix', TPPU_PLUGIN_URL . 'assets/menu-fix.js', [], '1.0.0', true);
}


/* =========================================================
   5. HELPER: parsing textarea terstruktur
   ========================================================= */

/**
 * Render breadcrumb. $trail = [ ['label' => 'Beranda', 'url' => '/beranda/'], ..., ['label' => 'Halaman aktif'] (tanpa url) ]
 */
function tppu_breadcrumb($trail) {
    $parts = [];
    foreach ($trail as $item) {
        if (!empty($item['url'])) {
            $parts[] = '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
        } else {
            $parts[] = '<span class="tppu-breadcrumb-current">' . esc_html($item['label']) . '</span>';
        }
    }
    return '<nav class="tppu-breadcrumb" aria-label="Breadcrumb">' . implode(' <span class="tppu-breadcrumb-sep">/</span> ', $parts) . '</nav>';
}

function tppu_parse_lines($text) {
    $lines = preg_split('/\r\n|\r|\n/', trim((string) $text));
    $out = [];
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l !== '') $out[] = $l;
    }
    return $out;
}

function tppu_parse_subcpmk($text) {
    $out = [];
    foreach (tppu_parse_lines($text) as $line) {
        if (strpos($line, '|') !== false) {
            [$kode, $isi] = array_map('trim', explode('|', $line, 2));
            $out[$kode] = $isi;
        }
    }
    return $out;
}

function tppu_parse_materi($text) {
    $out = [];
    $current = null;
    foreach (tppu_parse_lines($text) as $line) {
        if (strpos($line, '# ') === 0) {
            $current = trim(substr($line, 2));
            $out[$current] = [];
        } elseif (strpos($line, '- ') === 0 && $current !== null) {
            $out[$current][] = trim(substr($line, 2));
        }
    }
    return $out;
}


/* =========================================================
   6. SHORTCODES: [tppu_kurikulum] dan [tppu_dosen]
   ========================================================= */

add_shortcode('tppu_kurikulum', 'tppu_shortcode_kurikulum');
function tppu_shortcode_kurikulum($atts) {
    ob_start();
    $terms = get_terms(['taxonomy' => 'semester', 'hide_empty' => false, 'orderby' => 'name']);
    usort($terms, function ($a, $b) {
        return (int) filter_var($a->name, FILTER_SANITIZE_NUMBER_INT) <=> (int) filter_var($b->name, FILTER_SANITIZE_NUMBER_INT);
    });
    ?>
    <?php
    // Mata kuliah yang TIDAK perlu tampilkan nama Inggris, walau datanya ada
    $no_english_titles = ['pancasila', 'bahasa indonesia'];
    ?>
    <div class="tppu-wrap" style="padding-top:40px; padding-bottom:56px;">
    <div class="tppu-kurikulum-wrap">
        <h2 class="tppu-struktur-title">Struktur Kurikulum</h2>

        <div class="tppu-list-heading-row">
            <span></span>
            <input type="text" id="tppuMatkulSearch" class="tppu-search-input" placeholder="Cari mata kuliah atau kode...">
        </div>

        <div class="tppu-tabs" role="tablist">
            <?php foreach ($terms as $i => $term): ?>
                <button type="button" class="tppu-tab<?= $i === 0 ? ' is-active' : '' ?>" data-target="tppu-panel-<?= $term->term_id ?>"><?= esc_html($term->name) ?></button>
            <?php endforeach; ?>
        </div>
        <?php foreach ($terms as $i => $term):
            $matkuls = get_posts([
                'post_type'      => 'mata_kuliah',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
                'tax_query'      => [[
                    'taxonomy' => 'semester',
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ]],
            ]);
            // Semester 5 tidak menampilkan nama Inggris sama sekali
            $skip_english_this_semester = (stripos($term->name, '5') !== false);
        ?>
            <div class="tppu-panel<?= $i === 0 ? ' is-shown' : '' ?>" id="tppu-panel-<?= $term->term_id ?>">
                <table class="tppu-table">
                    <thead><tr><th style="width:48px;">No</th><th style="width:120px;">Kode</th><th>Mata Kuliah</th><th style="width:70px;">SKS</th></tr></thead>
                    <tbody>
                        <?php if (empty($matkuls)): ?>
                            <tr><td colspan="4">Belum ada mata kuliah pada semester ini.</td></tr>
                        <?php else: $no = 1; foreach ($matkuls as $mk):
                            $kode = get_post_meta($mk->ID, 'tppu_kode', true);
                            $nama = get_the_title($mk);
                            $nama_en = get_post_meta($mk->ID, 'tppu_nama_en', true);
                            $show_en = $nama_en
                                && !$skip_english_this_semester
                                && !in_array(mb_strtolower(trim($nama)), $no_english_titles, true);
                            $search_val = mb_strtolower($kode . ' ' . $nama);
                        ?>
                            <tr class="tppu-matkul-row" data-search="<?= esc_attr($search_val) ?>">
                                <td><?= $no++ ?></td>
                                <td><?= esc_html($kode) ?></td>
                                <td>
                                    <a href="<?= esc_url(get_permalink($mk->ID)) ?>"><?= esc_html($nama) ?></a>
                                    <?php if ($show_en): ?><span class="tppu-matkul-en"><?= esc_html($nama_en) ?></span><?php endif; ?>
                                </td>
                                <td><?= esc_html(get_post_meta($mk->ID, 'tppu_sks', true)) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <p class="tppu-empty-state" style="display:none;" data-no-result-for="tppu-panel-<?= $term->term_id ?>">Tidak ada mata kuliah yang cocok.</p>
            </div>
        <?php endforeach; ?>
    </div>
    </div>
    <script>
    (function () {
        var wrap = document.currentScript.previousElementSibling;
        if (!wrap || !wrap.classList.contains('tppu-kurikulum-wrap')) {
            wrap = document.querySelectorAll('.tppu-kurikulum-wrap');
            wrap = wrap[wrap.length - 1];
        }
        var tabs = wrap.querySelectorAll('.tppu-tab');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');
                wrap.querySelectorAll('.tppu-panel').forEach(function (p) { p.classList.remove('is-shown'); });
                var target = wrap.querySelector('#' + tab.dataset.target);
                if (target) target.classList.add('is-shown');
            });
        });

        var searchInput = document.getElementById('tppuMatkulSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase();
                wrap.querySelectorAll('.tppu-panel').forEach(function (panel) {
                    var visible = 0;
                    panel.querySelectorAll('.tppu-matkul-row').forEach(function (row) {
                        var match = row.dataset.search.indexOf(q) !== -1;
                        row.style.display = match ? '' : 'none';
                        if (match) visible++;
                    });
                    var noResult = panel.querySelector('[data-no-result-for]');
                    if (noResult) noResult.style.display = (visible === 0 && q !== '') ? 'block' : 'none';
                });
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('tppu_dosen', 'tppu_shortcode_dosen');
function tppu_shortcode_dosen($atts) {
    $dosens = get_posts(['post_type' => 'dosen', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    ob_start();
    ?>
    <div class="tppu-wrap" style="padding-top:40px; padding-bottom:56px;">
    <div class="tppu-list-heading-row">
        <h2 class="tppu-list-heading">Daftar Dosen dan Staf Pengajar</h2>
        <input type="text" id="tppuSearchInput" class="tppu-search-input" placeholder="Cari nama dosen...">
    </div>

    <?php if (empty($dosens)): ?>
        <p>Belum ada data dosen.</p>
    <?php else: ?>
        <div class="tppu-dosen-grid" id="tppuStaffGrid">
            <?php foreach ($dosens as $d): ?>
                <a class="tppu-dosen-card" href="<?= esc_url(get_permalink($d->ID)) ?>" data-name="<?= esc_attr(mb_strtolower(get_the_title($d))) ?>">
                    <?php if (has_post_thumbnail($d->ID)): ?>
                        <?= get_the_post_thumbnail($d->ID, 'medium') ?>
                    <?php else: ?>
                        <span class="tppu-avatar-fallback" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="40%" height="40%" fill="none"><circle cx="12" cy="8" r="4" stroke="#FFFFFF" stroke-width="1.6"/><path d="M4 20c0-3.5 3.5-6 8-6s8 2.5 8 6" stroke="#FFFFFF" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                    <?php endif; ?>
                    <span><?= esc_html(get_the_title($d)) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="tppu-empty-state" id="tppuNoResult" style="display:none;">Tidak ada dosen dengan nama tersebut.</p>
    <?php endif; ?>

    <script>
    document.getElementById('tppuSearchInput')?.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        var cards = document.querySelectorAll('#tppuStaffGrid .tppu-dosen-card');
        var visible = 0;
        cards.forEach(function (card) {
            var match = card.dataset.name.indexOf(q) !== -1;
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        var noResult = document.getElementById('tppuNoResult');
        if (noResult) noResult.style.display = visible === 0 ? 'block' : 'none';
    });
    </script>
    </div>
    <?php
    return ob_get_clean();
}


/* =========================================================
   7. HALAMAN ADMIN: IMPORT DATA AWAL
   ========================================================= */

add_action('admin_menu', function () {
    add_management_page('Import Data TPPU', 'Import Data TPPU', 'manage_options', 'tppu-import', 'tppu_render_import_page');
});

function tppu_render_import_page() {
    if (!current_user_can('manage_options')) return;

    $result = null;
    if (isset($_POST['tppu_do_import']) && check_admin_referer('tppu_import_action', 'tppu_import_nonce')) {
        $result = tppu_run_import();
    }
    ?>
    <div class="wrap">
        <h1>Import Data Awal Prodi TPPU</h1>
        <p>Klik tombol di bawah untuk mengisi otomatis <strong>39 mata kuliah</strong> (Semester 1–6) dari data kurikulum yang sudah ada, dan <strong>6 profil dosen</strong> (dari foto yang sudah tersedia). Aman diklik berkali-kali — data yang sudah ada tidak akan digandakan.</p>
        <form method="post">
            <?php wp_nonce_field('tppu_import_action', 'tppu_import_nonce'); ?>
            <p><button type="submit" name="tppu_do_import" value="1" class="button button-primary">Import Sekarang</button></p>
        </form>
        <?php if ($result): ?>
            <div class="notice notice-success"><p>
                Selesai. Mata kuliah baru ditambahkan: <strong><?= (int) $result['matkul_new'] ?></strong>
                (dilewati/sudah ada: <?= (int) $result['matkul_skip'] ?>).
                Dosen baru ditambahkan: <strong><?= (int) $result['dosen_new'] ?></strong>
                (dilewati/sudah ada: <?= (int) $result['dosen_skip'] ?>).
                Profil dosen (gelar, pendidikan, jabatan, penelitian/PKM) diisi untuk
                <strong><?= (int) ($result['dosen_profile_new'] ?? 0) + (int) ($result['dosen_profile_updated'] ?? 0) ?></strong> dosen
                (<?= (int) ($result['dosen_profile_new'] ?? 0) ?> baru, <?= (int) ($result['dosen_profile_updated'] ?? 0) ?> diperbarui).
                Halaman baru dibuat: <strong><?= (int) $result['pages_new'] ?></strong>,
                diperbarui: <strong><?= (int) ($result['pages_updated'] ?? 0) ?></strong>
                (dilewati: <?= (int) $result['pages_skip'] ?>).
                Halaman "Beranda" sudah diset ulang sebagai halaman depan situs.
            </p></div>
        <?php endif; ?>
    </div>
    <?php
}

function tppu_run_import() {
    $matkul_new = 0; $matkul_skip = 0;
    $dosen_new = 0; $dosen_skip = 0;

    // ---- Import Mata Kuliah dari data/matakuliah.json ----
    $json_path = TPPU_PLUGIN_DIR . 'data/matakuliah.json';
    if (file_exists($json_path)) {
        $data = json_decode(file_get_contents($json_path), true);
        if (is_array($data)) {
            foreach ($data as $sem => $semData) {
                $term = get_term_by('slug', 'semester-' . $sem, 'semester');
                if (!$term) continue;

                $order = 0;
                foreach ($semData['matkul'] as $mk) {
                    $order++;
                    $existing = get_posts([
                        'post_type'   => 'mata_kuliah',
                        'meta_key'    => 'tppu_kode',
                        'meta_value'  => $mk['kode'],
                        'post_status' => 'any',
                        'numberposts' => 1,
                    ]);
                    if (!empty($existing)) { $matkul_skip++; continue; }

                    $post_id = wp_insert_post([
                        'post_type'    => 'mata_kuliah',
                        'post_title'   => $mk['nama'],
                        'post_name'    => $mk['slug'],
                        'post_content' => $mk['deskripsi'] ?? '',
                        'post_status'  => 'publish',
                        'menu_order'   => $order,
                    ]);
                    if (is_wp_error($post_id) || !$post_id) continue;

                    wp_set_post_terms($post_id, [(int) $term->term_id], 'semester');

                    update_post_meta($post_id, 'tppu_kode', $mk['kode']);
                    update_post_meta($post_id, 'tppu_nama_en', $mk['nama_en'] ?? '');
                    update_post_meta($post_id, 'tppu_sks', $mk['sks'] ?? '');
                    update_post_meta($post_id, 'tppu_sks_detail', $mk['sks_detail'] ?? '');
                    update_post_meta($post_id, 'tppu_prasyarat', $mk['prasyarat'] ?? '');

                    if (!empty($mk['cpmk'])) {
                        update_post_meta($post_id, 'tppu_cpmk', implode("\n", $mk['cpmk']));
                    }
                    if (!empty($mk['subcpmk'])) {
                        $lines = [];
                        foreach ($mk['subcpmk'] as $kode => $isi) {
                            $lines[] = $kode . ' | ' . $isi;
                        }
                        update_post_meta($post_id, 'tppu_subcpmk', implode("\n", $lines));
                    }
                    if (!empty($mk['materi'])) {
                        $lines = [];
                        foreach ($mk['materi'] as $judul => $poinList) {
                            $lines[] = '# ' . $judul;
                            foreach ($poinList as $poin) {
                                $lines[] = '- ' . $poin;
                            }
                        }
                        update_post_meta($post_id, 'tppu_materi', implode("\n", $lines));
                    }
                    if (!empty($mk['pustaka_utama'])) {
                        update_post_meta($post_id, 'tppu_pustaka_utama', implode("\n", $mk['pustaka_utama']));
                    }
                    if (!empty($mk['pustaka_pendukung'])) {
                        update_post_meta($post_id, 'tppu_pustaka_pendukung', implode("\n", $mk['pustaka_pendukung']));
                    }

                    $matkul_new++;
                }
            }
        }
    }

    // ---- Import Dosen dari foto yang sudah dibundel ----
    $foto_dir = TPPU_PLUGIN_DIR . 'assets/foto/';
    if (is_dir($foto_dir)) {
        foreach (scandir($foto_dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $slug = pathinfo($file, PATHINFO_FILENAME);
            $nama = ucwords(str_replace('-', ' ', $slug));

            $existing = get_page_by_path($slug, OBJECT, 'dosen');
            if ($existing) { $dosen_skip++; continue; }

            $post_id = wp_insert_post([
                'post_type'   => 'dosen',
                'post_title'  => $nama,
                'post_name'   => $slug,
                'post_status' => 'publish',
            ]);
            if (is_wp_error($post_id) || !$post_id) continue;

            $filepath = $foto_dir . $file;
            $filetype = wp_check_filetype($file);
            $upload = wp_upload_bits($file, null, file_get_contents($filepath));
            if (empty($upload['error'])) {
                $attachment = [
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => $nama,
                    'post_status'    => 'inherit',
                ];
                $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
                set_post_thumbnail($post_id, $attach_id);
            }

            $dosen_new++;
        }
    }

    $profile_result = function_exists('tppu_import_dosen_profiles') ? tppu_import_dosen_profiles() : array('new' => 0, 'updated' => 0);
    $dosen_profile_new     = $profile_result['new'];
    $dosen_profile_updated = $profile_result['updated'];

    $page_result = function_exists('tppu_import_pages') ? tppu_import_pages() : array('pages_new' => 0, 'pages_skip' => 0, 'pages_updated' => 0);
    $pages_new     = $page_result['pages_new'];
    $pages_skip    = $page_result['pages_skip'];

    // Selalu segerin ulang URL (rewrite rules) tiap kali import dijalankan —
    // biar /staf-pengajar/ dkk selalu nunjuk ke halaman yang benar.
    flush_rewrite_rules();
    $pages_updated = $page_result['pages_updated'] ?? 0;

    return compact('matkul_new', 'matkul_skip', 'dosen_new', 'dosen_skip', 'dosen_profile_new', 'dosen_profile_updated', 'pages_new', 'pages_skip', 'pages_updated');
}


define('TPPU_PAGE_BERANDA_HTML', <<<'TPPUHTML'
<div class="tppu-page">

<section class="tppu-hero-photo" style="background-image:url('{{HERO_IMG}}');">
  <div class="tppu-wrap">
    <div class="tppu-hero-photo-text">
      <div class="tppu-eyebrow">Program Studi</div>
      <h1 class="tppu-hero-photo-h1">Teknologi Pemeliharaan Pesawat Udara</h1>
      <p class="tppu-hero-photo-sub">Politeknik Penerbangan Medan</p>
      <div class="tppu-cta-row">
        <a href="/staf-pengajar/" class="tppu-btn tppu-btn-outline">Staf Pengajar</a>
        <a href="/kurikulum-silabus/" class="tppu-btn tppu-btn-outline">Kurikulum &amp; Silabus</a>
      </div>
    </div>
  </div>
</section>

</div>

TPPUHTML
);

define('TPPU_PAGE_TENTANG_HTML', <<<'TPPUHTML'
<div class="tppu-page">

<div class="tppu-page-head">
  <h1>Tentang Kami</h1>
  <p>Mengenal lebih dekat Program Studi Teknologi Pemeliharaan Pesawat Udara, Politeknik Penerbangan Medan.</p>
</div>

<section>
  <div class="tppu-notice">Bagian sejarah, visi, dan misi di bawah ini masih draf umum — sesuaikan dengan data resmi program studi sebelum dipublikasikan.</div>
  <div class="tppu-two-col">
    <div>
      <h2 style="font-size:1.4rem; margin-bottom:16px;">Sejarah Singkat</h2>
      <p>Program Studi Teknologi Pemeliharaan Pesawat Udara merupakan salah satu program vokasi di lingkungan Politeknik Penerbangan Medan, di bawah pembinaan Kementerian Perhubungan Republik Indonesia. Program studi ini dibentuk untuk menjawab kebutuhan tenaga teknisi perawatan pesawat udara yang kompeten dan bersertifikasi.</p>
      <p style="margin-top:12px;">Sejak berdiri, program studi terus mengembangkan fasilitas praktik — mulai dari hanggar pelatihan, laboratorium avionik, hingga kerja sama dengan operator dan bengkel perawatan pesawat — untuk memastikan lulusan siap kerja begitu menyelesaikan studi.</p>
    </div>
    <div>
      <h2 style="font-size:1.4rem; margin-bottom:16px;">Visi &amp; Misi</h2>
      <p><strong style="color:#10182b">Visi:</strong> Menjadi program studi vokasi perawatan pesawat udara yang unggul dan diakui industri penerbangan nasional maupun internasional.</p>
      <p style="margin-top:12px;"><strong style="color:#10182b">Misi:</strong></p>
      <ul class="tppu-plain-list" style="margin-top:8px;">
        <li>Menyelenggarakan pendidikan vokasi berbasis kompetensi sesuai standar kelaikudaraan.</li>
        <li>Membekali taruna/taruni dengan praktik langsung di hanggar dan laboratorium.</li>
        <li>Menjalin kerja sama dengan industri penerbangan untuk penempatan kerja lulusan.</li>
        <li>Mendorong penelitian terapan di bidang perawatan pesawat udara.</li>
      </ul>
    </div>
  </div>
</section>

<section class="tppu-section-alt">
  <div style="padding:0 4px;">
    <h2 style="font-size:1.4rem; margin-bottom:8px;">Kompetensi lulusan</h2>
    <p style="max-width:60ch; margin-bottom:24px;">Lulusan Prodi TPPU dibekali kompetensi teknis dan sikap kerja yang sesuai kebutuhan industri perawatan pesawat udara.</p>
    <ul class="tppu-competency">
      <li><h4>Perawatan Rangka &amp; Mesin</h4><p>Inspeksi, perbaikan, dan perawatan berkala struktur pesawat (airframe) serta sistem powerplant.</p></li>
      <li><h4>Sistem Avionik</h4><p>Pemeriksaan dan troubleshooting dasar sistem kelistrikan dan instrumen elektronik pesawat udara.</p></li>
      <li><h4>Keselamatan &amp; Regulasi</h4><p>Penerapan prosedur keselamatan kerja serta pemahaman regulasi kelaikudaraan.</p></li>
    </ul>
  </div>
</section>

</div>

TPPUHTML
);

define('TPPU_PAGE_KONTAK_HTML', <<<'TPPUHTML'
<div class="tppu-page">

<div class="tppu-page-head">
  <h1>Kontak</h1>
  <p>Hubungi Program Studi TPPU untuk informasi akademik, kerja sama, atau penerimaan mahasiswa baru.</p>
</div>

<section>
  <div class="tppu-notice">Alamat, nomor telepon, dan e-mail di bawah ini masih contoh — ganti dengan data kontak resmi kampus sebelum dipublikasikan.</div>
  <div class="tppu-contact-grid">
    <div>
      <div class="tppu-info-item">
        <svg class="tppu-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
        <div><h4>Alamat</h4><p>Kampus Politeknik Penerbangan Medan<br>Kota Medan, Sumatera Utara, Indonesia</p></div>
      </div>
      <div class="tppu-info-item">
        <svg class="tppu-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.4 2.1L8 9.9a16 16 0 0 0 6 6l1.4-1.4a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.8 2z"/></svg>
        <div><h4>Telepon</h4><p>(061) 000-0000</p></div>
      </div>
      <div class="tppu-info-item">
        <svg class="tppu-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
        <div><h4>E-mail</h4><p><a href="mailto:info@poltekbangmedan.ac.id" style="color:#B5730A; text-decoration:none;">info@poltekbangmedan.ac.id</a></p></div>
      </div>
      <div class="tppu-info-item">
        <svg class="tppu-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <div><h4>Jam Layanan</h4><p>Senin–Jumat, 08.00–16.00 WIB</p></div>
      </div>
      <a href="mailto:info@poltekbangmedan.ac.id?subject=Pertanyaan%20Prodi%20TPPU" class="tppu-btn tppu-btn-dark" style="margin-top:22px;">Kirim E-mail</a>
    </div>
    <div class="tppu-map-box">
      <iframe src="https://www.google.com/maps?q=Politeknik+Penerbangan+Medan&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Lokasi Politeknik Penerbangan Medan"></iframe>
    </div>
  </div>
</section>

</div>

TPPUHTML
);



/* =========================================================
   8. IMPORT HALAMAN (Beranda, Tentang Kami, Staf Pengajar,
      Kurikulum & Silabus, Kontak) — otomatis, sekali klik
   ========================================================= */

/* =========================================================
   7b. IMPORT PROFIL DOSEN LENGKAP (nama+gelar, pendidikan,
       jabatan fungsional, penelitian & PKM) dari data/dosen_profiles.json
   ========================================================= */
function tppu_import_dosen_profiles() {
    $new = 0; $updated = 0;
    $json_path = TPPU_PLUGIN_DIR . 'data/dosen_profiles.json';
    if (!file_exists($json_path)) {
        return array('new' => 0, 'updated' => 0);
    }
    $rows = json_decode(file_get_contents($json_path), true);
    if (!is_array($rows)) {
        return array('new' => 0, 'updated' => 0);
    }

    foreach ($rows as $row) {
        $slug  = sanitize_title($row['slug']);
        $nama  = $row['nama'];
        $existing = get_page_by_path($slug, OBJECT, 'dosen');

        if ($existing) {
            wp_update_post(array('ID' => $existing->ID, 'post_title' => $nama));
            $post_id = $existing->ID;
            $updated++;
        } else {
            $post_id = wp_insert_post(array(
                'post_type'   => 'dosen',
                'post_title'  => $nama,
                'post_name'   => $slug,
                'post_status' => 'publish',
            ));
            if (is_wp_error($post_id) || !$post_id) continue;
            $new++;
        }

        update_post_meta($post_id, 'tppu_pendidikan', $row['pendidikan'] ?? '');
        update_post_meta($post_id, 'tppu_jabatan', $row['jabatan'] ?? '');
        if (!empty($row['penelitian_pkm'])) {
            update_post_meta($post_id, 'tppu_penelitian_pkm', implode("\n", $row['penelitian_pkm']));
        }
    }

    return array('new' => $new, 'updated' => $updated);
}

function tppu_import_pages() {
    $new = 0;
    $skip = 0;
    $updated = 0;
    $beranda_id = null;

    $hero_img      = plugins_url('assets/img/hero-poltekbang.jpg', __FILE__);
    $hero_blur_img = plugins_url('assets/img/hero-staf-blur.jpg', __FILE__);
    $logo_img      = plugins_url('assets/img/logo-poltekbang.png', __FILE__);

    $beranda_html = str_replace(
        array('{{HERO_IMG}}', '{{LOGO_IMG}}'),
        array($hero_img, $logo_img),
        TPPU_PAGE_BERANDA_HTML
    );

    // Hero kecil (foto blur + judul + logo bulat) dipakai di Staf Pengajar & Kurikulum,
    // persis desain web referensi.
    $page_hero = function ($title, $subtitle) use ($hero_blur_img, $logo_img) {
        $title    = esc_html($title);
        $subtitle = esc_html($subtitle);
        return <<<HTML
<div class="tppu-page-hero" style="background-image:linear-gradient(rgba(11,37,69,0.55), rgba(19,49,92,0.65)), url('{$hero_blur_img}');">
  <div class="tppu-wrap">
    <div class="tppu-page-hero-top"><a href="/beranda/">&larr; Kembali</a></div>
    <div class="tppu-page-hero-inner">
      <div class="tppu-page-hero-text">
        <h1>{$title}</h1>
        <p>{$subtitle}</p>
      </div>
      <div class="tppu-page-hero-logo"><img src="{$logo_img}" alt="Logo Poltekbang Medan"></div>
    </div>
  </div>
</div>
HTML;
    };

    $staf_hero = $page_hero('Staf Pengajar', 'Berikut daftar dosen pengajar Program Studi Teknologi Pemeliharaan Pesawat Udara (TPPU), Politeknik Penerbangan Medan.');
    $kurikulum_hero = $page_hero('Kurikulum & Silabus', 'Struktur kurikulum dan mata kuliah Program Studi Teknologi Pemeliharaan Pesawat Udara (TPPU), Politeknik Penerbangan Medan.');

    $pages = array(
        array(
            'slug'      => 'beranda',
            'title'     => 'Beranda',
            'content'   => "<!-- wp:html -->\n" . $beranda_html . "\n<!-- /wp:html -->",
            'managed'   => true,
        ),
        array(
            'slug'      => 'tentang-kami',
            'title'     => 'Tentang Kami',
            'content'   => "<!-- wp:html -->\n" . TPPU_PAGE_TENTANG_HTML . "\n<!-- /wp:html -->",
            'managed'   => true,
        ),
        array(
            'slug'      => 'staf-pengajar',
            'title'     => 'Staf Pengajar',
            'content'   => "<!-- wp:html -->\n" . $staf_hero . "\n<!-- /wp:html -->\n<!-- wp:shortcode -->[tppu_dosen]<!-- /wp:shortcode -->",
            'managed'   => true,
        ),
        array(
            'slug'      => 'kurikulum-silabus',
            'title'     => 'Kurikulum & Silabus',
            'content'   => "<!-- wp:html -->\n" . $kurikulum_hero . "\n<!-- /wp:html -->\n<!-- wp:shortcode -->[tppu_kurikulum]<!-- /wp:shortcode -->",
            'managed'   => true,
        ),
        array(
            'slug'      => 'kontak',
            'title'     => 'Kontak',
            'content'   => "<!-- wp:html -->\n" . TPPU_PAGE_KONTAK_HTML . "\n<!-- /wp:html -->",
            'managed'   => true,
        ),
    );

    foreach ($pages as $p) {
        $existing = get_page_by_path($p['slug'], OBJECT, 'page');
        if ($existing) {
            if ($p['slug'] === 'beranda') { $beranda_id = $existing->ID; }
            // Halaman ini dikelola plugin (dibuat lewat importer) — konten ditimpa
            // ulang tiap kali "Import Sekarang" diklik, supaya update desain plugin
            // otomatis ikut ter-apply tanpa perlu edit manual satu-satu.
            wp_update_post(array(
                'ID'           => $existing->ID,
                'post_content' => $p['content'],
                'post_status'  => 'publish',
            ));
            $updated++;
            continue;
        }
        $id = wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $p['title'],
            'post_name'    => $p['slug'],
            'post_content' => $p['content'],
        ));
        if ($id && !is_wp_error($id)) {
            $new++;
            if ($p['slug'] === 'beranda') { $beranda_id = $id; }
        }
    }

    // Set Beranda sebagai halaman depan situs
    if ($beranda_id) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $beranda_id);
    }

    // Susun menu utama otomatis kalau tema punya lokasi menu terdaftar
    $locations = get_registered_nav_menus();
    if (!empty($locations)) {
        $menu_name = 'Menu Utama TPPU';
        $menu = wp_get_nav_menu_object($menu_name);
        if (!$menu) {
            $menu_id = wp_create_nav_menu($menu_name);
        } else {
            $menu_id = $menu->term_id;
        }
        if ($menu_id && !is_wp_error($menu_id)) {
            $existing_items = wp_get_nav_menu_items($menu_id);
            if (empty($existing_items)) {
                foreach ($pages as $p) {
                    $page_obj = get_page_by_path($p['slug'], OBJECT, 'page');
                    if ($page_obj) {
                        wp_update_nav_menu_item($menu_id, 0, array(
                            'menu-item-title'     => $p['title'],
                            'menu-item-object'    => 'page',
                            'menu-item-object-id' => $page_obj->ID,
                            'menu-item-type'      => 'post_type',
                            'menu-item-status'    => 'publish',
                        ));
                    }
                }
            }
            $first_location = array_key_first($locations);
            $current_locations = get_theme_mod('nav_menu_locations', array());
            if (empty($current_locations[$first_location])) {
                $current_locations[$first_location] = $menu_id;
                set_theme_mod('nav_menu_locations', $current_locations);
            }
        }
    }

    return array('pages_new' => $new, 'pages_skip' => $skip, 'pages_updated' => $updated);
}
