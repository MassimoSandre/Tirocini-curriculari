<?php 
    session_start();

    if($_GET) {
        if($_GET["logout"] == 1) {
            $_SESSION = array();

            session_destroy();
        }
    }

    // if($_POST) {
    //     include_once("config.php");
    //     $con = new mysqli($hostname, $username, $password, $database);

    //     if(mysqli_connect_errno()) {
    //         echo "Errore di connessione database: " . $con->connect_error;
    //         exit();
    //     }

    //     $log_email = $_POST["email"];
    //     $log_password = hash("sha512",$_POST["password"]);

        
    //     $sql = "
    //         SELECT * 
    //         FROM Utenti U INNER JOIN Tipi_utente T
    //         ON U.tipo_utente = T.ID_tipoutente
    //         WHERE email = \"$log_email\"
    //         AND password = \"$log_password\"";

    //     $ris = $con->query($sql) or die("Errore query login: " . $con->error);

    //     if($ris->num_rows == 0) {
    //         // non esiste nessun utente corrispondente alle credenziali inserite
    //         $_SESSION["error"] = 1;
    //     }
    //     else {
    //         foreach($ris as $riga) {
    //             $_SESSION["loggedin"] = true;
    //             $_SESSION["utente"] = $riga["nome"] ." ". $riga["cognome"];
    //             $_SESSION["privilegi"] = $riga["tipo"];
    //         }
    //         $_SESSION["error"] = 0;
    //     }
        
    //     $con->close();
    // }
    

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
    <body onload="requestUsers(document.getElementById('usersel'))">
        <select id="usersel" onchange="requestUsers(this)">
            <option value="*">Tutti gli utenti</option>
            <?php
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
            ?>
        </select>

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
                xhttp.open("GET", "getusers.php?users="+x.value, true);
                xhttp.send();
            }
        </script>
    </body>
</html>