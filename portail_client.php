<?php
session_start();

include_once "include/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "CLIENT") {
   
     header("Location: login.php");
    exit;

}
$id_emprunteur = $_SESSION['user_id'];
$nom = $_SESSION['nom'] ?? "Client";

$nom_biblio = $_SESSION['nom_biblio'] ?? "Non rattaché";
$id_biblio = $_SESSION['id_bibliotheque'] ?? null;





$message = "";



?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Emprunteur</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/book.png">

</head>
<body>
    <header>
 

<h2>Portail Client</h2>
   <h3>  Bienvenue, <?php echo $_SESSION['nom'] ?? "nouveau"; ?> (<?php echo $_SESSION['user_id'] ?? ""; ?>) 
    | 
    role : <?php echo $_SESSION['role'] ?? "Inconnu"; ?> | | 
    Nom bibliothèque : <?php echo $_SESSION['nom_biblio'] ?? "Non rattaché"; ?>
        <a href="deconnexion.php">Déconnexion</a>
</h3>

        <nav>
            <a href="?action=liste_livres">Livres disponibles</a>
            <a href="?action=mes_emprunts">Mes emprunts</a>
        </nav>
        <hr>
    </header>

    <?php if ($message): ?>
        <p style="color: green; font-weight: bold;"><?= $message ?></p>
    <?php endif; ?>
