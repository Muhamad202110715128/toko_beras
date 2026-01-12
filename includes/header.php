<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. INCLUDE FILE PENTING
// Gunakan dirname agar path aman dari manapun file ini di-include
include_once dirname(__FILE__) . '/config.php';
include_once dirname(__FILE__) . '/fungsi_notifikasi.php';

$role = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? '';

// 2. LOGIKA TAMPILAN ROLE
$folderNotif = 'Admin'; // default aman

if (!empty($_SESSION['role'])) {
  $role = strtolower($_SESSION['role']);

  if ($role === 'admin') {
    $folderNotif = 'Admin';
  } elseif ($role === 'kasir') {
    $folderNotif = 'Kasir';
  } elseif ($role === 'pemilik') {
    $folderNotif = 'Pemilik';
  }
}

$icon = '👤'; // Saya kembalikan ke icon orang standar agar tidak error encoding
$avatarBg = '#6c757d';
$roleLabel = '';

if ($role === 'admin') {
  $roleLabel = 'Admin';
  $avatarBg = '#15d736ff';
} elseif ($role === 'kasir') {
  $avatarBg = '#15d736ff';
  $roleLabel = 'Kasir';
} elseif ($role === 'pemilik') {
  $avatarBg = '#15d736ff';
  $roleLabel = 'Pemilik';
}

// 3. AUTO CHECK TRIGGER (jalankan sekali per request)
if (isset($koneksi) && !defined('NOTIF_CHECK_DONE')) {
  define('NOTIF_CHECK_DONE', true);
  cekPeringatanStokOtomatis($koneksi);
}

// 4. AMBIL DATA NOTIFIKASI
$jumlah_notif = 0;
$list_notif = null;
$notif_rows = [];

