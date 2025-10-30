<?php
session_start();
include_once "include/config.php";

$error = '';
$success = '';

// Vérifie que l'utilisateur est connecté et est employé
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "EMPLOYE") {
    header("Location: login.php");
    exit;
}
$nom = $_SESSION['nom'];
$id_biblio = $_SESSION['id_bibliotheque'];


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $adresse = $_POST['adresse'] ?? '';
    $role = $_POST['role'] ?? '';
    $date_inscription = $_POST['date_inscription'] ?? '';


    if (!empty($nom) && !empty($email) && !empty($mot_de_passe) && !empty($role)) {

        // Vérifie si l'utilisateur existe déjà
        $check = $mysqli->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Un utilisateur avec cet email existe déjà.";
        } else {
            // Hachage du mot de passe
            $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            // Récupération de l'id_role
            $stmt_role = $mysqli->prepare("SELECT id FROM role WHERE nom_role = ?");
            $stmt_role->bind_param("s", $role);
            $stmt_role->execute();
            $result_role = $stmt_role->get_result();
            $role_row = $result_role->fetch_assoc();
            $stmt_role->close();

            if (!$role_row) {
                $error = "Le rôle sélectionné est invalide.";
            } else {
                $id_role = $role_row['id'];

             
                $stmt = $mysqli->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, adresse, id_role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $nom, $email, $mot_de_passe_hache, $adresse, $id_role);

                if ($stmt->execute()) {
                    $id_utilisateur = $stmt->insert_id;

            
                    if ($role === 'CLIENT') {
                        
                        $date_mysql = !empty($date_inscription) ? date("Y-m-d", strtotime($date_inscription)) : date("Y-m-d");

                        $stmt2 = $mysqli->prepare("INSERT INTO emprunteurs (id_utilisateur, date_inscription ,id_bibliotheque) VALUES (?, ? ,?)");
                        $stmt2->bind_param("isi", $id_utilisateur, $date_mysql ,$id_biblio);

                        if (!$stmt2->execute()) {
                            $error = "Erreur lors de l'insertion dans emprunteurs : " . $stmt2->error;
                        }
                        $stmt2->close();
                    }

                    if (empty($error)) {
                        $success = "Utilisateur créé avec succès !";
                    }
                } else {
                    $error = "Erreur lors de l'insertion dans utilisateurs : " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $check->close();
    } else {
        $error = "Veuillez remplir tous les champs.";
    }

    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un  client </title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/book.png">
   
</head>
<body>

<h1>Créer un nouveau client</h1>

<?php 
if (!empty($error)) echo "<div class='alert alert-error'>$error</div>"; 
if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; 
?>

<form method="post" >
    Nom : <input type="text" name="nom" required>
    Email : <input type="email" name="email" required>
    Adresse : <input type="text" name="adresse" required>
    Mot de passe : <input type="password" name="mot_de_passe" required>

    Rôle : 
    <select name="role" required>
        <option value="">-- Sélectionnez un rôle --</option>
        <option value="CLIENT">Client</option>
     
    </select>

    <hr>
    Date d'inscription : <input type="date" name="date_inscription">

    <hr>
    <input type="text" value="Bibliothèque ID: <?= htmlspecialchars($id_biblio) ?>" disabled>

    <button type="submit">📥 Inscrire le client </button>
</form>
<p>
    <a href="portail_employe.php" class="btn-retour"> Retour à la page employé</a>
</p>
</body>
</html>
