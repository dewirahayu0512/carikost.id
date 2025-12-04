<?php
include 'db.php';

$kostRef = $database->collection('kosts');

// Contoh Filter Sederhana (Lokasi)
if (isset($_GET['lokasi'])) {
    // Note: Firestore search text terbatas, biasanya butuh Algolia utk search canggih.
    // Kita gunakan where exact match untuk simplifikasi tutorial ini
    $query = $kostRef->where('alamat', '=', $_GET['lokasi']); 
} else {
    $query = $kostRef;
}

$documents = $query->documents();

foreach ($documents as $doc) {
    $k = $doc->data();
    echo "<h3>" . $k['nama_kost'] . "</h3>";
    echo "<p>Harga: Rp " . number_format($k['harga']) . "</p>";
    echo "<a href='detail_kost.php?id=" . $doc->id() . "'>Lihat Detail</a>";
    echo "<hr>";
}
?>