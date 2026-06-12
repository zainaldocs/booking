<?php
$tpl = "Kode Booking: [kode_booking]\n<a href='[cancel_link]'>[cancel_link]</a>";
$nama = "Zeze";
$kode_booking = "BKG-123";
$ruangan_name = "Meeting";
$tanggal = "2026";
$waktu = "09:00";
$departemen_name = "RND";
$keterangan_safe = "Test";
$cancel_link = "http://localhost/cancel";

$body_user = str_replace(
    ['[nama]', '[kode_booking]', '[ruangan]', '[tanggal]', '[waktu]', '[departemen]', '[keterangan]', '[cancel_link]'], 
    [$nama, $kode_booking, $ruangan_name, $tanggal, $waktu, $departemen_name, $keterangan_safe, $cancel_link], 
    nl2br($tpl)
);

echo "<pre>";
echo htmlspecialchars($body_user);
echo "</pre>";
?>
