<?php 
    session_start();

    // controllo se l'utente ha cliccato sul tasto per il logout, nel caso provvedo alla distruzione della sessione
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

    // controllo che l'utente abbia i privilegi per accedere a questa pagina: Amministratore
    include_once("config.php");
    $con = new mysqli($hostname, $username, $password, $database);

    if(mysqli_connect_errno()) {
        echo "Errore di connessione database: " . $con->connect_error;
        exit();
    }
    
    $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
    $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

    foreach($ris as $riga) {
        if($riga["tipo"]!="Amministratore") {
            // l'utente non è amministratore, lo rimando all'home
            header("Location: index.php");
            exit();
        }
    }
    

    if($_POST) {
        if($_POST["submit"] == "Conferma modifiche") {
            // l'utente vuole salvare delle modfiche effettuate
            // eseguo l'aggiornamento
            $sql = "
                UPDATE Classi 
                SET anno = '".$_POST["anno"]."',
                sezione = '".$_POST["sezione"]."',
                indirizzo = '".$_POST["indirizzo"]."'
                WHERE ID_classe = '".$_POST["class"]."'";
            
            $ris = $con->query($sql) or die("Errore query modifica classe: " . $con->error);  
        }
        else if($_POST["submit"] == "Cancella classe") {
            // l'utente vuole cancellare una classe
            // eseguo l'eliminazione
            $sql = "
                DELETE FROM Classi 
                WHERE ID_classe = '".$_POST["class"]."'";

            $ris = $con->query($sql) or die("Errore query eliminazione classe: " . $con->error);  
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
        <title>Gestione Classi</title>
    </head>
    <body onload="requestClasses(document.getElementById('coursesel'))">
        <ul class="navbar">
            <li class="left"><a href="profile.php">Home</a></li>
            <?php
                // creo la navbar, diversa per ogni tipo di utente
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
        <div class='filseldiv'>
            <select id="coursesel" onchange="requestClasses(this)">
                <option value="*">Tutti gli indirizzi</option>
                <?php
                    // mi collego al database
                    include_once("config.php");
                    $con = new mysqli($hostname, $username, $password, $database);
            
                    if(mysqli_connect_errno()) {
                        echo "Errore di connessione database: " . $con->connect_error;
                        exit();
                    }
            
                    // richiedo gli indirizzi al database
                    $sql = "
                        SELECT * 
                        FROM  Indirizzi";
                    $ris = $con->query($sql) or die("Errore query indirizzi: " . $con->error);
            
                    // metto gli indirizzi in un select
                    foreach($ris as $riga) {
                        echo "<option value='".$riga["sigla"]."'>".$riga["sigla"]." - ".$riga["nome"]."</option>";
                    }
                        
                    // chiudo la connessione
                    $con->close();
                ?>
            </select>
        </div>

        <div id="classtable"></div>

        <div class="bg" id="popup">
        
        </div>
        <script>

            // funzione per richiedere il form di modifica classe
            function editClassForm(a) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    
                        document.getElementById("popup").innerHTML = xhttp.responseText;
                        document.getElementById("popup").style.display = "block";
                    }
                };
                xhttp.open("GET", "gettables.php?classedit=1&class="+a, true);
                xhttp.send();
            }

            // funzione per chiedere le classi dato un indirizzo (o anche tutti gli indirizzi)
            function requestClasses(x) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    // Typical action to be performed when the document is ready:
                    document.getElementById("classtable").innerHTML = xhttp.responseText;
                    }
                };
                xhttp.open("GET", "gettables.php?classes="+x.value, true);
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