<?php
session_start();
include_once "include/config.php"; 
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "EMPLOYE") {
    header("Location: login.php");
    exit;
}



$nom = $_SESSION['nom'];


$id_bibliotheque = $_SESSION['id_bibliotheque'] ?? null;


$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter_livre'])) {
        $titre = trim($_POST['titre']);
        $auteur = trim($_POST['auteur']);
        $date_publication = $_POST['date_publication'];
        $nombrepages = (int)$_POST['nombrepages'];
        $quantite = (int)$_POST['quantite'];

        if ($id_bibliotheque) {
            // 1. Insertion du livre
        $stmt = $mysqli->prepare("INSERT INTO `livre` (`livre`.` titre`, `livre`.` auteur`, `livre`.` date_publication` , `livre`.`  nombrepages`  ) 
                     VALUES ( ? , ? , ? , ?  ) 
                     ");
            $stmt->bind_param("sssi", $titre, $auteur, $date_publication, $nombrepages);
            $stmt->execute();

            $id_livre = $stmt->insert_id;

            // 2. Insertion dans inventaire
            $stmt = $mysqli->prepare("
                INSERT INTO inventaire (id_livre, id_bibliotheque, quantite)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iii", $id_livre, $id_bibliotheque, $quantite);
            $stmt->execute();

            $message = "📚 Livre ajouté avec succès dans votre bibliothèque !";
        } else {
            $message = "❌ Erreur : aucune bibliothèque associée à cet employé.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un livre</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/book.png">

</head>
<body>
    <header>
        <h2>Portail Employé</h2>
        <h3>Bienvenue      Bonjour <b><?= htmlspecialchars($nom) ?></b>
    (Bibliothèque <?= htmlspecialchars($id_bibliotheque) ?>) |
    <a href="deconnexion.php">Déconnexion</a>
     </h3>
        <nav>
<a href="livre.php">Gérer l'inventaire</a>
    <a href="emprunts_employe.php">Gérer les emprunts</a>
    <a href="nouveauclient.php">Créer un nouveau client</a>
        </nav>
        <hr>
    </header>


    <h2>Ajouter un nouveau livre</h2>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="titre">Titre :</label>
            <input type="text" name="titre" id="titre" required>
        </div>

        <div class="form-group">
            <label for="auteur">Auteur :</label>
            <input type="text" name="auteur" id="auteur" required>
        </div>

        <div class="form-group">
            <label for="date_publication">Date de publication :</label>
            <input type="date" name="date_publication" id="date_publication" required>
        </div>

        <div class="form-group">
            <label for="nombrepages">Nombre de pages :</label>
            <input type="number" name="nombrepages" id="nombrepages" min="1" required>
        </div>

        <div class="form-group">
            <label for="quantite">Quantité   :</label>
            <input type="number" name="quantite" id="quantite" min="1" required>
        </div>

        <button type="submit" name="ajouter_livre">📚 Ajouter le livre</button>
    </form>

<p>
    <a href="portail_employe.php" class="btn-retour"> Retour à la page employé</a>
</p>



</body>
</html>