if ($role && isset($koneksi)) {
  // gunakan role asli; fungsi notifikasi akan menormalisasi ('owner' <-> 'pemilik')
  $role_db = $role;
  $list_notif = ambilNotifikasi($koneksi, $role_db);
  // hitung unread (fungsi menangani normalisasi)
  $jumlah_notif = hitungNotifikasiBelumDibaca($koneksi, $role_db);

  // Proses hasil query menjadi array
  if ($list_notif && is_object($list_notif) && method_exists($list_notif, 'fetch_assoc')) {
    while ($r = $list_notif->fetch_assoc()) {
      $notif_rows[] = $r;
    }
  } elseif (is_array($list_notif)) {
    $notif_rows = $list_notif;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Stok Beras</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../style.css">

  <style>

  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-success py-2 mb-4">
    <div class="container d-flex justify-content-between align-items-center">

      <!-- BRAND -->
      <div class="d-flex align-items-center">
        <span class="navbar-brand mb-0 h4 text-white" id="brandTitle">Agen Beras Idola</span>
      </div>

      <!-- AREA KANAN -->
      <div class="d-flex align-items-center">

        <!-- A. NOTIFIKASI -->
        <?php if ($role): ?>
          <div class="dropdown dropstart me-3 position-relative">
            <a class="text-white notification-wrapper" href="#" role="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
              <i class="bi bi-bell-fill" style="font-size: 1.25rem; position:relative;"></i>
              <?php if (!empty($jumlah_notif) && $jumlah_notif > 0): ?>
                <span class="badge bg-danger notification-badge"><?= (int)$jumlah_notif ?></span>
              <?php endif; ?>
            </a>

            <div class="dropdown-menu shadow dropdown-menu-notif" aria-labelledby="notifDropdown" data-bs-popper="static">
              <div class="notif-header">Notifikasi <?= htmlspecialchars($roleLabel ?? ucfirst($role ?? '')) ?></div>

              <div class="notif-list">
                <?php if (!empty($notif_rows)): ?>
                  <?php foreach ($notif_rows as $n): ?>
                    <?php
                    $id = (int)($n['id'] ?? 0);
                    $judul = htmlspecialchars($n['judul'] ?? '');
                    $pesan = htmlspecialchars($n['pesan'] ?? '');
                    $link = htmlspecialchars($n['link'] ?? '#');
                    $created = !empty($n['created_at']) ? date('d/m H:i', strtotime($n['created_at'])) : '';
                    $isUnread = (($n['status'] ?? '') === 'unread');
                    ?>
                    <a class="notif-item <?= $isUnread ? 'notif-unread' : '' ?>" href="/toko_beras/includes/baca_notifikasi.php?id=<?= $id ?>&redirect=<?= urlencode($link) ?>">
                      <div class="d-flex justify-content-between align-items-start">
                        <div class="notif-title"><?= $judul ?></div>
                        <div class="notif-time"><?= $created ?></div>
                      </div>
                      <div class="notif-msg"><?= $pesan ?></div>
                    </a>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-4 text-muted small">
                    <i class="bi bi-bell-slash" style="font-size:1.4rem; display:block; margin-bottom:.5rem;"></i>
                    Tidak ada notifikasi baru
                  </div>
                <?php endif; ?>
              </div>

            </div>
          </div>
        <?php endif; ?>

        <!-- B. USER INFO -->
        <?php if ($role): ?>
          <div class="d-flex align-items-center">
            <div class="user-avatar" style="background: <?= htmlspecialchars($avatarBg) ?>;">
              <?= $icon ?>
            </div>
            <div class="user-info ms-2 d-none d-sm-block">
              <div class="name"><?= htmlspecialchars($username ?: $roleLabel) ?></div>
              <div class="role"><?= htmlspecialchars($roleLabel) ?></div>
            </div>
          </div>
        <?php endif; ?>

        <!-- C. HAMBURGER -->
        <button class="btn btn-hamburger ms-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
          aria-controls="sidebarMenu" title="Menu">
          <span class="bars">☰</span>
        </button>

      </div>
    </div>
  </nav>

  <!-- SCRIPT BOOTSTRAP (Wajib ada) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Notifikasi: sinkronisasi Bootstrap API + fallback ringan (tidak mengganggu admin/kasir) -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var btn = document.getElementById('notifDropdown');
      if (!btn) return;
      var root = btn.closest('.dropdown');
      var menu = root ? root.querySelector('.dropdown-menu') : null;
      var dd = null;

      try {
        dd = bootstrap.Dropdown.getOrCreateInstance(btn);
      } catch (err) {
        dd = null;
      }

      // Pastikan aria-expanded sinkron saat Bootstrap memicu event
      btn.addEventListener('shown.bs.dropdown', function() {
        btn.setAttribute('aria-expanded', 'true');
      });
      btn.addEventListener('hidden.bs.dropdown', function() {
        btn.setAttribute('aria-expanded', 'false');
      });

      // Setelah klik, jika Bootstrap gagal membuka dropdown, buka via API/fallback (tanpa preventDefault)
      btn.addEventListener('click', function() {
        setTimeout(function() {
          var expanded = btn.getAttribute('aria-expanded') === 'true';
          var visible = menu && menu.classList.contains('show');

          if (!expanded && !visible) {
            if (dd && typeof dd.show === 'function') {
              try {
                dd.show();
              } catch (e) {
                /* ignore */
              }
            } else if (menu) {
              menu.classList.add('show');
              btn.classList.add('show');
              btn.setAttribute('aria-expanded', 'true');
            }
          }
        }, 12);
      });

      // Tutup saat klik di luar (gunakan API jika ada)
      document.addEventListener('click', function(ev) {
        if (!root || root.contains(ev.target)) return;
        if (menu && menu.classList.contains('show')) {
          if (dd && typeof dd.hide === 'function') {
            try {
              dd.hide();
            } catch (e) {
              /* ignore */
            }
          } else {
            menu.classList.remove('show');
            btn.classList.remove('show');
            btn.setAttribute('aria-expanded', 'false');
          }
        }
      });
    });
  </script>

</body>

</html>