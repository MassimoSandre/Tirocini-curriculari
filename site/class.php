<?php 
    session_start();

    // controllo se l'utente ha cliccato sul tasto per il logout, nel caso provvedo alla distruzione della sessione
    if($_GET && isset($_GET["logout"])) {
        if($_GET["logout"] == 1) {
            $_SESSION = array();

            session_destroy();
        }
    }

    // controllo che l'utente sia loggato
    if(!isset($_SESSION["loggedin"]) || !$_SESSION["loggedin"]) {
        header("Location: index.php");
        exit();
    }

    // controllo che l'untete abbia specificato una classe da visualizzare, altrimenti lo rispedisco alla home
    if(!isset($_GET['class'])) {
        header("Location: index.php");
        exit();
    }

    $class = $_GET['class'];

    // controllo che l'utente abbia i privilegi per accedere a questa pagina: Amministratore, Docente
    include_once("config.php");
    $con = new mysqli($hostname, $username, $password, $database);

    if(mysqli_connect_errno()) {
        echo "Errore di connessione database: " . $con->connect_error;
        exit();
    }
    
    $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];

    $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

    foreach($ris as $riga) {
        if($riga["tipo"]!="Docente" && $riga["tipo"]!="Amministratore") {
            // se l'utente non è ne un docente ne un amministratore, lo rimando alla home, non può accedere
            header("Location: index.php");
            exit();
        }
        
        if($riga["tipo"]=="Docente") {
            // se l'utente è un docente, mi assicuro che possa accedere a questa classe: deve essere il sovrintendente dell'indirizzo
            $sql = "SELECT * FROM Classi C INNER JOIN Indirizzi I ON C.indirizzo = I.sigla WHERE C.ID_classe='$class' AND I.sovrintendente = '".$_SESSION["userid"]."'";
            $ris = $con->query($sql) or die ("Errore query verifica sovrintedente: " . $con->error);
            if($ris->num_rows == 0) {
                // Il docente non è il sovrintendente dell'indirizzo della classe, lo rimando alla home
                header("Location: index.php");
                exit();
            }
        }
    }
    
    $con->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/png" href="icon.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classe</title>
</head>
<body>

    <ul class="navbar">
        <li class="left"><a href="profile.php">Home</a></li>
        <?php
            // creo la navbar, diversa in base al tipo di utente
            if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
                switch($_SESSION["privilegi"]) {
                    case "Amministratore":

                        echo "
                            <li class='left dropdown'>
                                <a href='#'>Gestione Utenti</a>
                                <div class='dropdown-content'>
                                    <a href='usermanagement.php'>Visualizza Utenti</a>
                                    <a href='userinsertion.php'>Inserisci Utente</a>
                                </div>
                            </li>
                            
                            <li class='left dropdown'>
                                <a href='#'>Gestione Aziende</a>
                                <div class='dropdown-content'>
                                    <a href='sitemanagement.php'>Visualizza Aziende</a>
                                    <a href='siteinsertion.php'>Inserisci Azienda</a>
                                </div>
                            </li>
                            
                            <li class='left '><a href='coursemanagement.php'>Gestione Indirizzi</a></li>

                            <li class='left dropdown'>
                                <a href='#' class='active'>Gestione Classi</a>
                                <div class='dropdown-content'>
                                    <a class='active' href='classmanagement.php'>Visualizza Classi</a>
                                    <a href='classinsertion.php'>Inserisci Classe</a>
                                </div>
                            </li>

                            <li class='left dropdown'>
                                <a href='#'>Gestione Tutoring</a>
                                <div class='dropdown-content'>
                                    <a href='tutoringmanagement.php'>Visualizza Tutoring</a>
                                    <a href='tutoringinsertion.php'>Inserisci Tutoring</a>
                                </div>
                            </li>
                            ";
                        break;
                    case "Studente":
                        echo "
                            <li class='left'><a href='tutoringinfo.php'>I miei PCTO</a></li>
                            ";
                        break;
                    case "Referente":
                        echo "
                            <li class='left dropdown'>
                                <a href='#'>Gestione Tutor</a>
                                <div class='dropdown-content'>
                                    <a href='usermanagement.php'>Visualizza Tutor</a>
                                    <a href='userinsertion.php'>Inserisci Tutor</a>
                                </div>
                            </li>
                            ";
                        break;
                    case "Tutor":
                        echo "
                            <li class='left'><a href='tutoringinfo.php'>I miei tutoring</a></li>
                            ";
                        break;
                    case "Docente":
                        echo "
                            <li class='left'><a class='active' href='myclasses.php'>Le mie classi</a></li>
                            ";
                        break;
                }
            }
            
        ?>


        <li class="right dropdown">
            <?php 
                echo "<a href='#' onclick='toggleForm()'> " . $_SESSION["utente"] . "</a>";

                // nel dropdown inserisco il pulsante di logout
                echo "
                    <div id='dropdown' class='dropdown-content'>
                        <a href='" .  $_SERVER['PHP_SELF'] . "?logout=1'>Logout</a>
                    </div>
                    ";
            ?>

            
        </li>
    </ul>

    <?php
        // mi collego al database
        include_once("config.php");
        $con = new mysqli($hostname, $username, $password, $database);

        if(mysqli_connect_errno()) {
            echo "Errore di connessione database: " . $con->connect_error;
            exit();
        }


        // ottengo gli studenti della classe che si vuole visualizzare
        $sql = "SELECT * FROM Utenti WHERE id_classe = $class";
        $ris = $con->query($sql) or die("Errore query studenti classe: " . $con->error);  
        
        // preparo la tabella in cui inserire gli studenti
        echo "<table class='ltable'>";
        echo "<tr class='title'><th>ID utente</th><th>Cognome</th><th>Nome</th><th>Email</th></tr>";
        
        // inserisco tutti gli studneti
        $i = 0;
        foreach($ris as $riga) {
            if ($i == 0) {
                echo "<tr class='clickable pari'  onclick=\"window.location='profile.php?user=".$riga["ID_utente"]."'\">";
            }
            else {
                echo "<tr class='clickable dispari'  onclick=\"window.location='profile.php?user=".$riga["ID_utente"]."'\">";
            }
            $i++;
            $i = $i%2;
            

            echo "<td>".$riga["ID_utente"]."</td>";
            echo "<td>".$riga["cognome"]."</td>";
            echo "<td>".$riga["nome"]."</td>";
            echo "<td>".$riga["email"]."</td>";

            echo "</tr>";
        }
        echo "</table>";
    
    ?>

    <script>
        function toggleForm() {
            if(document.getElementById("dropdown").style.display == "block") {
                // se il menù del logout è attualmente mostrato, lo nascondo
                document.getElementById("dropdown").style.display = "none";
            }
            else {
                // se il menù del logout è attualmente nascosto, lo mostro
                document.getElementById("dropdown").style.display = "block";
            }
        }
    </script>
</body>
</html>