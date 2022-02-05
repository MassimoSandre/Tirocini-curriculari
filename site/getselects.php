<?php

    session_start();

    // la pagina di aspetta delle richieste di tipo GET
    if($_GET && isset($_GET["type"])) {
        
        if($_GET["type"] == "userins") { 
            // si sta richiedendo l'eventuale select da associare all'inserimento di un tutor o di uno studente
            if($_GET["value"]=="2") { 
                // il select richiesto è quello dello studente

                // mi collego al database
                include_once("config.php");
                $con = new mysqli($hostname, $username, $password, $database);

                if(mysqli_connect_errno()) {
                    echo "Errore di connessione database: " . $con->connect_error;
                    exit();
                }

                // mi assicuro che l'utente abbia i permessi per richiedere questo select
                $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
                $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

                foreach($ris as $riga) {
                    $usertype = $riga["tipo"];
                }

                if($usertype == "Amministratore") {
                    // l'utente è un amministratore, quindi ha il permesso di ricevere a questo select

                    // seleziono tutte le classi
                    $sql = "
                        SELECT *
                        FROM Classi
                        ORDER BY anno, indirizzo, sezione";

                    $ris = $con->query($sql) or die("Errore query classi: " . $con->error);  
                    
                    
                    // metto tutte le classi in un selct
                    echo "<select name='rsel'>";
                    $i = 0;
                    foreach($ris as $riga) {
                        echo "<option value='".$riga["ID_classe"]."'>".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</option>";
                    }
                    echo "</select>";
                }
            }
            else if($_GET["value"]=="4") {
                // il form richiesto è quello del tutor

                // mi connetto al database
                include_once("config.php");
                $con = new mysqli($hostname, $username, $password, $database);

                if(mysqli_connect_errno()) {
                    echo "Errore di connessione database: " . $con->connect_error;
                    exit();
                }

                // mi assicuro che l'utente abbia il permesso per ricevere questo select
                $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
                $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

                foreach($ris as $riga) {
                    $usertype = $riga["tipo"];
                }

                if($usertype == "Amministratore") {
                    // l'utente è un amministratore, quindi può accedere a questo select

                    // scarico tutte le aziende dal db
                    $sql = "
                        SELECT *
                        FROM Aziende";

                    $ris = $con->query($sql) or die("Errore query classi: " . $con->error);  
                    
                    
                    // metto tutte le aziende in un select
                    echo "<select name='rsel'>";
                    
                    $i = 0;
                    foreach($ris as $riga) {
                        echo "<option value='".$riga["ID_azienda"]."'>".$riga["denominazione"]."</option>";
                    }
                    echo "</select>";
                }
            }
        }
        else if($_GET["type"] == "nextsection") {
            // l'utente vuole richiedere la prossima sessione, passati indirizzo e anno di una classe
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro che l'utente abbia il permesso di richiedere questo dato
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // l'utente è un amministratore, quindi può accedere a questo dato

                // seleziono il numero di classi con lo stesso anno e indirizzo di quella passata
                $sql = "
                    SELECT COUNT(*) as nclasses
                    FROM Classi
                    WHERE anno = ".$_GET["year"]."
                    AND indirizzo = '".$_GET["course"]."'";

                $ris = $con->query($sql) or die("Errore query num classi: " . $con->error);  
                
                foreach($ris as $riga) {
                    // in base al numero di classi con lo stesso anno e indirizzo, genero la lettera della sezione, partendo dalla a
                    echo chr(97+$riga["nclasses"]);
                }
            }
        }
        else if($_GET["type"] == "newcourse") {
            // l'utente sta richiedendo form per inserire un nuovo indirizzo
            
            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro che l'utente abbia il permesso di accedere a questo form
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // L'utente è un amministratore, quindi può accedere al form

                // seleziono tutti i sovrintendenti
                $sql = "
                    SELECT *
                    FROM Utenti U INNER JOIN Tipi_utente T
                    ON U.tipo_utente = T.ID_tipoutente
                    WHERE T.tipo = 'Docente'";

                $ris = $con->query($sql) or die("Errore query sovrintendenti: " . $con->error);  
                
                // preparo i campi del form
                echo "<tr>";
                echo "<td>";
                echo "<input type='text' name='sigla'>";
                echo "</td>";
                echo "<td>";
                echo "<input type='text' name='nome'>";
                echo "</td>";
                echo "<td>";
                echo "<select name='sovrintendente'>";

                // inserisco i docenti in un select
                foreach($ris as $riga) {
                    echo "<option value='".$riga['ID_utente']."'>".$riga["nome"]." ".$riga["cognome"]."</option>";
                }
                echo "</select>";
                echo "</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan='3'>";
                echo "<input type='submit' value='Aggiungi'>";
                echo "</td>";
                echo "</tr>";
            }
        }
        else if($_GET["type"] == "myclasses") {
            // l'utente sta richiedendo una lista di classi
            
            // i parametri sono il docente (l'utente loggato) e l'indirizzo per cui filtrare
            $doc = $_SESSION["userid"];
            $courses = $_GET["value"];

            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro che l'utente possa accedere alle classi
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Docente") {
                // l'utente è un docente, eseguiamo la query seguente, che si assicura anche che il docente sia effettivamente sovrintendente dell'indirizzo che sta richiedendo

                // seleziono tutti le classi degli indirizzi richiesti, purché rientrino tra quelli di cui il docente è il sovrintendente
                $sql = "
                    SELECT *
                    FROM Indirizzi I INNER JOIN Classi C
                    ON I.sigla = C.indirizzo
                    WHERE I.sovrintendente = $doc
                    AND (I.sigla = '$courses' OR '$courses' = '*')
                    ORDER BY C.anno, C.indirizzo, C.sezione";

                $ris = $con->query($sql) or die("Errore query classi docente: " . $con->error);  

                // creo una serie di div che contengono il nome della classe e che, una volta cliccati, rimandano alla pagina class.php della classe corrispondente
                foreach($ris as $riga) {
                    echo "<div id='".$riga["ID_classe"]."' onclick=\"window.location='class.php?class=".$riga["ID_classe"]."'\" class='inline-block-item'>";
                    echo "<div>";
                    echo $riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"];
                    echo "</div>";
                    echo "</div>";

                }
            }
        }
        else if($_GET["type"] == "tutors") {
            // l'utente sta richiedendo il select dei tutor

            $site = $_GET["site"];
            

            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro che l'utente possa accedere a questi dati
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                //l'utente è amministratore, quindi può accedere a questo select

                // prendo tutti i tutor delle aziende richieste
                $sql = "
                    SELECT *
                    FROM Utenti U INNER JOIN Tipi_utente T
                    ON U.tipo_utente = T.ID_tipoutente
                    WHERE T.tipo = 'Tutor'
                    AND (U.id_azienda = '$site' OR '$site' = '*')";

                $ris = $con->query($sql) or die("Errore query classi docente: " . $con->error);  

                // inserisco in un select i tutor estratti
                echo "<select id='tutorsel' onchange='requestTutoringInfo()'>
                    <option value='*'>Tutti i tutor</option>";
                foreach($ris as $riga) {
                    echo "<option value='".$riga["ID_utente"]."'>".$riga["nome"]." ".$riga["cognome"]."</option>";
                }
                echo "</select>";
            }
        }
        else if($_GET["type"] == "classes") {
            // l'utente vuole richiedere un select con delle classi, di un determinato indirizzo (o tutti)

            // prendo l'indirizzo per cui filtrare
            $course = $_GET["course"];

            // mi collego al db
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro che l'utente abbia i permessi per accedere a questo select
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // l'utente è un amministratore, quindi può accedere a questa select

                // scarico tutte le classi degli indirizzi richiesti
                $sql = "
                        SELECT * 
                        FROM Classi
                        WHERE (indirizzo = '$course' OR '$course' = '*')";
            
                $ris = $con->query($sql) or die("Errore query classi: " . $con->error);
        
                // inserisco in un select le classi scaricate
                echo "<select id='classsel' onchange='requestStudents(this.value)'>
                    <option value='*'>Tutte le classi</option>";
                foreach($ris as $riga) {
                    echo "<option value='".$riga["ID_classe"]."'>".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</option>";
                }
                echo "</select>";
            }
        }
        else if($_GET["type"] == "students") {
            // l'utente sta richiedendo un select con degli studenti, di una determinata classe o di tutte le classi
            $class = $_GET["class"];

            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro che l'utente abbia i permessi per accedere al select che sta richiedendo
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // L'utente è un amministratore, quindi ha il permesso di accedere al select

                // scarico tutti gli studenti delle classi richieste
                $sql = "
                        SELECT * 
                        FROM Utenti U INNER JOIN Tipi_utente T
                        ON U.tipo_utente = T.ID_tipoutente
                        WHERE (U.id_classe = '$class' OR '$class' = '*')
                        AND T.tipo = 'Studente'";
            
                $ris = $con->query($sql) or die("Errore query classi: " . $con->error);
        
                // inserisco le classi scaricate in un select
                echo "<select id='studentsel' onchange='requestTutoringInfo()'>
                    <option value='*'>Tutti gli studenti</option>";
                foreach($ris as $riga) {
                    echo "<option value='".$riga["ID_utente"]."'>".$riga["nome"]." ".$riga["cognome"]."</option>";
                }
                echo "</select>";
            }
        }
    }

?>
