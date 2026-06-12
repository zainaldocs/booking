<?php
require_once 'config/database.php';

// Fetch Ruangan
$stmt_ruangan = $pdo->query("SELECT * FROM ruangan");
$ruangans = $stmt_ruangan->fetchAll(PDO::FETCH_ASSOC);

// Fetch Jenis Fasilitas
$stmt_jf = $pdo->query("SELECT * FROM jenis_fasilitas");
$jenis_fasilitas = $stmt_jf->fetchAll(PDO::FETCH_ASSOC);

// Fetch Departemen
$stmt_dept = $pdo->query("SELECT * FROM departemen ORDER BY nama_departemen ASC");
$departemens = $stmt_dept->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'success') $message = '<div class="bg-green-100 text-green-700 p-4 rounded mb-4 shadow">Request peminjaman berhasil dikirim. Menunggu persetujuan admin.</div>';
    elseif ($_GET['msg'] == 'error') $message = '<div class="bg-red-100 text-red-700 p-4 rounded mb-4 shadow">Terjadi kesalahan. Silakan coba lagi.</div>';
    elseif ($_GET['msg'] == 'time_invalid') $message = '<div class="bg-red-100 text-red-700 p-4 rounded mb-4 shadow"><strong>Peminjaman Gagal!</strong><br>Waktu peminjaman harus antara jam 08:00 - 17:00 dan Jam Mulai harus sebelum Jam Selesai.</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Booking Ruang Meeting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/fullcalendar.global.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-72 bg-white shadow-lg h-full flex flex-col z-10">
        <div class="p-6 border-b border-gray-100 bg-brand-50 bg-opacity-50">
            <h1 class="text-2xl font-bold text-brand-600 flex items-center space-x-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>BookingRoom</span>
            </h1>
        </div>
        <nav class="flex-1 p-6 space-y-3">
            <a href="form_booking.php" class="block w-full btn-primary text-center shadow-md transform hover:-translate-y-0.5 transition-all mb-8">
                + Form Peminjaman
            </a>
            <a href="index.php" class="block px-4 py-3 rounded-lg bg-brand-50 text-brand-600 font-semibold shadow-sm transition">Dashboard Kalender</a>
            <a href="cek_status.php" class="block px-4 py-3 rounded-lg text-gray-600 hover:bg-brand-50 hover:text-brand-600 font-semibold transition">Cek Status / Batal</a>
        </nav>
        <div class="p-6 border-t border-gray-100 bg-gray-50">
            <a href="admin/login.php" class="block w-full px-4 py-3 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 text-center font-semibold transition-all shadow-sm">
                Login Panel Admin
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-y-auto p-8">
        <div class="max-w-6xl w-full mx-auto flex-1 flex flex-col">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Jadwal Ruang Meeting</h2>
                    <p class="text-gray-500 mt-1">Lihat ketersediaan ruang meeting yang telah disetujui.</p>
                </div>
            </div>
            
            <?= $message ?>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col">
                <div id="calendar" class="flex-1"></div>
            </div>
        </div>
    </main>



    <script>
        if (window.history.replaceState && window.location.search.includes('msg=')) {
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({path: url.href}, '', url.href);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                allDaySlot: false,
                hiddenDays: [0, 6], // Hide Sunday, Saturday
                events: 'api_events.php',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                themeSystem: 'standard',
                eventColor: '#f97316',
                height: '100%'
            });
            calendar.render();
        });
    </script>
</body>
</html>
