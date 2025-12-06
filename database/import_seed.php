<?php
// database/import_seed.php
require_once __DIR__ . '/../includes/db.php';

function import_csv($path, $cb) {
  if (!file_exists($path)) { echo "Missing: $path\n"; return; }
  if (($h = fopen($path, 'r')) === false) { echo "Cannot open: $path\n"; return; }
  $header = fgetcsv($h); // skip header
  $i = 0;
  while (($row = fgetcsv($h)) !== false) {
    $cb($row);
    $i++;
    if ($i % 1000 === 0) echo ".";
  }
  fclose($h);
  echo "\nImported $i rows from $path\n";
}

/* countries.csv: iso2,name_en,name_th */
import_csv(__DIR__.'/data/countries.csv', function($r) use ($pdo) {
  [$iso2,$name_en,$name_th] = $r + [null,null,null];
  $stmt = $pdo->prepare("INSERT INTO countries (iso2,name_en,name_th) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name_en=VALUES(name_en), name_th=VALUES(name_th)");
  $stmt->execute([$iso2,$name_en,$name_th]);
});

/* cities.csv: country_iso2,name_en,name_th */
import_csv(__DIR__.'/data/cities.csv', function($r) use ($pdo) {
  [$ciso,$name_en,$name_th] = $r + [null,null,null];
  $cid = $pdo->prepare("SELECT id FROM countries WHERE iso2=?");
  $cid->execute([$ciso]);
  $country_id = (int)($cid->fetchColumn() ?: 0);
  if ($country_id) {
    $stmt = $pdo->prepare("INSERT INTO cities (country_id,name_en,name_th) VALUES (?,?,?)");
    $stmt->execute([$country_id,$name_en,$name_th]);
  }
});

/* airlines.csv: iata,icao,name,country_iso2 */
import_csv(__DIR__.'/data/airlines.csv', function($r) use ($pdo) {
  [$iata,$icao,$name,$ciso] = $r + [null,null,null,null];
  $cid = $pdo->prepare("SELECT id FROM countries WHERE iso2=?");
  $cid->execute([$ciso]);
  $country_id = $cid->fetchColumn() ?: null;
  $stmt = $pdo->prepare("INSERT INTO airlines (iata,icao,name,country_id) VALUES (?,?,?,?)");
  $stmt->execute([$iata ?: null,$icao ?: null,$name,$country_id]);
});

/* airports.csv: iata,icao,name,country_iso2,city_name_en */
import_csv(__DIR__.'/data/airports.csv', function($r) use ($pdo) {
  [$iata,$icao,$name,$ciso,$city_en] = $r + [null,null,null,null,null];
  $cid = $pdo->prepare("SELECT id FROM countries WHERE iso2=?");
  $cid->execute([$ciso]);
  $country_id = (int)($cid->fetchColumn() ?: 0);
  if (!$country_id) return;

  // map city (ถ้าไม่มีในเมือง จะสร้างเพิ่มแบบ name_en เท่านั้น)
  $getCity = $pdo->prepare("SELECT id FROM cities WHERE country_id=? AND name_en=? LIMIT 1");
  $getCity->execute([$country_id,$city_en]);
  $city_id = $getCity->fetchColumn();
  if (!$city_id && $city_en) {
    $ins = $pdo->prepare("INSERT INTO cities (country_id,name_en) VALUES (?,?)");
    $ins->execute([$country_id,$city_en]);
    $city_id = $pdo->lastInsertId();
  }

  $stmt = $pdo->prepare("INSERT INTO airports (iata,icao,name,city_id,country_id) VALUES (?,?,?,?,?)");
  $stmt->execute([$iata ?: null,$icao ?: null,$name,$city_id ?: null,$country_id]);
});

echo "DONE.\n";
