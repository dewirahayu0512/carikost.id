<?php
session_start();
include 'db.php';

$id_kost = $_GET['id']; // ID kost dari URL

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Hitung total (Logika sederhana)
    $durasi = $_POST['durasi']; // per bulan
    $harga_per_bulan = $_POST['harga_hidden'];
    $total = $durasi * $harga_per_bulan;
    
    // Simpan ke collection 'bookings'
    $bookingData = [
        'kost_id' => $id_kost,
        'penyewa_id' => $_SESSION['user_id'],
        'tanggal_booking' => new DateTime(),
        'durasi' => $durasi,
        'total_bayar' => $total,
        'status' => 'menunggu_pembayaran', // Default status
        'metode_bayar' => 'QRIS' // Sesuai US007
    ];
    
    $docRef = $database->collection('bookings')->add($bookingData);
    
    // Redirect ke halaman pembayaran (Tampilkan QRIS dummy)
    header("Location: payment.php?booking_id=" . $docRef->id());
}
?>