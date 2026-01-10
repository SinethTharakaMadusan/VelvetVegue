<?php
require_once 'Database.php';
header('Content-Type: text/plain');

function out($s) { echo $s . PHP_EOL; }

out('--- DB Status ---');

if (!isset($conn) || !$conn) {
    out('Connection failed: ' . mysqli_connect_error());
    exit;
}

out('MySQL client: ' . mysqli_get_client_info());


$res = mysqli_query($conn, "SELECT DATABASE() AS db");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    out('Current database: ' . ($row['db'] ?? 'NULL'));
} else {
    out('Unable to determine current database: ' . mysqli_error($conn));
}

$tables = ['products','product_images','product_color','products_size'];
foreach ($tables as $t) {
    $ok = mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $t) . "'");
    if ($ok && mysqli_num_rows($ok) > 0) {
        $c = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM `" . $t . "`");
        if ($c) {
            $r = mysqli_fetch_assoc($c);
            out("Table {$t}: EXISTS, rows=" . ($r['cnt'] ?? '0'));
        } else {
            out("Table {$t}: EXISTS, but count failed: " . mysqli_error($conn));
        }
    } else {
        out("Table {$t}: MISSING");
    }
}

out('--- End ---');

?>