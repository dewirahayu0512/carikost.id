<?php

// 1. PERBAIKAN: Menggunakan konstanta ajaib PHP yang benar (__DIR__)
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

// 2. PERBAIKAN: Menggunakan konstanta ajaib PHP yang benar (__DIR__)
$serviceAccountPath = __DIR__ . '/firebase_key.json';

// --- LOGIKA KONEKSI ---

// Cek apakah file kunci layanan ada
if (!file_exists($serviceAccountPath)) {
    // Keluar jika file kunci tidak ditemukan
    die("Error Koneksi: File firebase_key.json tidak ditemukan pada jalur: " . $serviceAccountPath);
}

try {
    // Inisiasi Factory Firebase
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        // URL regional sudah benar
        ->withDatabaseUri('https://carikost-id-default-rtdb.asia-southeast1.firebasedatabase.app');

    // Buat instance database
    $database = $factory->createDatabase();

} catch (FirebaseException $e) {
    // Tangani kesalahan Firebase
    die("Error Firebase: Gagal menginisiasi koneksi database. Pesan: " . $e->getMessage());

} catch (\Exception $e) {
    // Tangani kesalahan umum
    die("Error Umum: Terjadi kesalahan yang tidak terduga saat koneksi. Pesan: " . $e->getMessage());
}

?>