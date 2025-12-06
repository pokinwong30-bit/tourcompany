<?php
// includes/public_layout.php
if (!function_exists('public_base_url')) {
  function public_base_url(): string {
      static $base = null;
      if ($base !== null) return $base;

      // 1) ถ้าตั้ง APP_URL ไว้ใน .env (เช่น http://localhost:8888/tour-company) ให้ใช้ค่านี้
      $env = getenv('APP_URL');
      if ($env) return $base = rtrim($env, '/');

      // 2) ถ้าไม่ได้ตั้ง APP_URL ให้เดาจาก path ปัจจุบันของสคริปต์
      //    เช่น /tour-company/index.php -> คืน /tour-company
      $script = $_SERVER['SCRIPT_NAME'] ?? '';
      $dir = rtrim(str_replace('\\','/', dirname($script)), '/');
      if ($dir === '/' || $dir === '\\') $dir = ''; // root ให้เป็นค่าว่าง
      return $base = $dir;
  }
}


// escape สั้น ๆ
if (!function_exists('h')) {
    function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('public_start_page')) {
    function public_start_page(string $title = 'หน้าเว็บ'): void {
        ?>
        <!doctype html>
        <html lang="th">
        <head>
          <meta charset="utf-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <title><?= h($title) ?></title>

          <!-- Bootstrap 5 -->
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

          <!-- Google Fonts (Poppins + Kanit) -->
          <link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
          <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap&subset=latin,latin-ext,thai" rel="stylesheet">

          <style>
            :root{
              --bs-body-font-family: 'Poppins','Kanit',system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans Thai',sans-serif;
              --bs-font-sans-serif: var(--bs-body-font-family);
            }
            html, body{
              font-family: var(--bs-body-font-family);
              -webkit-font-smoothing: antialiased;
              -moz-osx-font-smoothing: grayscale;
              text-rendering: optimizeLegibility;
              font-variant-numeric: tabular-nums;
            }
          </style>
        </head>
        <body class="bg-light">
        <?php
    }
}

if (!function_exists('public_end_page')) {
    function public_end_page(): void {
        ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body></html>
        <?php
    }
}
