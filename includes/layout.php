<?php
function render_header($title = 'Admin System')
{
?>
    <!doctype html>
    <html lang="th">

    <head>

        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= htmlspecialchars($title) ?></title>

            <!-- Bootstrap -->
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

            <!-- Google Fonts (Poppins + Kanit) -->
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <!-- โหลดน้ำหนักยอดนิยม; ปรับได้ตามต้องการ -->
            <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap&subset=latin,latin-ext,thai" rel="stylesheet">

            <!-- ฟอนต์อัตโนมัติ: อังกฤษ=Poppins, ไทย=Kanit -->
            <style>
                /* ให้ Bootstrap ใช้ฟอนต์เรา */
                :root {
                    --bs-body-font-family: 'Poppins', 'Kanit', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif;
                    --bs-font-sans-serif: var(--bs-body-font-family);
                }

                /* ใช้กับทั้งระบบ */
                html,
                body {
                    font-family: var(--bs-body-font-family);
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                    text-rendering: optimizeLegibility;
                    font-variant-numeric: tabular-nums;
                }

                /* กรณีมีการตั้ง lang บน element — จะบังคับใช้ฟอนต์ตรงภาษา */
                :lang(th) {
                    font-family: 'Kanit', 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif;
                }

                :lang(en) {
                    font-family: 'Poppins', 'Kanit', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif;
                }

                /* ยูทิลิตี้เผื่ออยากบังคับเป็นรายบล็อก */
                .th {
                    font-family: 'Kanit', 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif;
                }

                .en {
                    font-family: 'Poppins', 'Kanit', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif;
                }
            </style>
        </head>

    </head>

    <body class="bg-light">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container">
                <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard.php">Backoffice</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="nav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tours_create.php">Tours</a></li>
                        <?php
                        // อ่านบทบาทจาก session แล้ว normalize
                        $__role = strtolower(trim($_SESSION['user']['role'] ?? 'guest'));
                        if ($__role === 'director'):
                        ?>

                            <?php if (in_array($__role, ['director', 'manager'], true)): ?>
                                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/bookings.php">Bookings</a></li>
                            <?php endif; ?>

                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/users.php">Users</a></li>
                        <?php endif; ?>
                        <?php if (in_array($__role, ['director', 'manager'], true)): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/guides.php">Guides</a></li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/hotels.php">
                                <i class="bi bi-building"></i> Hotels
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/transportations.php">
                                <i class="bi bi-truck"></i> Transportation
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/costsheets.php">
                                <i class="bi bi-truck"></i> Cost sheets
                            </a>
                        </li>

                        <?php if (in_array($__role, ['director', 'manager'], true)): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/logs.php">Logs</a></li>
                        <?php endif; ?>

                    </ul>
                    </ul>
                    <ul class="navbar-nav ms-auto align-items-center">
                        <?php if (!empty($_SESSION['user'])): ?>
                            <li class="nav-item d-flex align-items-center">
                                <span class="navbar-text me-3 text-nowrap">
                                    สวัสดี,&nbsp;<strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong>
                                </span>
                                <a class="btn btn-sm btn-outline-light px-3" href="<?= BASE_URL ?>/logout.php">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="btn btn-sm btn-outline-light px-3" href="<?= BASE_URL ?>/login.php">Login</a>
                            </li>
                        <?php endif; ?>
                    </ul>


                </div>
            </div>
        </nav>
        <div class="container">
        <?php
    }

    function render_footer()
    {
        ?>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>

    </html>
<?php
    }
