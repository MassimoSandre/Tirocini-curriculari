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
            // L'utente non è amministratore, quindi lo rimando alla home
            header("Location: index.php");
            exit();
        }
    }
    


    if($_POST) {
        if($_POST["submit"] == "Conferma modifiche") {
            // eseguo l'update dell'attività di tutoring
            $sql = "
                UPDATE Tutoring 
                SET data_inizio = '".$_POST["newstart"]."',
                data_fine = '".$_POST["newend"]."',
                ore_totali = '".$_POST["oretot"]."',
                ore_assenza = '".$_POST["oreab"]."',
                valutazione = '".$_POST["evaluation"]."'
                WHERE id_tutor = '".$_POST["tutor"]."'
                AND id_studente = '".$_POST["stud"]."'
                AND data_inizio = '".$_POST["start"]."'";
            

            $ris = $con->query($sql) or die("Errore query modifica tutoring: " . $con->error);  
        }
        else if($_POST["submit"] == "Cancella tutoring") {
            // effettuo l'eliminazione dell'attività di tutoring
            $sql = "
                DELETE FROM Tutoring 
                WHERE id_tutor = '".$_POST["tutor"]."'
                AND id_studente = '".$_POST["stud"]."'
                AND data_inizio = '".$_POST["start"]."'";

            $ris = $con->query($sql) or die("Errore query eliminazione tutoring: " . $con->error);  
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
        <title>Gestione Tutoring</title>
    </head>
    <body onload='requestTutoringInfo()'>
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
                                    <a href='#'>Gestione Classi</a>
                                    <div class='dropdown-content'>
                                        <a href='classmanagement.php'>Visualizza Classi</a>
                                        <a href='classinsertion.php'>Inserisci Classe</a>
                                    </div>
                                </li>

                                <li class='left dropdown'>
                                    <a class='active' href='#'>Gestione Tutoring</a>
                                    <div class='dropdown-content'>
                                        <a class='active' href='tutoringmanagement.php'>Visualizza Tutoring</a>
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
        <?php
            echo "<div class='filseldiv'>";
            
                
            echo "<table><tr><td>";
            echo "
                <select id='sitesel' onchange='requestTutors(this.value)'>
                <option value='*'>Tutti le aziende</option>";
    
            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);
    
            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }
    
            // prendo tutte le aziende
            $sql = "
                SELECT * 
                FROM  Aziende A";
    
            $ris = $con->query($sql) or die("Errore query Aziende: " . $con->error);
    
            // inserisco tutte le aziende in un select
            foreach($ris as $riga) {
                echo "<option value='".$riga["ID_azienda"]."'>".$riga["denominazione"]."</option>";
            }
                
                
            echo "</select>";

            echo "<div id='tutorseldiv'><select id='tutorsel' onchange='requestTutoringInfo()'>
                <option value='*'>Tutti i tutor</option>";
            
            // prendo tutti i tutor
            $sql = "
                SELECT *
                FROM Utenti U INNER JOIN Tipi_utente T
                ON U.tipo_utente = T.ID_tipoutente
                WHERE T.tipo = 'Tutor'";

            $ris = $con->query($sql) or die("Errore query tutor: " . $con->error);  

            // inserisco i tutor in un select
            foreach($ris as $riga) {
                echo "<option value='".$riga["ID_utente"]."'>".$riga["nome"]." ".$riga["cognome"]."</option>";
            }

            echo "</select></div>";

            echo "</td><td>";

            echo "
                <select id='coursesel' onchange='requestClasses(this.value)'>
                <option value='*'>Tutti gli indirizzi</option>";
            
            // prendo tutti gli indirizzi
            $sql = "
                SELECT * 
                FROM  Indirizzi";
    
            $ris = $con->query($sql) or die("Errore query indirizzi: " . $con->error);
    
            // inserisco tutti gli indirizzi in un select
            foreach($ris as $riga) {
                echo "<option value='".$riga["sigla"]."'>".$riga["sigla"]." - ".$riga["nome"]."</option>";
            }
                
                
            echo "</select>";

            echo "<div id='classseldiv'><select id='classsel' onchange='requestStudents(this.value)'>
                <option value='*'>Tutte le classi</option>";
            
            // prendo tutte le classi
            $sql = "
                SELECT * 
                FROM Classi";
    
            $ris = $con->query($sql) or die("Errore query classi: " . $con->error);
    
            // inserisco le classi in un select
            foreach($ris as $riga) {
                echo "<option value='".$riga["ID_classe"]."'>".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</option>";
            }

            echo "</select></div>";
            echo "<div id='studentseldiv'>
                <select id='studentsel' onchange='requestTutoringInfo()'>
                    <option value='*'>Tutti gli studenti</option>";
            
            // prendo tutti gli studenti
            $sql = "
                SELECT *
                FROM Utenti U INNER JOIN Tipi_utente T
                ON U.tipo_utente = T.ID_tipoutente
                WHERE T.tipo = 'Studente'";
    
            $ris = $con->query($sql) or die("Errore query studenti: " . $con->error);
    
            // inserisco gli studneti in un select
            foreach($ris as $riga) {
                echo "<option value='".$riga["ID_utente"]."'>".$riga["nome"]." ".$riga["cognome"]."</option>";
            }

            echo "</select></div></td></td></table></div>";

            // chiudo la connessione
            $con->close();
        
        ?>


        <div id="tutoringinfotable"></div>

        <div class="bg" id="popup">
        
        </div>

        <script>
            // la funzione requestTutors(x) richiede i tutor al variare del select delle aziende
            function requestTutors(x) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    // Typical action to be performed when the document is ready:
                    document.getElementById('tutorseldiv').innerHTML = xhttp.responseText;
                    requestTutoringInfo();
                    }
                };
                xhttp.open('GET', 'getselects.php?type=tutors&site='+x, true);
                xhttp.send();
            }
            
            // la funzione editTutoringForm(a,b,c) richiede il form per modificare un'attività di tutoring
            function editTutoringForm(a,b,c) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    
                        document.getElementById("popup").innerHTML = xhttp.responseText;
                        document.getElementById("popup").style.display = "block";
                    }
                };
                xhttp.open("GET", "gettables.php?tutoringedit=1&stud="+a+"&tutor="+b+"&start="+c, true);
                xhttp.send();
            }
                   
            // la funzione requestClasses(x) richiede le classi al variare del select degli indirizzi 
            function requestClasses(x) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    // Typical action to be performed when the document is ready:
                    document.getElementById("classseldiv").innerHTML = xhttp.responseText;
                    requestTutoringInfo();
                    }
                };
                xhttp.open("GET", "getselects.php?type=classes&course="+x, true);
                xhttp.send();
            }

            // la funzione requestStudents(x) richiede gli studneti al variare del select delle classi
            function requestStudents(x) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    // Typical action to be performed when the document is ready:
                    document.getElementById("studentseldiv").innerHTML = xhttp.responseText;
                    requestTutoringInfo();
                    }
                };
                xhttp.open("GET", "getselects.php?type=students&class="+x, true);
                xhttp.send();
            }

            // la funzione requestTutoringInfo() richiede la tabella delle attività di tutoring al variare di qualsiasi select
            function requestTutoringInfo() {
                var azienda = document.getElementById('sitesel').value;
                
                var tutor = document.getElementById("tutorsel").value;
                var studente = document.getElementById("studentsel").value;
                var classe = document.getElementById("classsel").value;

                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    // Typical action to be performed when the document is ready:
                    document.getElementById("tutoringinfotable").innerHTML = xhttp.responseText;
                    }
                };
                xhttp.open("GET", "gettables.php?tutoring=1&azienda="+azienda+"&tutor="+tutor+"&studente="+studente+"&classe="+classe, true);
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