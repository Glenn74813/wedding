<?php
define('NO_SESSION', 1);
require 'config.php';
$st = db()->prepare('SELECT mime, data FROM photos WHERE id=?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$st->bindColumn(1, $mime);
$st->bindColumn(2, $data, PDO::PARAM_LOB);
if (!$st->fetch(PDO::FETCH_BOUND)) { http_response_code(404); exit; }
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=31536000, immutable');
is_resource($data) ? fpassthru($data) : print($data);
