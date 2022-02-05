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
            // L'utente non è un amministratore, quindi lo rimando alla home
            header("Location: index.php");
            exit();
        }
    }
    

    // controllo se sono stati caricati file 
    if(isset($_POST["carica"])) {
        //print_r( $_FILES["docupload"]);
        include_once("config.php");

        $ext = array_reverse(explode(".",$_FILES['docupload']['name']))[0];

        // genero un nome random per il file, in modo che non sia indicizzabile senza conoscerne il nome
        $filename = "";
        for($i = 0; $i < 10; $i++) {
            $number = random_int(0, 36);
            $character = base_convert($number, 10, 36);
            $filename .= $character;
        }

        $folderfile = basename($filename.".$ext");
        $uploadfile = $uploaddir . $folderfile;

        // inserisco il percorso della documentazione nel database
        $sql = "UPDATE Aziende SET percorso_doc = 'doc/$folderfile' WHERE ID_azienda = ".$_POST["site"];
        $ris = $con->query($sql) or die("Errore query inserimento percorso file: " . $con->error);

        // sposto il file nel percorso
        move_uploaded_file($_FILES['docupload']['tmp_name'], $uploadfile);
           
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
        <title>Gestione Aziende</title>
    </head>
    <body onload="requestSites()">
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
                                    <a href='#' class='active'>Gestione Aziende</a>
                                    <div class='dropdown-content'>
                                        <a class='active' href='sitemanagement.php'>Visualizza Aziende</a>
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
        <div id="sitetable"></div>

        <script>
            // la funzione requestSites() richiede la tabella con i dati delle aziende
            function requestSites() {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    // Typical action to be performed when the document is ready:
                    document.getElementById("sitetable").innerHTML = xhttp.responseText;
                    }
                };
                xhttp.open("GET", "gettables.php?sites=1", true);
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