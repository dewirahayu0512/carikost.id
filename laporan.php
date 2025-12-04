<?php
session_start();
include 'db.php';

// 1. Cek Login Pemilik
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// 2. Ambil Filter Bulan & Tahun (Default: Bulan Ini)
$bulan_pilih = $_GET['bulan'] ?? date('m');
$tahun_pilih = $_GET['tahun'] ?? date('Y');

// Array Nama Bulan untuk Tampilan
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// 3. Ambil Data Booking
$bookings = $database->getReference('bookings')->getValue();
$laporan = [];
$total_pendapatan = 0;

if ($bookings && is_array($bookings)) {
    foreach ($bookings as $b) {
        // Filter: Milik Owner INI + Status DIKONFIRMASI + Bulan/Tahun Cocok
        if (isset($b['owner_id']) && $b['owner_id'] == $uid && $b['status'] == 'Dikonfirmasi') {
            
            $tgl_transaksi = $b['tanggal_booking'] ?? ''; // Format: YYYY-MM-DD
            $bulan_transaksi = date('m', strtotime($tgl_transaksi));
            $tahun_transaksi = date('Y', strtotime($tgl_transaksi));

            if ($bulan_transaksi == $bulan_pilih && $tahun_transaksi == $tahun_pilih) {
                // Ambil data penyewa untuk laporan
                $b['nama_penyewa'] = $b['nama_penyewa'] ?? 'N/A';
                
                $laporan[] = $b;
                $total_pendapatan += $b['total_bayar'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Carikost.id</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        /* --- THEME CONFIGURATION (TEAL MODERN) --- */
        :root {
            --color-teal: #00796B;
            --color-teal-dark: #004d40;
            --color-teal-light: #e0f2f1;
            --color-red: #d32f2f;
            --color-text: #2f3542;
            --bg-color: #f4f6f8; 
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-color); 
            padding: 25px 0; 
            color: var(--color-text); 
        }
        .container { max-width: 900px; margin: 0 auto; padding: 0 15px; }
        
        /* --- Filter Section --- */
        .filter-card { 
            background: white; 
            padding: 15px 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            margin-bottom: 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-left: 5px solid var(--color-teal);
        }
        .filter-form { display: flex; gap: 10px; align-items: center; }
        
        select, .btn-filter { 
            padding: 10px 15px; 
            border-radius: 8px; 
            border: 1px solid #ddd; 
            font-family: 'Poppins', sans-serif;
            font-size: 0.9em;
            transition: 0.3s;
        }
        select:focus { border-color: var(--color-teal); outline: none; }
        
        .btn-filter { 
            background: var(--color-teal); 
            color: white; 
            border: none; 
            cursor: pointer; 
            font-weight: 600; 
        }
        .btn-filter:hover { background: var(--color-teal-dark); }
        
        .btn-download { 
            background: var(--color-red); 
            color: white; 
            border: none; 
            cursor: pointer; 
            font-weight: 600; 
            padding: 10px 20px; 
            border-radius: 8px;
            display: flex; 
            align-items: center; 
            gap: 8px; 
            box-shadow: 0 4px 10px rgba(211, 47, 47, 0.3);
        }
        .btn-download:hover { background: #b71c1c; }
        .btn-back { 
            text-decoration: none; 
            color: var(--color-teal); 
            font-weight: 600; 
            font-size: 0.9em; 
            display: flex; 
            align-items: center; 
            gap: 5px;
        }

        /* --- Report Paper Style (Untuk Cetak/PDF) --- */
        .report-paper { 
            background: white; 
            padding: 50px; 
            border-radius: 10px; 
            box-shadow: 0 0 20px rgba(0,0,0,0.1); 
            min-height: 800px;
        }
        .report-header { 
            text-align: center; 
            border-bottom: 3px solid var(--color-teal); 
            padding-bottom: 20px; 
            margin-bottom: 40px; 
        }
        .report-header h1 { 
            margin: 0; 
            font-size: 1.8em; 
            font-weight: 800;
            color: var(--color-teal);
            letter-spacing: 0.5px;
        }
        .report-header p { 
            margin: 5px 0 0; 
            color: #555; 
            font-size: 0.9em;
        }

        /* --- Table Styling --- */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            font-size: 0.9em; 
        }
        th, td { 
            border: 1px solid #e0e0e0; 
            padding: 10px 12px; 
            text-align: left; 
        }
        th { 
            background-color: var(--color-teal); 
            color: white; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.8em; 
        }
        tbody tr:nth-child(even) { background-color: #f9f9f9; } /* Stripe */
        
        .total-row td { 
            background-color: var(--color-teal-light); 
            font-weight: 700; 
            font-size: 1em; 
            color: var(--color-teal-dark); 
            border-top: 3px solid var(--color-teal);
        }
        
        /* --- Tanda Tangan (PERBAIKAN) --- */
        .signature-area { 
            margin-top: 80px; /* Jarak lebih jauh dari tabel */
            text-align: right; 
            width: 100%;
        }
        .signature-box {
            display: inline-block;
            width: 250px; /* Lebar kotak tanda tangan */
            text-align: center;
        }
        .signature-box p { margin: 0; font-size: 0.9em; color: #333; }
        .signature-line {
            display: block;
            margin-top: 80px; /* Jarak untuk tanda tangan manual */
            border-bottom: 1px solid #333; /* Garis Tanda Tangan */
            padding-top: 5px;
        }
        .signature-box strong { 
            display: block; 
            margin-top: 5px; 
            font-weight: 700;
        }

        @media print {
            body { background: white; padding: 0; }
            .report-paper { box-shadow: none; border-radius: 0; min-height: 100vh; }
            th { background-color: #eee !important; color: #333 !important; -webkit-print-color-adjust: exact; }
            .total-row td { background-color: #e0f2f1 !important; -webkit-print-color-adjust: exact; }
            .report-header h1 { color: #000 !important; }
            
            /* Sembunyikan garis pemisah untuk tanda tangan jika PDF sudah menyertakannya */
            .signature-area p.small-print { font-size: 0.7em; margin-top: 2px; color: #555; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="filter-card" data-html2canvas-ignore="true">
            <div>
                <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
            </div>
            
            <form method="GET" class="filter-form">
                <select name="bulan">
                    <?php foreach($nama_bulan as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $k == $bulan_pilih ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun">
                    <?php for($y=date('Y'); $y>=2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $tahun_pilih ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-filter"><i class="fa-solid fa-search"></i> Tampilkan</button>
            </form>

            <button onclick="downloadPDF()" class="btn-download">
                <i class="fa-solid fa-file-pdf"></i> Download PDF
            </button>
        </div>

        <div class="report-paper" id="print-area">
            
            <div class="report-header">
                <h1>LAPORAN PENDAPATAN KOST</h1>
                <p>Periode: <strong><?= $nama_bulan[$bulan_pilih] ?> <?= $tahun_pilih ?></strong></p>
                <p style="font-size: 0.9em; margin-top: 5px;">Dicetak pada: <?= date('d-m-Y H:i') ?></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">Tanggal</th>
                        <th style="width: 25%;">Nama Penyewa</th>
                        <th style="width: 25%;">Kost</th>
                        <th style="width: 10%;">Durasi</th>
                        <th style="width: 10%;">Metode</th>
                        <th style="width: 10%; text-align: right;">Jumlah (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($laporan)): 
                        $no = 1;
                        foreach ($laporan as $row):
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal_booking'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_penyewa']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kost']) ?></td>
                            <td><?= $row['durasi_bulan'] ?> Bln</td>
                            <td><?= $row['metode_bayar'] ?></td>
                            <td style="text-align: right;">
                                <?= number_format($row['total_bayar'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #888;">
                                <i class="fa-solid fa-folder-open" style="font-size: 1.5em; display: block; margin-bottom: 10px;"></i>
                                Tidak ada transaksi terkonfirmasi pada periode *<?= $nama_bulan[$bulan_pilih] ?> <?= $tahun_pilih ?>*.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="6" style="text-align: right;">TOTAL PENDAPATAN BULAN INI</td>
                        <td style="text-align: right;">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="signature-area">
                <div class="signature-box">
                    <p>Mengetahui,</p>
                    <span class="signature-line"></span>
                    <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>
                    <p class="small-print">Pemilik Kost</p>
                </div>
            </div>
        </div>

    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('print-area');
            const opt = {
                margin:       10,
                filename:     'Laporan_Keuangan_<?= $nama_bulan[$bulan_pilih] ?>_<?= $tahun_pilih ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Generate PDF
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>