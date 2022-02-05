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
            // l'utente non è un amministratore, lo rimando alla home
            header("Location: index.php");
            exit();
        }
    }
    

    if($_POST) {
        if(!isset($_POST["submit"])) { 
            // l'utente vuole inserire un indirizzo
            $course = $_POST["sigla"];
            $course_name = $_POST["nome"];
            $course_supervisor = $_POST["sovrintendente"];

            // eseguo l'inserimento
            $sql = "
                INSERT INTO Indirizzi (sigla, nome, sovrintendente)
                VALUES('$course','$course_name','$course_supervisor')";
            

            $ris = $con->query($sql) or die("Errore query inserimento indirizzo: " . $con->error);
            
        }
        else {
            
            if($_POST["submit"] == "Conferma modifiche") {
                // l'utente vuole modificare un indirizzo
                // effettuo l'aggiornamento
                $sql = "
                    UPDATE Indirizzi 
                    SET nome = '".$_POST["nome"]."',
                    sovrintendente = '".$_POST["sovrintendente"]."'
                    WHERE sigla = '".$_POST["course"]."'";
                
    
                $ris = $con->query($sql) or die("Errore query modifica indirizzo: " . $con->error);  
            }
            else if($_POST["submit"] == "Cancella indirizzo") {
                // l'utente vuole cancellare un indirizzo
                // eseguo l'eliminazione
                $sql = "
                    DELETE FROM Indirizzi 
                    WHERE sigla = '".$_POST["sigla"]."'";
    
                $ris = $con->query($sql) or die("Errore query eliminazione indirizzo: " . $con->error);  
            }
        }

    }

    // chiudo la connessione
    $con->close();
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <link rel="stylesheet" href="style.css">
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestione Indirizzi</title>
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
                                    <a href='#'>Gestione Aziende</a>
                                    <div class='dropdown-content'>
                                        <a href='sitemanagement.php'>Visualizza Aziende</a>
                                        <a href='siteinsertion.php'>Inserisci Azienda</a>
                                    </div>
                                </li>
                                
                                <li class='left'><a class='active' href='coursemanagement.php'>Gestione Indirizzi</a></li>

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
        <div id="coursestable">
            <?php
                // mi connetto al database
                include_once("config.php");
                $con = new mysqli($hostname, $username, $password, $database);
    
                if(mysqli_connect_errno()) {
                    echo "Errore di connessione database: " . $con->connect_error;
                    exit();
                }
    
                // prendo dal db tutti gli indirizzi col relativo sovrintendente
                $sql = "
                    SELECT I.*, U.nome as snome, U.cognome as scognome
                    FROM Indirizzi I INNER JOIN Utenti U
                    ON I.sovrintendente = U.ID_utente";
    
                $ris = $con->query($sql) or die("Errore query classi: " . $con->error);  
                
                // imposto la tabella per gli indirizzi
                echo "<form method='POST' action='" . $_SERVER["PHP_SELF"] . "'>";
                echo "<table class='ltable' id='coursetable'>";
                echo "<tr class='title'><th>Sigla</th><th>Nome</th><th>Sovrintendente</th></tr>";
                
                // metto tutti gli indirizzi scaricati dal db come record in una tabella
                $i = 0;
                foreach($ris as $riga) {
                    if ($i == 0) {
                        echo "<tr class='pari'>";
                    }
                    else {
                        echo "<tr class='dispari'>";
                    }
                    $i++;
                    $i = $i%2;
                    
    
                    echo "<td>".$riga["sigla"]."</td>";
                    echo "<td>".$riga["nome"]."</td>";
                    echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["sovrintendente"]."'\">".$riga["snome"]." ".$riga["scognome"]."</td>";
                    echo "<td class='ext'><button type='button' onclick=\"editCourseForm('".$riga["sigla"]."')\">Modifica</button></td>";
                    echo "</tr>";
                }
                echo "<tr id='newbutton'><td colspan='3'><input type='button' onclick='requestForm()' value='Aggiungi'></td></tr>";
                echo "</table></form>";
            ?>
        </div>
            
        <div class="bg" id="popup">
        
        </div>

        <script>
           
           // funzione per richiedere il form di modifica di un indirizzo
           function editCourseForm(a) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    
                        document.getElementById("popup").innerHTML = xhttp.responseText;
                        document.getElementById("popup").style.display = "block";
                    }
                };
                xhttp.open("GET", "gettables.php?courseedit=1&course="+a, true);
                xhttp.send();
            }

            // funzione per richiedere il form di inserimento di un nuovo indirizzo
            function requestForm() {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    // Typical action to be performed when the document is ready:
                    document.getElementById("newbutton").remove();
                    document.getElementById("coursetable").innerHTML += xhttp.responseText;
                    }
                };
                xhttp.open("GET", "getselects.php?type=newcourse&target=<?php echo $_SERVER["PHP_SELF"]; ?>", true);
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