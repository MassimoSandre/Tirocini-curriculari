<?php 
    session_start();

    // controllo se l'utente ha cliccato sul tasto per il logout, nel caso provvedo alla distruzione della sessione
    if($_GET) {
        if($_GET["logout"] == 1) {
            $_SESSION = array();

            session_destroy();
        }
    }

    if($_POST) {
        // l'utente sta provando a loggare
        include_once("config.php");
        // mi collego al database
        $con = new mysqli($hostname, $username, $password, $database);

        if(mysqli_connect_errno()) {
            echo "Errore di connessione database: " . $con->connect_error;
            exit();
        }

        // prendo le credenziali passate dall'utente
        $log_email = $_POST["email"];
        $log_password = hash("sha512",$_POST["password"]);

        // effettuo una query per verificare che l'utente esista e abbia quelle credenziali e sia autorizzato ad entrare
        $sql = "
            SELECT * 
            FROM Utenti U INNER JOIN Tipi_utente T
            ON U.tipo_utente = T.ID_tipoutente
            WHERE email = \"$log_email\"
            AND password = \"$log_password\"
            AND autorizzato = '1'";

        $ris = $con->query($sql) or die("Errore query login: " . $con->error);


        if($ris->num_rows == 0) {
            // non esiste nessun utente corrispondente alle credenziali inserite (o non è stato abilitato)
            $_SESSION["error"] = 1;
        }
        else {
            // l'utente si è loggato con successo, quindi setto le variabili di sessione
            foreach($ris as $riga) {
                $_SESSION["loggedin"] = true;
                $_SESSION["userid"] = $riga["ID_utente"];
                $_SESSION["utente"] = $riga["nome"] ." ". $riga["cognome"];
                $_SESSION["privilegi"] = $riga["tipo"];
            }
            $_SESSION["error"] = 0;
        }
        
        // chiudo al connessione
        $con->close();
    }
    
    // controllo che l'utente non sia loggato
    if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"]) {
        header("Location: profile.php");
        exit();
    }

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCTO</title>
</head>
<body>

    <!-- <ul class="navbar">
        <li class="left"><a class="active" href="profile.php">Home</a></li>
        <?php
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
                if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
                    // nessun utente loggato
                    echo "<a href='#' onclick='toggleForm()'> Accedi </a>";

                    // nel dropdown inserisco il form di login
                    echo "
                        <div id='dropdown' class='dropdown-content'>
                            <form onsubmit='return checkLogForm()' id='logForm' method='POST' action='" . $_SERVER['PHP_SELF'] . "'>
                                <label for='email'>Email:</label>
                                <input id='log1' type='email' name='email' placeholder='Email...'><br>
                                <label for='password'>Password:</label>
                                <input type='password' name='password' placeholder='Password'><br>

                        ";

                    if(isset($_SESSION["error"]) && $_SESSION["error"] == 1) {
                        echo "
                            <p class='error'> Credenziali non riconosciute! </p>
                            ";
                    }

                    echo "
                                <input type='submit' value='Accedi'>
                            </form>
                        </div>
                        ";
                }
                else {
                    // utente loggato
                    echo "<a href='#' onclick='toggleForm()'> " . $_SESSION["utente"] . "</a>";

                    // nel dropdown inserisco il pulsante di logout
                    echo "
                        <div id='dropdown' class='dropdown-content'>
                            <a href='#'>Profilo</a>
                            <a href='" .  $_SERVER['PHP_SELF'] . "?logout=1'>Logout</a>
                            
                        </div>
                        ";
                }
            ?>

            
        </li>
    </ul> -->


    <?php 

        // nel dropdown inserisco il form di login
        echo "
            <form class='logindiv' onsubmit='return checkLogForm()' id='logForm' method='POST' action='" . $_SERVER['PHP_SELF'] . "'>
                <label for='email'>Email:</label>
                <input onfocus='fixInputError(this)' id='log0' type='email' name='email' placeholder='Email...'><br>
                <label for='password'>Password:</label>
                <input onfocus='fixInputError(this)' id='log1' type='password' name='password' placeholder='Password'><br>

            ";

        if(isset($_SESSION["error"]) && $_SESSION["error"] == 1) {
            echo "
                <p class='error'> Credenziali non riconosciute! </p>
                ";
        }

        echo "
                <input type='submit' value='Accedi'>
            </form>
            ";
       
    ?>

    <script>

        function fixInputError(x) {
            x.classList.remove("errorinput");
        }

        function checkLogForm() {
            let err = false;
            
            if(document.getElementById("log0").value == "") {
                document.getElementById("log0").classList.add("errorinput");
                err = true;
            }
            if(document.getElementById("log1").value == "") {
                document.getElementById("log1").classList.add("errorinput");
                err = true;
            }
            
            return !err;
        }

    </script>
</body>
</html>