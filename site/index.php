<?php 
    session_start();

    if($_GET) {
        if($_GET["logout"] == 1) {
            $_SESSION = array();

            session_destroy();
        }
    }

    if($_POST) {
        include_once("config.php");
        $con = new mysqli($hostname, $username, $password, $database);

        if(mysqli_connect_errno()) {
            echo "Errore di connessione database: " . $con->connect_error;
            exit();
        }

        $log_email = $_POST["email"];
        $log_password = hash("sha512",$_POST["password"]);

        
        $sql = "
            SELECT * 
            FROM Utenti U INNER JOIN Tipi_utente T
            ON U.tipo_utente = T.ID_tipoutente
            WHERE email = \"$log_email\"
            AND password = \"$log_password\"";

        $ris = $con->query($sql) or die("Errore query login: " . $con->error);

        if($ris->num_rows == 0) {
            // non esiste nessun utente corrispondente alle credenziali inserite
            $_SESSION["error"] = 1;
        }
        else {
            foreach($ris as $riga) {
                $_SESSION["loggedin"] = true;
                $_SESSION["utente"] = $riga["nome"] ." ". $riga["cognome"];
                $_SESSION["privilegi"] = $riga["tipo"];
            }
            $_SESSION["error"] = 0;
        }
        
        $con->close();
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
<body 
    <?php 
        if(isset($_SESSION['error']) && $_SESSION['error'] == 1) {
            echo "onload='toggleForm();'";
        }
    ?>>

<ul class="navbar">
        <li class="left"><a class="active" href="">Home</a></li>
        <?php
            if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
                switch($_SESSION["privilegi"]) {
                    case "Amministratore":
                        echo "<li class='left'><a href='usermanagament.php'>Gestione utenti</a></li>";
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
    </ul>


    <script>
        function toggleForm() {
            if(document.getElementById("dropdown").style.display == "block") {
                document.getElementById("dropdown").style.display = "none";
            }
            else {
                document.getElementById("dropdown").style.display = "block";
            }
        }

        function fixInputError(x) {
            x.classList.remove("errorinput");
        }

        function checkLogForm() {
            let err = false;
            if(document.getElementById("log0").value == "") {
                document.getElementById("log0").classList.remove("errorinput");
                err = true;
            }
            if(document.getElementById("log1").value == "") {
                document.getElementById("log1").classList.remove("errorinput");
                err = true;
            }
            return !err;
        }

    </script>
</body>
</html>