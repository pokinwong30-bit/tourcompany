<?php
// lib/helpers_public.php

// --- base_url(): แก้เคส BASE_URL ไม่ได้ define ---
if (!function_exists('base_url')) {
    function base_url(): string {
        // ถ้า define('BASE_URL', ...) ไว้แล้ว ให้ใช้
        if (defined('BASE_URL')) return rtrim((string)BASE_URL, '/');
        // รองรับ .env -> APP_URL (กรณี layout.php ตั้งไว้)
        $env = getenv('APP_URL') ?: '';
        return rtrim($env, '/');
    }
}

if (!function_exists('h')) {
    function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

// --- thai_date(): มี fallback ถ้าไม่มี Intl ---
if (!function_exists('thai_date')) {
    function thai_date(?string $date, string $format = 'j F Y'): string
    {
        if (!$date) return '';
        $ts = strtotime($date);
        if (!$ts) return '';

        if (class_exists('IntlDateFormatter')) {
            // Buddhist calendar (+543) อัตโนมัติ
            $fmt = new IntlDateFormatter('th_TH@calendar=buddhist', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'Asia/Bangkok', IntlDateFormatter::GREGORIAN, $format);
            $out = $fmt->format($ts);
            if ($out !== false) return $out;
        }

        // --- Fallback แบบ manual ---
        $months = [1=>'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
        $d = (int)date('j', $ts);
        $m = (int)date('n', $ts);
        $y = (int)date('Y', $ts) + 543;
        return $d.' '.$months[$m].' '.$y;
    }
}

if (!function_exists('int_get')) {
    function int_get(string $key, int $default = 1): int {
        $v = $_GET[$key] ?? $default;
        if (!is_numeric($v)) return $default;
        $v = (int)$v;
        return $v > 0 ? $v : $default;
    }
}

if (!function_exists('paginate_meta')) {
    function paginate_meta(int $total, int $page, int $per_page): array {
        $pages = max(1, (int)ceil($total / $per_page));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $per_page;
        return compact('pages','page','per_page','offset','total');
    }
}

if (!function_exists('render_pagination')) {
    function render_pagination(int $pages, int $page, array $extra_params = []): string {
        if ($pages <= 1) return '';
        $qs = function($p) use ($extra_params) {
            $params = array_merge($_GET, $extra_params, ['page' => $p]);
            return '?' . http_build_query($params);
        };
        ob_start(); ?>
        <nav class="d-flex justify-content-center py-3">
          <ul class="pagination pagination-lg flex-wrap gap-2">
            <li class="page-item <?= $page<=1?'disabled':'' ?>">
              <a class="page-link" href="<?= $qs(max(1,$page-1)) ?>" aria-label="Previous">&laquo;</a>
            </li>
            <?php
              $window = 2;
              $start = max(1, $page - $window);
              $end   = min($pages, $page + $window);
              if ($start > 1) {
                  echo '<li class="page-item"><a class="page-link" href="'.$qs(1).'">1</a></li>';
                  if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
              }
              for ($i = $start; $i <= $end; $i++) {
                  $active = $i == $page ? 'active' : '';
                  echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$qs($i).'">'. $i .'</a></li>';
              }
              if ($end < $pages) {
                  if ($end < $pages-1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                  echo '<li class="page-item"><a class="page-link" href="'.$qs($pages).'">'.$pages.'</a></li>';
              }
            ?>
            <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
              <a class="page-link" href="<?= $qs(min($pages,$page+1)) ?>" aria-label="Next">&raquo;</a>
            </li>
          </ul>
        </nav>
        <style>
          .pagination .page-link{line-height:1.4;padding:.6rem .9rem;min-width:44px}
          .pagination{row-gap:.5rem}
        </style>
        <?php return ob_get_clean();
    }
}
