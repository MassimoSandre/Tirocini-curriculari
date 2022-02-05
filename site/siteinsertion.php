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

    // chiudo la connessione
    $con->close();

    if($_POST) {
        // effettuo la connessione
        include_once("config.php");
        $con = new mysqli($hostname, $username, $password, $database);

        if(mysqli_connect_errno()) {
            echo "Errore di connessione database: " . $con->connect_error;
            exit();
        }


        $site_name = $_POST["sitename"];
        $site_ref = $_POST["referente"];


        // effettuo l'inserimento dell'azienda
        $sql = "
            INSERT INTO Aziende (denominazione, referente)
            VALUES('$site_name', '$site_ref')
            ";
        

        $ris = $con->query($sql) or die("Errore query inserimento azienda: " . $con->error);
        
        // chiudo la connessione
        $con->close();
    }
    

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Azienda</title>
</head>
<body>
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
                                    <a href='sitemanagement.php'>Visualizza Aziende</a>
                                    <a class='active' href='siteinsertion.php'>Inserisci Azienda</a>
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
    <form class='filseldiv' action=<?php echo $_SERVER["PHP_SELF"];?> method="POST">
            <label for='sitename'>Denominazione:</label>
            <input type="text" name="sitename" placeholder="Denominazione" required><br>

            <label for='referente'>Referente:</label>
            <select name="referente" required>
            <?php

                // mi collego al datbase
                include_once("config.php");
                $con = new mysqli($hostname, $username, $password, $database);
        
                if(mysqli_connect_errno()) {
                    echo "Errore di connessione database: " . $con->connect_error;
                    exit();
                }
        
                // eseguo la query per trovare i referenti (solo quelli non ancora associati ad un'azienda)
                $sql = "
                        SELECT * 
                        FROM  Utenti U INNER JOIN Tipi_utente T
                        ON U.tipo_utente = T.ID_tipoutente
                        WHERE T.tipo = 'Referente'
                        AND NOT EXISTS (
                            SELECT * 
                            FROM Aziende A
                            WHERE A.referente = U.ID_utente
                        )"; // un utente non può essere referente di due aziende
        
                $ris = $con->query($sql) or die("Errore query referenti: " . $con->error);
        
                // metto i referenti in un select
                foreach($ris as $riga) {
                    echo "<option value='".$riga["ID_utente"]."'>".$riga["nome"]." ".$riga["cognome"]."</option>";
                }
                    
                
                // chiudo la connessione
                $con->close(); 
            ?>
        </select>

        <input type="submit" value="Inserisci">
        
    </form>

    <script>
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