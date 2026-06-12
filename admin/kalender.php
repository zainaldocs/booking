<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Jadwal - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="../assets/js/fullcalendar.global.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-y-auto p-8">
        <div class="max-w-6xl w-full mx-auto flex-1 flex flex-col">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Kalender Jadwal</h2>
                    <p class="text-gray-500 mt-1">Lihat ketersediaan dan jadwal ruang meeting secara menyeluruh.</p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col min-h-[600px]">
                <div id="calendar" class="flex-1 h-full"></div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                allDaySlot: false,
                hiddenDays: [0, 6], // Hide Sunday, Saturday
                events: '../api_events.php',
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
