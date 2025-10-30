<?php
session_start();
include_once "include/config.php"; 

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';



    $sql = "SELECT u.id_utilisateur, u.nom, u.email, u.mot_de_passe, r.nom_role , e.id_bibliotheque as  id_biblio_employe, b.nom_bibliotheque 
    AS  nom_biblio_employe , be.nom_bibliotheque AS nom_biblio_emprunteur , be.id_bibliotheque as id_biblio_emprunteur
     FROM utilisateurs u INNER JOIN role r ON u.id_role = r.id LEFT JOIN employe e ON u.id_utilisateur = e.id_utilisateur LEFT JOIN bibliotheque b ON e.id_bibliotheque = b.id_bibliotheque LEFT JOIN emprunteurs em ON u.id_utilisateur=em.id_utilisateur LEFT JOIN bibliotheque be ON be.id_bibliotheque=em.id_bibliotheque WHERE u.email = ?";
        
 

    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Vérifie le mot de passe
            if (password_verify($mot_de_passe, $user['mot_de_passe'])) {
                
             
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['role'] = $user['nom_role'];
              

                if ($user['nom_role'] === 'EMPLOYE') {
                $_SESSION['id_bibliotheque'] = $user['id_biblio_employe'] ?? null;
            $_SESSION['nom_biblio'] = $user['nom_biblio_employe'] ?? "Aucune bibliothèque assignée";
                      header("Location: portail_employe.php");
                    exit;
                }

            

        elseif ($user['nom_role'] === 'CLIENT') {
    // Cas emprunteur
    $_SESSION['id_bibliotheque'] = $user['id_biblio_emprunteur'] ?? null;
    $_SESSION['nom_biblio'] = $user['nom_biblio_emprunteur'] ?? "Aucune bibliothèque assignée";
    
    header("Location: portail_client.php");
    exit;
}
              else {



    $_SESSION['message'] = "Rôle inconnu pour l'utilisateur.";
    header("Location: login.php");
    exit;

            
                }

            } else {
                $message = "⚠️ Mot de passe incorrect";
            }
        } else {
            $message = "⚠️ Aucun utilisateur trouvé avec cet email";
        }

        $stmt->close();
    } else {
        $message = "❌ Erreur SQL : " . $mysqli->error;
    }
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="icon" type="image/png" href="img/book.png">
</head>
<body>
    <div class="login-box">
  
        <h2>Connexion</h2>
        
        <form method="POST" action="">
            
            <label>Email :</label>
            <input type="email" name="email" required>
            
            <label>Mot de passe :</label>
            <input type="password" name="mot_de_passe" required>
            
            <button type="submit">Se connecter</button>
        </form>
        <?php if ($message): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>

    </div>

  
<div class="back-home-container">
            <a href="index.php" class="back-home">🏠 Retour à l'accueil</a>
</div>

    
</body>
</html>
