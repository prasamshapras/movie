<?php
require_once '../includes/config.php';
if(!isAdminLoggedIn()) header('Location: ../login.php');

$showtime_id = intval($_GET['showtime_id'] ?? 0);
if(!$showtime_id) die('No showtime id');

$rows = ['A','B','C','D',]; // change to add more rows
$perRow = 10;
$ins = $pdo->prepare("INSERT INTO seats (showtime_id, seat_label, status) VALUES (?, ?, 'available')");
foreach($rows as $r){
  for($i=1;$i<=$perRow;$i++){
    $label = $r.$i;
    $ins->execute([$showtime_id, $label]);
  }
}
echo "<div style='padding:20px; font-family:Inter;'><h3>Seats created for showtime $showtime_id</h3><p><a href='dashboard.php' class='btn'>Back to Admin</a> <a href='movies.php' class='btn-muted'>Manage Movies</a></p></div>";
