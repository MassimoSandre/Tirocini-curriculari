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
            // l'utente non è un amministratore, quindi lo rimando alla home
            header("Location: index.php");
            exit();
        }
    }
    
    $con->close();

    if($_POST) {
        // l'utente ha inserito una nuova classe da inserire
        include_once("config.php");
        $con = new mysqli($hostname, $username, $password, $database);

        if(mysqli_connect_errno()) {
            echo "Errore di connessione database: " . $con->connect_error;
            exit();
        }

        // prendo i dati per la classe
        $class_year = $_POST["year"];
        $class_course = $_POST["course"];
        $class_section = $_POST["section"];


        // inserisco la classe nel db
        $sql = "
            INSERT INTO Classi (anno, sezione, indirizzo)
            VALUES('$class_year','$class_section','$class_course')";
        

        $ris = $con->query($sql) or die("Errore query inserimento classe: " . $con->error);

        // chiudo la connessione
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
    <title>Inserimento Classi</title>
</head>
<body onload="updateForm()">
    <ul class="navbar">
        <li class="left"><a href="profile.php">Home</a></li>
        <?php
            // creo la navbar, diversa per ogni utente
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
                                    <a href='classmanagement.php'>Visualizza Classi</a>
                                    <a class='active' href='classinsertion.php'>Inserisci Classe</a>
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
        <label for='year'>Anno</label>
        <select name="year" id="anno" onchange="updateForm()">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
        </select>

        <label for='indirizzo'>Indirizzo</label>
        <select name="course" id="indirizzo" onchange="updateForm()" required>
            <?php
                // mi collego al database
                include_once("config.php");
                $con = new mysqli($hostname, $username, $password, $database);
        
                if(mysqli_connect_errno()) {
                    echo "Errore di connessione database: " . $con->connect_error;
                    exit();
                }
        
                // prendo tutti gli indirizzi ordinati per sigla
                $sql = "
                    SELECT * 
                    FROM  Indirizzi
                    ORDER BY sigla";
                $ris = $con->query($sql) or die("Errore query tipi utente: " . $con->error);
        
                // metto gli indirizzi estratti in un select
                foreach($ris as $riga) {
                    echo "<option value='".$riga["sigla"]."'>".$riga["sigla"]." - ".$riga["nome"]."</option>";
                }
                    
                // chiudo la connessione
                $con->close(); 
            ?>
        </select>
        <br>

        <label for='sezione'>Sezione:</label>
        <input type="text" id="sezione" name="section" placeholder="Sezione" readonly><br>

        <input type="submit" value="Inserisci">

    </form>


    <script>
        // funzione per richiedere al server la nuova sezione in funzione di anno e indirizzo inseriti
        function updateForm() {
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    // inserisco la sezione scaricata nell'input corrispondente
                    document.getElementById("sezione").value = xhttp.responseText;
                }
            };
            xhttp.open("GET", "getselects.php?type=nextsection&course="+document.getElementById("indirizzo").value+"&year="+document.getElementById("anno").value, true);
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