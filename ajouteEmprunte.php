<?php
session_start();
require_once "include/config.php";

// Vérifier que l'utilisateur est connecté et est un client
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "CLIENT") {
    die("Accès interdit. Veuillez vous connecter en tant que client.");
   
    header("Location: login.php");
    exit;
}

$nom = $_SESSION['nom'] ?? "Client";
$id_client = $_SESSION['user_id'];

$nom_biblio = $_SESSION['nom_biblio'] ?? "Non rattaché";
$id_biblio = $_SESSION['id_bibliotheque'] ?? null;

// Initialiser les dates
$date_emprunt = date('Y-m-d');
$date_retour_prev = date('Y-m-d', strtotime('+14 days'));

$message = "";

// Récupérer les livres disponibles dans la bibliothèque
$livres = $mysqli->query("SELECT `livre`.`id_livre`, `livre`.` titre`, `livre`.` auteur` , i.quantite FROM `livre` JOIN inventaire i ON `livre`.`id_livre` = i.id_livre WHERE i.id_bibliotheque = $id_biblio AND i.quantite > 0 ORDER BY `livre`.` titre` ASC");
   


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_livre = (int)$_POST['id_livre'];
    $id_emprunteur = $id_client;

    $dateEmprunt = date('Y-m-d');
    $dateRetourPrevue = date('Y-m-d', strtotime('+14 days'));
    $dateRetourReel = NULL;
    $statut = "EN_ATTENTE";

    // Vérifier si le client a moins de 2 emprunts en cours
    $stmt_check = $mysqli->prepare("
      
SELECT COUNT(*) AS nb FROM `emprunts` WHERE `emprunts`.`emprunteur` = ? AND `emprunts`.` date_retour_reel` IS NULL AND `emprunts`.`statut`= 'EN_ATTENTE';

    ");
    $stmt_check->bind_param("i", $id_emprunteur);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $nbEmprunts = $result_check->fetch_assoc()['nb'] ?? 0;
    $stmt_check->close();

    if ($nbEmprunts >= 2) {
        $message = "❌ Vous avez déjà 2 emprunts en cours.";
    } else {
        // Vérifier la quantité du livre
        $stmt_quantite = $mysqli->prepare("
            SELECT quantite 
            FROM inventaire 
            WHERE id_livre = ? AND id_bibliotheque = ?
        ");
        $stmt_quantite->bind_param("ii", $id_livre, $id_biblio);
        $stmt_quantite->execute();
        $result_quantite = $stmt_quantite->get_result();
        $quantite = $result_quantite->fetch_assoc()['quantite'] ?? 0;
        $stmt_quantite->close();

        if ($quantite > 0) {
            // Insérer l'emprunt
            $stmt = $mysqli->prepare("
                INSERT INTO emprunts
                (emprunteur, livre, dateEmprunt, date_retour_prevue, date_retour_reel, statut)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iissss", $id_emprunteur, $id_livre, $dateEmprunt, $dateRetourPrevue, $dateRetourReel, $statut);
            if ($stmt->execute()) {
                $message = "✅ Emprunt effectué avec succès !";

                // Mettre à jour l'inventaire
                $stmt_update = $mysqli->prepare("
                    UPDATE inventaire 
                    SET quantite = quantite - 1 
                    WHERE id_livre = ? AND id_bibliotheque = ?
                ");
                $stmt_update->bind_param("ii", $id_livre, $id_biblio);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                $message = "❌ Erreur : " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "❌ Livre indisponible dans votre bibliothèque.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouter un emprunt</title>
<link rel="icon" type="image/png" href="img/book.png">
<link rel="stylesheet" href="css/style.css">

</head>
<body>

<div class="container">
<h2>Ajouter un emprunt</h2>

<?php if ($message): ?>
    <p class="message <?= strpos($message,'✅')!==false?'success':'error' ?>"><?= $message ?></p>
<?php endif; ?>

<form action="" method="post">
    <input type="hidden" name="id_client" value="<?= $id_client ?>">

    <label for="id_livre">Choisir un livre :</label>
    <select name="id_livre" id="id_livre" required>
        <option value="">-- Sélectionnez un livre --</option>
        <?php while($l = $livres->fetch_assoc()): ?>
            <option value="<?= $l['id_livre'] ?>"><?= htmlspecialchars($l[' titre']) ?> (<?= $l['quantite'] ?> dispo)</option>
        <?php endwhile; ?>
    </select>

    <label for="dateEmprunt">Date d'emprunt :</label>
    <input type="date" name="dateEmprunt" id="dateEmprunt" value="<?= $date_emprunt ?>" readonly>

    <label for="date_retour">Date prévue de retour :</label>
    <input type="date" name="date_retour" id="date_retour" value="<?= $date_retour_prev ?>" readonly>

    <button type="submit">Ajouter l'emprunt</button>
</form>
</div>
<p>
    <a href="portail_client.php" class="btn-retour"> Retour à la page  Portail client </a>
</p>
</body>
</html>
