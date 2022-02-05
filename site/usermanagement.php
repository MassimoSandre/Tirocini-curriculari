<?php 
    session_start();

    if($_GET) {
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

    // controllo che l'utente abbia i privilegi per accedere a questa pagina: Amministratore o Referente
    include_once("config.php");
    $con = new mysqli($hostname, $username, $password, $database);

    if(mysqli_connect_errno()) {
        echo "Errore di connessione database: " . $con->connect_error;
        exit();
    }
    
    $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];

    $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

    foreach($ris as $riga) {
        if($riga["tipo"]!="Amministratore" && $riga["tipo"]!="Referente") {
            header("Location: index.php");
            exit();
        }
        else {
            $usertype = $riga["tipo"];
        }
    }
    
    $con->close();


?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <link rel="stylesheet" href="style.css">
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestione utenti</title>
    </head>
    <body 
        <?php
            if($usertype == "Amministratore") {
                echo "onload=\"requestUsers(document.getElementById('usersel').value)\"";
            }
            else {
                include_once("config.php");
                $con = new mysqli($hostname, $username, $password, $database);

                if(mysqli_connect_errno()) {
                    echo "Errore di connessione database: " . $con->connect_error;
                    exit();
                }
                
            
                $sql = "SELECT ID_tipoutente FROM Tipi_utente WHERE tipo = 'Tutor'";
                $ris = $con->query($sql) or die("Errore query ricerca id tipo tutor: " . $con->error);
                foreach($ris as $riga) {
                    $typeid = $riga["ID_tipoutente"];
                }   
                echo "onload=\"requestUsers($typeid)\"";
            
            }
        ?>
    >
        <ul class="navbar">
            <li class="left"><a href="profile.php">Home</a></li>
            <?php
                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
                    switch($_SESSION["privilegi"]) {
                        case "Amministratore":

                            echo "
                                <li class='left dropdown'>
                                    <a class='active' href='#'>Gestione Utenti</a>
                                    <div class='dropdown-content'>
                                        <a class='active' href='usermanagement.php'>Visualizza Utenti</a>
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
                                    <a href='#'>Gestione Classi</a>
                                    <div class='dropdown-content'>
                                        <a href='classmanagement.php'>Visualizza Classi</a>
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
                                    <a class='active' href='#'>Gestione Tutor</a>
                                    <div class='dropdown-content'>
                                        <a class='active' href='usermanagement.php'>Visualizza Tutor</a>
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
                                <li class='left'><a href='myclasses.php'>Le mie classi</a></li>
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
            if($usertype == "Amministratore") {
                echo "
                    <div class='filseldiv'>
                    <select id='usersel' onchange='requestUsers(this.value)'>
                    <option value='*'>Tutti gli utenti</option>";
                    
                include_once("config.php");
                $con = new mysqli($hostname, $username, $password, $database);
        
                if(mysqli_connect_errno()) {
                    echo "Errore di connessione database: " . $con->connect_error;
                    exit();
                }
        
                
                $sql = "
                    SELECT * 
                    FROM  Tipi_utente T";
        
                $ris = $con->query($sql) or die("Errore query tipi utente: " . $con->error);
        
                
                foreach($ris as $riga) {
                    echo "<option value='".$riga["ID_tipoutente"]."'>".$riga["tipo"]."</option>";
                }
                    
                
                $con->close();
                    
                echo "</select></div>";
            }
        ?>

        <div id="usertable"></div>

        <script>
            function requestUsers(x) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    // Typical action to be performed when the document is ready:
                    document.getElementById("usertable").innerHTML = xhttp.responseText;
                    }
                };
                xhttp.open("GET", "gettables.php?users="+x, true);
                xhttp.send();
            }
            function toggleForm() {
                if(document.getElementById("dropdown").style.display == "block") {
                    document.getElementById("dropdown").style.display = "none";
                }
                else {
                    document.getElementById("dropdown").style.display = "block";
                }
            }
        </script>
    </body>
</html>