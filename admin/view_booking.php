<?php
$page_title = 'View Booking';

require_once '../includes/config.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$booking_id = intval($_GET['booking_id'] ?? 0);

if ($booking_id <= 0) {
    die('Invalid booking ID.');
}

$stmt = $pdo->prepare("
    SELECT 
        b.booking_id,
        b.customer_id,
        b.movie_id,
        b.showtime_id,
        b.seat_label,
        b.amount,
        b.status,
        b.payment_status,
        b.created_at,

        c.name AS customer_name,
        c.email AS customer_email,

        m.title AS movie_title,
        m.genre,
        m.language,
        m.duration,

        s.screen,
        s.show_date,
        s.show_time,

        p.payment_method,
        p.amount AS payment_amount,
        p.payment_status AS payment_record_status,
        p.created_at AS payment_created_at

    FROM bookings b
    LEFT JOIN customers c ON b.customer_id = c.customer_id
    LEFT JOIN movies m ON b.movie_id = m.movie_id
    LEFT JOIN showtimes s ON b.showtime_id = s.showtime_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    WHERE b.booking_id = ?
    LIMIT 1
");

$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die('Booking not found.');
}

require_once 'includes/admin_header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Booking Details</h3>
        <a href="bookings.php" class="btn btn-outline">Back to Bookings</a>
    </div>

    <div class="card-body">
        <div style="max-width: 800px; margin: auto; border: 1px solid #e5e7eb; border-radius: 12px; padding: 2rem; background: #fff;">
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="margin-bottom: 0.5rem;">Ticketly Booking Record</h2>
                <p style="color: #64748b;">Booking ID: <strong>#BK-<?= htmlspecialchars($booking['booking_id']) ?></strong></p>
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Customer Name</th>
                    <td style="padding: 12px;"><?= htmlspecialchars($booking['customer_name'] ?? 'Unknown') ?></td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Customer Email</th>
                    <td style="padding: 12px;"><?= htmlspecialchars($booking['customer_email'] ?? 'N/A') ?></td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Movie</th>
                    <td style="padding: 12px;"><?= htmlspecialchars($booking['movie_title'] ?? 'Unknown Movie') ?></td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Genre</th>
                    <td style="padding: 12px;"><?= htmlspecialchars($booking['genre'] ?? 'N/A') ?></td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Language</th>
                    <td style="padding: 12px;"><?= htmlspecialchars($booking['language'] ?? 'N/A') ?></td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Duration</th>
                    <td style="padding: 12px;">
                        <?= htmlspecialchars($booking['duration'] ?? 'N/A') ?> minutes
                    </td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Screen</th>
                    <td style="padding: 12px;"><?= htmlspecialchars($booking['screen'] ?? 'N/A') ?></td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Show Date</th>
                    <td style="padding: 12px;">
                        <?= !empty($booking['show_date']) ? date('D, M d, Y', strtotime($booking['show_date'])) : 'N/A' ?>
                    </td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Show Time</th>
                    <td style="padding: 12px;">
                        <?= !empty($booking['show_time']) ? date('h:i A', strtotime($booking['show_time'])) : 'N/A' ?>
                    </td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Seat</th>
                    <td style="padding: 12px;">
                        <strong><?= htmlspecialchars($booking['seat_label']) ?></strong>
                    </td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Amount</th>
                    <td style="padding: 12px;">
                        <strong>NPR <?= number_format($booking['amount'], 2) ?></strong>
                    </td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Booking Status</th>
                    <td style="padding: 12px;">
                        <?php
                        $statusColor = '#f59e0b';

                        if ($booking['status'] === 'Confirmed') {
                            $statusColor = '#10b981';
                        } elseif ($booking['status'] === 'Cancelled') {
                            $statusColor = '#ef4444';
                        }
                        ?>

                        <span style="padding: 5px 10px; border-radius: 6px; color: white; background: <?= $statusColor ?>;">
                            <?= htmlspecialchars($booking['status']) ?>
                        </span>
                    </td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Payment Status</th>
                    <td style="padding: 12px;">
                        <?= htmlspecialchars($booking['payment_status'] ?? 'Unpaid') ?>
                    </td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Payment Method</th>
                    <td style="padding: 12px;">
                        <?= htmlspecialchars($booking['payment_method'] ?? 'N/A') ?>
                    </td>
                </tr>

                <tr>
                    <th style="text-align:left; padding: 12px; background:#f8fafc;">Booking Date</th>
                    <td style="padding: 12px;">
                        <?= !empty($booking['created_at']) ? date('M d, Y h:i A', strtotime($booking['created_at'])) : 'N/A' ?>
                    </td>
                </tr>
            </table>

            <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem;">
                <button onclick="window.print()" class="btn btn-primary">Print</button>
                <a href="bookings.php" class="btn btn-outline">Back</a>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .admin-sidebar,
    .admin-header,
    .btn,
    a {
        display: none !important;
    }

    .admin-main {
        margin-left: 0 !important;
    }

    .card {
        box-shadow: none !important;
        border: none !important;
    }
}
</style>

<?php require_once 'includes/admin_footer.php'; ?>