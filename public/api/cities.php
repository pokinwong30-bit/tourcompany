<?php
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

$country_id = (int)($_GET['country_id'] ?? 0);
if ($country_id <= 0) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("SELECT id, name_en, COALESCE(name_th, name_en) AS name_th FROM cities WHERE country_id=? ORDER BY name_en");
$stmt->execute([$country_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
