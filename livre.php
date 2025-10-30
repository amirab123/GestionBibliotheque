<?php
session_start();

include_once "include/config.php"; // $mysqli = connexion MySQLi

// Vérifier que l'utilisateur est un employé
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'EMPLOYE') {
    header("Location: login.php");
    exit;
}
$nom = $_SESSION['nom'];
$id_biblio = $_SESSION['id_bibliotheque'];


$nom_biblio = $_SESSION['nom_biblio'] ?? null;

$message = "";

if (isset($_POST['ajouter'])) {
    $titre = trim($_POST['titre']);
    $auteur = trim($_POST['auteur']);
    $date_pub = $_POST['date_publication'];
    $nb_pages = (int)$_POST['nombrepages'];

    if ($titre && $auteur && $date_pub && $nb_pages > 0) {

        // Vérifier si le livre existe déjà
        $check = $mysqli->prepare("SELECT id_livre FROM `livre`  WHERE `livre`.` titre` = ? AND `livre`.` auteur` = ?");
        $check->bind_param("ss", $titre, $auteur);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Ce livre existe déjà !";
        } else {
            // Ajouter le livre
            $stmt = $mysqli->prepare("INSERT INTO `livre` (`livre`.` titre`, `livre`.` auteur`, `livre`.` date_publication` , `livre`.` nombrepages` ) VALUES ( ?, ?, ?, ? )");
            $stmt->bind_param("sssi", $titre, $auteur, $date_pub, $nb_pages);
            $stmt->execute();
            $message = "Livre ajouté avec succès !";
        }
    } else {
        $message = "Tous les champs doivent être remplis correctement.";
    }
}



// Supprimer un livre
if (isset($_POST['supprimer'])) {
 $id_livre = (int)$_POST['id_livre'];

    if ($id_livre > 0) {
       
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS nb FROM inventaire WHERE id_livre = ?");
        $stmt->bind_param("i", $id_livre);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['nb'] > 0) {
            // Le livre est dans l'inventaire → on bloque la suppression
            $message = "Impossible de supprimer ce livre : il est encore présent dans l'inventaire.";
        } else {
            // 2️⃣ Supprimer le livre
            $stmt = $mysqli->prepare("DELETE FROM livre WHERE id_livre = ? LIMIT 1");
            $stmt->bind_param("i", $id_livre);

            if ($stmt->execute()) {
                $message = "Livre supprimé avec succès !";
            } else {
                $message = "Erreur lors de la suppression : " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $message = "ID de livre invalide.";
    }
}

// Affichage du message
if ($message) {
    echo "<p>$message</p>";
}

// Vérifier si le formulaire "Modifier" a été soumis
if(isset($_POST['modifier'])) {
    $id_livre = (int)$_POST['id_livre'];
    $titre = trim($_POST['titre']);
    $auteur = trim($_POST['auteur']);
    $date_pub = $_POST['date_publication'];
    $nb_pages = (int)$_POST['nombre_pages'];

    // Validation simple
    if ($id_livre > 0 && $titre && $auteur && $date_pub && $nb_pages > 0) {
        $stmt = $mysqli->prepare("UPDATE `livre` SET `livre`.` titre` =?, `livre`.` auteur`=?, `livre`.` date_publication` =?, `livre`.`nombre_pages` =? WHERE `id_livre`=?");
        $stmt->bind_param("sssii", $titre, $auteur, $date_pub, $nb_pages, $id_livre);
        $stmt->execute();
        $message = "Livre modifié avec succès !";
    } else {
        $message = "Tous les champs doivent être remplis correctement pour la modification.";
    }
}

// Récupérer tous les livres pour l'affichage
$stmt = $mysqli->prepare("
SELECT i.*, `livre`.` auteur` , `livre`.` titre` ,`livre`.` date_publication` , `livre`.`nombre_pages` 
 , i.id_bibliotheque FROM inventaire i INNER JOIN `livre` ON i.id_livre = `livre`.`id_livre` WHERE i.id_bibliotheque = ? ORDER BY `livre`.`id_livre` DESC");

$stmt->bind_param("i", $_SESSION['id_bibliotheque']); // i = entier
$stmt->execute();
$result = $stmt->get_result();
$livres = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des livres</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/book.png">
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
<h1>Gestion des livres - <?= htmlspecialchars($_SESSION['nom_biblio']) ?></h1>

<a href="ajouterlivre.php" class="btn btn-ajouter">Ajouter un livre</a>










<h2>Supprimer un livre</h2>
<form method="post">
    <label>ID du livre à supprimer :</label>
    <input type="number" name="id_livre" min="1" required>
    <button type="submit" name="supprimer">Supprimer</button>
</form>

<h2>Liste des livres</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Titre</th>
        <th>Auteur</th>
        <th>Date publication</th>
        <th>Nombre de pages</th>
    </tr>
    <?php foreach($livres as $livre): ?>
    <tr>
        <td><?= $livre['id_livre'] ?></td>
        <td><?= htmlspecialchars($livre[' titre']) ?></td>
        <td><?= htmlspecialchars($livre[' auteur']) ?></td>
        <td><?= $livre[' date_publication'] ?></td>
        <td><?= $livre['nombre_pages'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<h2>Liste des livres</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Titre</th>
        <th>Auteur</th>
        <th>Date publication</th>
        <th>Nombre de pages</th>
        <th>Actions</th>
    </tr>
    <?php foreach($livres as $livre): ?>
    <tr>
        <form method="post">
            <td>
                <?= $livre['id_livre'] ?>
                <input type="hidden" name="id_livre" value="<?= $livre['id_livre'] ?>">
            </td>
            <td><input type="text" name="titre" value="<?= htmlspecialchars($livre[' titre']) ?>" required></td>
            <td><input type="text" name="auteur" value="<?= htmlspecialchars($livre[' auteur']) ?>" required></td>
            <td><input type="text" name="date_publication" value="<?= $livre[' date_publication'] ?>" required></td>
            <td><input type="number" name="nombre_pages" value="<?= $livre['nombre_pages'] ?>" min="1" required></td>
            <td>

        
               <button type="submit" name="modifier" class="btn btn-modifier">Modifier</button>
            
                <button type="submit" name="supprimer"    class  = " btn btn-supprimer" onclick="return confirm('Voulez-vous vraiment supprimer ce livre ?');">Supprimer</button>
            </td>
        </form>
    </tr>
    <?php endforeach; ?>
</table>

<p>
    <a href="portail_employe.php" class="btn-retour"> Retour à la page employé</a>
</p>
</body>

<footer>


Nom bibliothèque : <?= htmlspecialchars($_SESSION['nom_biblio'] ?? "Inconnue") ?> 
 
    | role : <?= htmlspecialchars($_SESSION['role']) ?>
    | id bibliotheque : <?= htmlspecialchars($_SESSION['id_bibliotheque']) ?>
    <hr>
    <p>&copy; 2025 Bibliothèque</p>

</footer>

</html>
