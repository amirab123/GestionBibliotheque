<?php
session_start();
require_once "include/config.php";

// Vérifie que c’est bien un employé connecté
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'EMPLOYE') {
    header("Location: login.php");

    exit;
}

$id_biblio = $_SESSION['id_bibliotheque'] ?? null;
$nom = $_SESSION['nom'];
$nom_biblio = $_SESSION['nom_biblio'] ?? null;


// --- Actions : accepter/refuser/terminer ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_emprunt = intval($_POST['id_emprunt']);
    $action = $_POST['action'];

    if ($action === 'En_Cours') {
        $stmt = $mysqli->prepare("UPDATE emprunts SET statut='En_Cours' WHERE id_emprunt=?");
        $stmt->bind_param("i", $id_emprunt);
        $stmt->execute();
    }
    if ($action === 'accepter') {
        $stmt = $mysqli->prepare("UPDATE emprunts SET statut='ACCEPTE' WHERE id_emprunt=?");
        $stmt->bind_param("i", $id_emprunt);
        $stmt->execute();
    }
    elseif ($action === 'refuser') {
        $stmt = $mysqli->prepare("UPDATE emprunts SET statut='REFUSE' WHERE id_emprunt=?");
        $stmt->bind_param("i", $id_emprunt);
        $stmt->execute();
    }
    elseif ($action === 'terminer') {
        $stmt = $mysqli->prepare("UPDATE emprunts SET statut='TERMINE', `emprunts`.` date_retour_reel`=NOW() WHERE id_emprunt=?");
        $stmt->bind_param("i", $id_emprunt);
        $stmt->execute();
    }
}

// --- Liste des emprunts de la bibliothèque ---
$sql = "SELECT DISTINCT `emprunts`.`id_emprunt` , u.nom, `livre`.` titre`  , `emprunts`.`  dateEmprunt` ,  `emprunts`.` date_retour_reel` , `emprunts`.`statut`  ,  
   `emprunts`.` date_retour_prevue` FROM `emprunts` JOIN 
 utilisateurs u ON `emprunts`.`emprunteur` = u.id_utilisateur JOIN `livre` ON `emprunts`.`livre` = `livre`.`id_livre` 
 JOIN inventaire i ON `emprunts`.`livre` = i.id_livre JOIN bibliotheque b ON i.id_bibliotheque = b.id_bibliotheque 
 WHERE i.id_bibliotheque = ? 
  LIMIT 100 ";

    
$stmt = $mysqli->prepare($sql);

$stmt->bind_param("i", $id_biblio);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des emprunts</title>
    <link rel="stylesheet" href="css/style.css">
</head>


 <header>

   <h2>Portail Employé</h2>
      <h3>Bienvenue     <b><?= htmlspecialchars($nom) ?></b> 
    (Bibliothèque <?= htmlspecialchars($id_biblio) ?>) | 
        |  role : <?= htmlspecialchars($_SESSION['role']) ?>  | 
        |  nom bibliotheque  : <?= htmlspecialchars($nom_biblio) ?>  |

    <a href="deconnexion.php">Déconnexion</a>
     </h3>
        <nav>
        
<a href="livre.php">Gérer l'inventaire</a>
 <a href="emprunts_employe.php">Gérer les emprunts</a> 
<a href="nouveauclient.php">Créer un nouveau client</a> 

        </nav>
        <hr>

    </header>
<body>
    <h2>Gestion des emprunts - Bibliothèque <?= htmlspecialchars($_SESSION['id_bibliotheque']) ?></h2>

    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Emprunteur</th>
            <th>Livre</th>
            <th>Date emprunt</th>
            <th>Date retour prévue</th>
            <th>Date retour réel</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_emprunt'] ?></td>
                <td><?= ($row['nom']) ?></td>
                <td><?= ($row[' titre']) ?></td>
                <td><?= ($row['  dateEmprunt']) ?></td>
                <td><?= $row[' date_retour_prevue'] ?></td>
                <td><?= $row[' date_retour_reel'] ?: '-' ?></td>
                <td><?= $row['statut'] ?></td>
                <td>
                    <?php if ($row['statut'] === 'EN_ATTENTE'): ?>
                            <form method="post" style="display:inline">
                            <input type="hidden" name="id_emprunt" value="<?= $row['id_emprunt'] ?>">
                            <button type="submit" name="action" class="btn-emprunt btn-" value="EN_COURS">✅ EN  Cours</button>
                        </form>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="id_emprunt" value="<?= $row['id_emprunt'] ?>">
                            <button type="submit" name="action" class="btn-emprunt btn-accepter" value="accepter">✅ Accepter</button>
                        </form>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="id_emprunt" value="<?= $row['id_emprunt'] ?>">
                            <button type="submit" name="action" class="btn-emprunt btn-refuser"  value="refuser">❌ Refuser</button>
                        </form>
                    <?php elseif ($row['statut'] === 'ACCEPTE' && !$row[' date_retour_reel']): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="id_emprunt" value="<?= $row['id_emprunt'] ?>">
                            <input type="text" name="date_retour_reel" placeholder="YYYY-MM-DD" required>
                            <button type="submit" name="action" value="terminer">📗 Retour confirmé</button>
                        </form>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>


<p>
    <a href="portail_employe.php" class="btn-retour"> Retour à la page employé</a>
</p>

<footer>
Nom bibliothèque : <?= htmlspecialchars($_SESSION['nom_biblio'] ?? "Inconnue") ?> 
 
    | role : <?= htmlspecialchars($_SESSION['role']) ?>
    | id bibliotheque : <?= htmlspecialchars($_SESSION['id_bibliotheque']) ?>
    <hr>
    <p>&copy; 2025 Bibliothèque</p>

</footer>
    
</body>
</html>
