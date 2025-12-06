<?php
// lib/countries.php

require_once __DIR__ . '/helpers_public.php';

const COUNTRIES_CSV = __DIR__ . '../data/countries.csv';
const COUNTRIES_CACHE = __DIR__ . '../data/countries.cache.php';

function countries_load(): array {
    // 1) พยายามอ่านจาก cache ก่อน (ถ้ามีและยังไม่หมดอายุ)
    if (is_file(COUNTRIES_CACHE)) {
        try {
            /** @var array $dat */
            $dat = @include COUNTRIES_CACHE; // ต้อง return array ['ts'=>..., 'items'=>[...] ]
            if (is_array($dat) && isset($dat['ts'], $dat['items']) && (time() - (int)$dat['ts'] < 86400)) {
                if (is_array($dat['items'])) return $dat['items'];
            }
        } catch (\Throwable $e) {
            // เงียบไว้ ใช้ CSV ต่อ
        }
    }

    // 2) โหลดจาก CSV (แหล่งจริง)
    $items = [];
    if (!is_file(COUNTRIES_CSV)) {
        // ไม่มี CSV ก็คืนค่าว่าง (กัน error หน้าบ้าน)
        return $items;
    }

    if (($fh = @fopen(COUNTRIES_CSV, 'r')) !== false) {
        // header: iso2,name_th,name_en
        $header = fgetcsv($fh);
        while (($row = fgetcsv($fh)) !== false) {
            [$iso2, $name_th, $name_en] = array_pad($row, 3, '');
            $iso2 = strtoupper(trim((string)$iso2));
            if ($iso2 === '') continue;
            $items[$iso2] = [
                'iso2'    => $iso2,
                'name_th' => trim((string)($name_th ?: $name_en)),
                'name_en' => trim((string)($name_en ?: $name_th)),
            ];
        }
        fclose($fh);
    }

    // 3) พยายามเขียน cache (ถ้าโฟลเดอร์เขียนไม่ได้ ให้ข้าม)
    $dir = dirname(COUNTRIES_CACHE);
    if (is_dir($dir) && is_writable($dir)) {
        $payload = ['ts' => time(), 'items' => $items];
        // เขียนเป็นไฟล์ PHP ที่ return array เพื่อลด parse cost
        @file_put_contents(COUNTRIES_CACHE, "<?php\nreturn " . var_export($payload, true) . ";\n");
    }
    return $items;
}


function countries_whitelist(): array {
    static $wl = null;
    if ($wl !== null) return $wl;
    $wl = array_keys(countries_load());
    return $wl;
}

function countries_name_th(string $iso2): string {
    $all = countries_load();
    $iso2 = strtoupper($iso2);
    return $all[$iso2]['name_th'] ?? $iso2;
}

function countries_dropdown_having_tours(PDO $pdo): array {
    // ดึง iso ที่มีทัวร์ต่างประเทศ (ไม่รวม TH)
    $stmt = $pdo->query("SELECT DISTINCT country_iso FROM tours WHERE is_on_sale = 1 AND country_iso <> 'TH'");
    $dbIso = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$dbIso) return [];
    $dbIso = array_map('strtoupper', $dbIso);

    // intersect กับ whitelist จาก CSV
    $wl = countries_whitelist();
    $valid = array_values(array_intersect($dbIso, $wl));

    // map เป็น array พร้อมชื่อไทย แล้วเรียงตามชื่อไทย
    $out = [];
    $all = countries_load();
    foreach ($valid as $iso) {
        $out[] = ['iso2'=>$iso, 'name_th'=>$all[$iso]['name_th'] ?? $iso];
    }
    usort($out, fn($a,$b)=>mb_strcasecmp($a['name_th'],$b['name_th'],'UTF-8'));
    return $out;
}

// polyfill เปรียบเทียบแบบไม่สนตัวพิมพ์ใหญ่เล็ก และกันกรณีไม่มี mbstring
if (!function_exists('mb_strcasecmp')) {
    function mb_strcasecmp($a,$b,$enc='UTF-8'){
        if (function_exists('mb_strtolower')) {
            return strcmp(mb_strtolower($a,$enc), mb_strtolower($b,$enc));
        }
        return strcmp(strtolower($a), strtolower($b));
    }
}
