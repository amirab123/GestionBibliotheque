<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Gestion Bibliothèque</title>
    <link rel="stylesheet" href="css/index.css">
  <link rel="icon" type="image/png" href="img/book.png">
</head>

<body>
    <h1>📚 Bienvenue dans la Gestion de la Bibliothèque</h1>

  
    <section class="intro">
        <h2>Découvrez notre bibliothèque en ligne</h2>
        <p>
            Accédez facilement à votre espace selon votre rôle : <br>

            <br>📖 Clients pour gérer vos emprunts .  
            <br>👨‍💼 Employés pour  gérer les emprunteurs .
        </p>

        <a href="login.php" class="btn">Se connecter</a>
        <a href="portail_employe.php" class="btn">Portail Employé</a>
        <a href="portail_client.php" class="btn">Portail Client</a>
    </section>





      <section class="cards">
        <div class="card">
            <h3>📚 Livres</h3>
            <p>Accédez à tous les livres disponibles dans notre bibliothèque en ligne.</p>
        </div>
        <div class="card">
            <h3>📝 Emprunts</h3>
            <p>Consultez vos emprunts en cours . <br> 

                .</p>
        </div>
        <div class="card">
            <h3>👥 Utilisateurs</h3>
            <p>Les employés peuvent gérer les membres .</p>
        </div>
    </section>


  

     <div class="slideshow-container">

<div class="mySlides fade">
  <div class="numbertext"></div>
  <img src="img/book-library.jpg"  style="width:100%">

</div>

<div class="mySlides fade">
  <div class="numbertext"></div>
  <img src="img/livre2.jpg"  style="width:100%">

</div>

<div class="mySlides fade">
  <div class="numbertext"></div>
  <img src="img/livre.jpg" style="width:100%">

</div>
<div class="mySlides fade">
  <div class="numbertext"></div>
  <img src="img/literature-composition.jpg" style="width:100%">

</div>

<a class="prev" onclick="plusSlides(-1)">❮</a>
<a class="next" onclick="plusSlides(1)">❯</a>

</div>
<br>

<div style="text-align:center">
  <span class="dot" onclick="currentSlide(1)"></span> 
  <span class="dot" onclick="currentSlide(2)"></span> 
  <span class="dot" onclick="currentSlide(3)"></span> 
</div>

<script>
let slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
  showSlides(slideIndex += n);
}

function currentSlide(n) {
  showSlides(slideIndex = n);
}

function showSlides(n) {
  let i;
  let slides = document.getElementsByClassName("mySlides");
  let dots = document.getElementsByClassName("dot");
  if (n > slides.length) {slideIndex = 1}    
  if (n < 1) {slideIndex = slides.length}
  for (i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";  
  }
  for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" active", "");
  }
  slides[slideIndex-1].style.display = "block";  
  dots[slideIndex-1].className += " active";
}
</script>


      <footer>
        <p>&copy; <?= date("Y") ?>  Gestion Bibliothèque | Tous droits réservés  </p>
    </footer>

     
</body>

</html>
