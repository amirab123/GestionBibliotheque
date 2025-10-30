<?php
session_start();
include_once "include/config.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "EMPLOYE") {
    header("Location: login.php");
    exit;
}


$nom = $_SESSION['nom'] ?? "Employé";
$nom_biblio = $_SESSION['nom_biblio'] ?? "Aucune bibliothèque assignée";
$id_biblio = $_SESSION['id_bibliotheque'] ?? null;







?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail Employé</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/book.png">
</head>
<body>
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
 






<h3>Résumé de la bibliothèque <?= htmlspecialchars($id_biblio) ?></h3>

<?php
// Nombre de livres disponibles dans cette bibliothèque
$stmt = $mysqli->prepare("SELECT SUM(quantite) as total_livres FROM inventaire i WHERE i.id_bibliotheque = ? ");
$stmt->bind_param("i", $id_biblio);
$stmt->execute();
$total_livres = $stmt->get_result()->fetch_assoc()['total_livres'] ?? 0;

// Nombre d'emprunts en cours pour cette bibliothèque
$stmt = $mysqli->prepare("
   SELECT COUNT(e.id_emprunt) as total_emprunts FROM emprunts e JOIN inventaire i ON e.livre = i.id_livre WHERE
    i.id_bibliotheque = ?  AND e.statut = 'EN_COURS'
");
$stmt->bind_param("i", $id_biblio);
$stmt->execute();
$total_emprunts = $stmt->get_result()->fetch_assoc()['total_emprunts'] ?? 0;
?>

<p>Total livres disponibles : <b><?= $total_livres ?></b></p>
<p>Emprunts en cours : <b><?= $total_emprunts ?></b></p>
<?php
$message = "";

// --- Actions :  Encours/acceptercrefuser/terminer ---
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
?>


<h2>👥 Gérer les emprunteurs</h2>
<table>
<tr><th>ID</th><th>Nom</th><th>Email</th><th>Date inscription</th></tr>
<?php
  $sql ="SELECT u.id_utilisateur, u.nom, u.email, e.date_inscription 
FROM utilisateurs u JOIN emprunteurs e ON u.id_utilisateur=e.id_utilisateur  where e.id_bibliotheque = ?";
$emprunteurs = $mysqli->prepare($sql);
$emprunteurs->bind_param("i", $id_biblio);
$emprunteurs->execute();
$result = $emprunteurs->get_result();


while ($e = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$e['id_utilisateur']}</td>
            <td>{$e['nom']}</td>
            <td>{$e['email']}</td>
            <td>{$e['date_inscription']}</td>
          </tr>";
}
?>
</table>



<h2>📊 Emprunts en cours</h2>
<table>
<tr><th>ID Emprunt</th><th>Client</th><th>Livre</th><th>Date Emprunt</th><th>Date Retour prévue</th>  <th>statut</th> </tr>
<?php
$emprunt = $mysqli->query("SELECT `emprunts`.`id_emprunt` , `livre`.` titre` , u.nom ,`emprunts`.`  dateEmprunt` ,`emprunts`.` date_retour_prevue` , `emprunts`.`statut`  FROM `emprunts` JOIN utilisateurs u ON `emprunts`.`emprunteur`= u.id_utilisateur JOIN `livre` ON `emprunts`.`livre`=`livre`.`id_livre` WHERE `emprunts`.`statut`= 'EN_Cours' ;");
while($e = $emprunt->fetch_assoc()) {
    echo "<tr>
            <td>{$e['id_emprunt']}</td>
            <td>{$e['nom']}</td>
            <td>{$e[' titre']}</td>
            <td>{$e['  dateEmprunt']}</td>
 
            <td>{$e[' date_retour_prevue']}</td>
              <td>{$e['statut']}</td>

          </tr>";
}
?>
</table>
<h2>📊 Emprunts EN ATTENTE</h2>
<table>
<tr><th>ID Emprunt</th><th>Client</th><th>Livre</th><th>Date Emprunt</th><th>Date Retour prévue</th>  <th>statut</th> </tr>
<?php
$emprunt = $mysqli->query("SELECT `emprunts`.`id_emprunt` , `livre`.` titre` , u.nom ,`emprunts`.`  dateEmprunt` ,`emprunts`.` date_retour_prevue` , `emprunts`.`statut`  FROM `emprunts` JOIN utilisateurs u ON `emprunts`.`emprunteur`= u.id_utilisateur JOIN `livre` ON `emprunts`.`livre`=`livre`.`id_livre` WHERE `emprunts`.`statut`= 'EN_ATTENTE' ;");
while($e = $emprunt->fetch_assoc()) {
    echo "<tr>
            <td>{$e['id_emprunt']}</td>
            <td>{$e['nom']}</td>
            <td>{$e[' titre']}</td>
            <td>{$e['  dateEmprunt']}</td>
 
            <td>{$e[' date_retour_prevue']}</td>
              <td>{$e['statut']}</td>

          </tr>";
}
?>
</table>
<h2>📊 Emprunts TERMINE</h2>
<table>
<tr><th>ID Emprunt</th><th>Client</th><th>Livre</th><th>Date Emprunt</th><th>Date Retour prévue</th>  <th>statut</th> 
<?php
$emprunt = $mysqli->query("SELECT `emprunts`.`id_emprunt` , `livre`.` titre` , u.nom ,`emprunts`.`  dateEmprunt` ,`emprunts`.` date_retour_prevue` , `emprunts`.`statut`  FROM `emprunts` JOIN utilisateurs u ON `emprunts`.`emprunteur`= u.id_utilisateur JOIN `livre` ON `emprunts`.`livre`=`livre`.`id_livre` WHERE `emprunts`.`statut`= 'TERMINE' ;");
while($e = $emprunt->fetch_assoc()) {
    echo "<tr>
            <td>{$e['id_emprunt']}</td>
            <td>{$e['nom']}</td>
            <td>{$e[' titre']}</td>
            <td>{$e['  dateEmprunt']}</td>
 
            <td>{$e[' date_retour_prevue']}</td>
              <td>{$e['statut']}</td>

          </tr>";
}
?>
</table>



</body>


<footer>
Nom bibliothèque : <?= htmlspecialchars($_SESSION['nom_biblio'] ?? "Inconnue") ?> 
 
    | role : <?= htmlspecialchars($_SESSION['role']) ?>
    | id bibliotheque : <?= htmlspecialchars($_SESSION['id_bibliotheque']) ?>
    <hr>
    <p>&copy; 2025 Bibliothèque</p>

</footer>

</html>
