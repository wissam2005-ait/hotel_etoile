<?php
session_start();
require_once 'database.php';
require_once 'notif_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['action'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $action         = $_POST['action'];

    $stmt = $conn->prepare("
        SELECT r.*, u.id AS client_id, u.nom, u.prenom,
               c.numero AS chambre_num, c.type AS chambre_type, c.id AS chambre_id
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN chambres c ON r.chambre_id = c.id
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Réservation introuvable.'];
        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'confirmer') {
        $conn->query("UPDATE reservations SET statut='confirmee' WHERE id=$reservation_id");
        $conn->query("UPDATE chambres SET statut='occupee' WHERE id={$res['chambre_id']}");

        envoyerNotification(
            $conn, $res['client_id'],
            "✅ Réservation Confirmée !",
            "Bonne nouvelle ! Votre réservation pour la chambre {$res['chambre_num']} ({$res['chambre_type']}) "
            . "du {$res['date_debut']} au {$res['date_fin']} a été <strong>confirmée</strong>. Merci de votre confiance et bienvenue à l'Hôtel Etoile !",
            'success'
        );
        $_SESSION['flash'] = ['type' => 'success', 'msg' => "✅ Réservation #{$reservation_id} confirmée. Le client a été notifié."];

    } elseif ($action === 'annuler') {
        $raison = htmlspecialchars(trim($_POST['raison'] ?? ''));

        $conn->query("UPDATE reservations SET statut='annulee' WHERE id=$reservation_id");
        $conn->query("UPDATE chambres SET statut='disponible' WHERE id={$res['chambre_id']}");

        $msg = "Votre réservation pour la chambre {$res['chambre_num']} ({$res['chambre_type']}) "
             . "du {$res['date_debut']} au {$res['date_fin']} a été <strong>annulée</strong>.";
        if ($raison) $msg .= "<br>Raison : " . $raison;

        envoyerNotification($conn, $res['client_id'], "❌ Réservation Annulée", $msg, 'danger');
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => "❌ Réservation #{$reservation_id} annulée. Le client a été notifié."];
    }
}

// Redirect back to referring page
$referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
if (strpos($referer, 'reservation.php') !== false) {
    header('Location: reservation.php');
} else {
    header('Location: dashboard.php');
}
exit;
?>
