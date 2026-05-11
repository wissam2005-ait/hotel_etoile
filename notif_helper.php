<?php
function envoyerNotification($conn, $user_id, $titre, $message, $type = 'info') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, titre, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $titre, $message, $type);
    $stmt->execute();
    $stmt->close();
}

function compterNotificationsNonLues($conn, $user_id) {
    $r = $conn->query("SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND lu=0");
    return $r->fetch_row()[0];
}
?>