<a href="ajouteEmprunte.php" class="btn btn-ajouter">Ajouter un emprunte</a>



    <main>
    <?php
    // 1. Liste des livres disponibles
 if (isset($_GET['action']) && $_GET['action'] === 'liste_livres') {
 
            

           $sql = (" SELECT `livre`.`id_livre` , `livre`.` titre` , `livre`.` auteur` , i.quantite FROM `livre` JOIN inventaire i ON `livre`.`id_livre` = i.id_livre WHERE i.id_bibliotheque = ? AND i.quantite > 0 ORDER BY `livre`.` titre` ASC " );

    $stmt =  $mysqli->prepare($sql);
    if (!$stmt) {
    die("Erreur SQL : " . $mysqli->error);
}
    $stmt->bind_param("i", $id_biblio);
    
//
  $stmt->execute();
    $result = $stmt->get_result();

    echo "<h2>Livres disponibles</h2>";

    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; margin:10px 0;'>";
        echo "<tr><th>ID</th><th>Titre</th><th>Auteur</th><th>Quantité</th><th>Action</th></tr>";

        while ($livre = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($livre['id_livre']) . "</td>";
            echo "<td>" . htmlspecialchars($livre[' titre']) . "</td>";
            echo "<td>" . htmlspecialchars($livre[' auteur']) . "</td>";
            echo "<td>" . htmlspecialchars($livre['quantite']) . "</td>";
         echo "<td><a href='?action=emprunter&id_livre=" . $livre['id_livre'] . "' class='btn-emprunter'>📖 Emprunter</a></td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>Aucun livre disponible dans cette bibliothèque.</p>";
    }

    $stmt->close();
}



    // 2. Faire un emprunt (max 2)
    if (isset($_GET['action']) && $_GET['action'] === 'emprunter' && isset($_GET['id_livre'])    ) {
        $id_livre = (int)$_GET['id_livre'];

    $id_emprunteur = $_SESSION['user_id'] ?? null;

    if (!$id_emprunteur) {
        echo "<p style='color:red;'>❌ Erreur : utilisateur non connecté.</p>";
        exit;
    }
 echo "<h2>📖 Emprunter un livre</h2>";
 echo "<p>Utilisateur : " . htmlspecialchars($nom) . " (ID: " . htmlspecialchars($id_emprunteur) . ")</p>";

  
        $sql = "
        SELECT COUNT(*)  nb  FROM `emprunts` WHERE `emprunts`.`emprunteur` = ? AND `emprunts`.` date_retour_reel` IS NULl and `emprunts`.`statut`='EN_ATTENTE' ";
        $stmt = $mysqli->prepare($sql);
     $stmt->bind_param("i", $id_emprunteur);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$nbEmprunts = $row['nb']; 
if ($nbEmprunts >= 2) {
    echo "<p style='color:red;'>❌ Vous avez déjà 2 emprunts en cours.</p>";
} else {

    $sql = "SELECT quantite FROM inventaire WHERE id_livre = ? AND id_bibliotheque = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $id_livre, $id_biblio);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $quantite = $row['quantite'] ?? 0;

    if ($quantite > 0 ) {
        // 2. Insérer l'emprunt
        $dateEmprunt = date('Y-m-d');
$dateRetourPrevue = date('Y-m-d', strtotime('+14 days'));
$dateRetourReel = NULL;
$statut = "EN_COURS";

$id_emprunteur = $_SESSION['user_id'];
   $stmt = $mysqli->prepare ("INSERT INTO `emprunts` 
   (`  dateEmprunt`, ` date_retour_prevue`, ` date_retour_reel`, `livre`, `emprunteur`, `statut`) VALUES (?, ?, null , ?, ?, ?)");
   $stmt->bind_param("ssiis",$dateEmprunt, $dateRetourPrevue,$id_livre,$id_emprunteur,$statut); 
        $stmt->execute();
        $sql = "UPDATE inventaire SET quantite = quantite - 1 WHERE id_livre = ? AND id_bibliotheque = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $id_livre, $id_biblio);
        $stmt->execute();

        echo "<p style='color:green;'>✅ Emprunt effectué avec succès !</p>";
    } else {
        echo "<p style='color:red;'>❌ Livre indisponible dans votre bibliothèque.</p>";
    }
}
        $stmt->close();
    }

    // 3. Liste des emprunts en cours
    if (isset($_GET['action']) && $_GET['action'] === 'mes_emprunts' && !isset($_GET['emprunteur']))  {
      
    

 
      
             $sql = " SELECT `emprunts`.`id_emprunt` , u.nom, `livre`.` titre`    ,  `emprunts`.`  dateEmprunt`    ,  `emprunts`.` date_retour_prevue` FROM  `emprunts` 
          
             JOIN utilisateurs u ON `emprunts`.`emprunteur` = u.id_utilisateur JOIN 
             `livre` ON `emprunts`.`livre` = `livre`.`id_livre` WHERE `emprunts`.`emprunteur` =  ?  
              and `emprunts`.` date_retour_reel`IS NULl and `emprunts`.`statut`='EN_cours' 
          ORDER BY `emprunts`.`  dateEmprunt`   DESC LIMIT 100 ";
          $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
    die("Erreur SQL : " . $mysqli->error);
}

$stmt->bind_param("i", $id_emprunteur);
$stmt->execute();
$result = $stmt->get_result();
   

 echo "<h2>📖 Mes emprunts en cours</h2>";
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; margin:10px 0;'>";
        echo "<tr><th>ID Emprunt</th><th>Livre</th><th>Date Emprunt</th><th>Date Retour prévue</th><th>Action</th></tr>";
        while ($emprunt = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($emprunt['id_emprunt']) . "</td>";
            echo "<td>" . htmlspecialchars($emprunt[' titre']) . "</td>";
            echo "<td>" . htmlspecialchars($emprunt['  dateEmprunt']) . "</td>";
            echo "<td>" . htmlspecialchars($emprunt[' date_retour_prevue']) . "</td>";
            echo "<td><a href='?action=retourner&id_emprunt=" . $emprunt['id_emprunt'] . "'>🔄 Retourner</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Vous n'avez aucun emprunt en cours.</p>";
    }

    $stmt->close();
}
    ?>







    </main>
  <footer>
  Nom bibliothèque : <?php echo $_SESSION['nom_biblio'] ?? "Non rattaché"; ?>
 
    | role : <?= htmlspecialchars($_SESSION['role']) ?>
    | id bibliotheque : <?= htmlspecialchars($_SESSION['id_bibliotheque']) ?>
    <hr>
    <p>&copy; 2025 Bibliothèque</p>

</footer>
</body>
</html>
