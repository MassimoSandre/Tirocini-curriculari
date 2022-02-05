<?php

    session_start();

    // la pagina di aspetta una richiesta di tipo GET
    if($_GET) {
        
        if(isset($_GET["users"])) {
            // L'utente sta richiedendo una tabella che riporti un sottoinsieme degli utenti
            
            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // controllo che l'utente abbia i permessi per accedere a questa tabella
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {                
                // l'utente è un referente, può accedere a tutti i dati, non c'è neccessità di ulteriori controlli

                // prendo gli utenti e le relative informazioni, tenendo conto dei filtri passati per parametro
                $sql = "
                    SELECT *
                    FROM ((Utenti U LEFT JOIN Aziende A
                    ON U.id_azienda = A.ID_azienda) 
                    LEFT JOIN Tipi_utente T
                    ON U.tipo_utente = T.ID_tipoutente)
                    LEFT JOIN Classi C
                    ON U.id_classe = C.ID_classe
                    WHERE tipo_utente = '".$_GET["users"]."'
                    OR \"".$_GET["users"]."\" = \"*\"";

                $ris = $con->query($sql) or die("Errore query users: " . $con->error);                  

                // inserisco in una tabella gli utenti scaricati
                echo "<table class='ltable'>";
                echo "<tr class='title'><th>ID utente</th><th>Cognome</th><th>Nome</th><th>Email</th><th>Autorizzato</th><th>Azienda</th><th>Classe</th><th>Tipo utente</th></tr>";
                
                $i = 0;
                foreach($ris as $riga) {
                    if ($i == 0) {
                        echo "<tr class='pari clickable' onclick=\"window.location='profile.php?user=".$riga["ID_utente"]."'\">";
                    }
                    else {
                        echo "<tr class='dispari clickable' onclick=\"window.location='profile.php?user=".$riga["ID_utente"]."'\">";
                    }
                    $i++;
                    $i = $i%2;
                    

                    echo "<td>".$riga["ID_utente"]."</td>";
                    echo "<td>".$riga["cognome"]."</td>";
                    echo "<td>".$riga["nome"]."</td>";
                    echo "<td>".$riga["email"]."</td>";
                    echo "<td>".($riga["autorizzato"]?"Sì":"No")."</td>";
                    echo "<td>".$riga["denominazione"]."</td>";

                    if($riga["id_classe"] == "") {
                        // l'utente non è uno studente
                        echo "<td></td>";
                    }
                    else {
                        // l'utente è uno studente, quindi stampo la sua classe e la rendo un link alla pagina che fornisce dettagli sulla classe
                        echo "<td class='clickable' onclick=\"window.location='class.php?class=".$riga["id_classe"]."'\">".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</td>";
                    }
                    echo "<td>".$riga["tipo"]."</td>";

                    echo "</tr>";
                }
                echo "</table>";
            }
            else if($usertype == "Referente"){
                // L'utente che ha richiesto una tabella è un referente, quindi bisogna fare controlli più approfonditi
                // devo per prima cosa capire che azienda rappresenta il referente
                $sql = "SELECT * FROM Aziende WHERE referente = '".$_SESSION["userid"]."'";
                $ris = $con->query($sql) or die("Errore query azienda: " . $con->error);  

                $site = "";
                foreach($ris as $riga) {
                    $site = $riga["ID_azienda"];
                }

                // Prendo tutti i tutor dell'azienda che il referente rappresenta
                $sql = "SELECT * FROM Utenti U INNER JOIN Tipi_utente T ON U.Tipo_utente = T.ID_tipoutente WHERE U.id_azienda = '$site' AND T.tipo = 'Tutor'";
                $ris = $con->query($sql) or die("Errore query tutors: " . $con->error);  

                // inserisco i tutor in una tabella 
                echo "<table class='ltable'>";
                echo "<tr class='title'><th>ID utente</th><th>Cognome</th><th>Nome</th><th>Email</th><th>Autorizzato</th><th>Tipo utente</th></tr>";
                
                $i = 0;
                foreach($ris as $riga) {
                    if ($i == 0) {
                        echo "<tr class='pari clickable' onclick=\"window.location='profile.php?user=".$riga["ID_utente"]."'\">";
                    }
                    else {
                        echo "<tr class='dispari clickable' onclick=\"window.location='profile.php?user=".$riga["ID_utente"]."'\">";
                    }
                    $i++;
                    $i = $i%2;
                    

                    echo "<td>".$riga["ID_utente"]."</td>";
                    echo "<td>".$riga["cognome"]."</td>";
                    echo "<td>".$riga["nome"]."</td>";
                    echo "<td>".$riga["email"]."</td>";
                    echo "<td>".($riga["autorizzato"]?"Sì":"No")."</td>";
                    echo "<td>".$riga["tipo"]."</td>";

                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        else if(isset($_GET["sites"])) {
            // L'utente sta richiedendo una tabella contenente i dati delle aziende

            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // controllo che l'utente possa accedere a questa tabella
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // L'utente è un amministratore, quindi può accedere a questa tabella

                // scarico le aziende dal database
                $sql = "
                    SELECT A.*,U.nome, U.cognome
                    FROM Aziende A INNER JOIN Utenti U
                    ON A.referente = U.ID_utente";

                $ris = $con->query($sql) or die("Errore query aziende: " . $con->error);  
                
                // inserisco le aziende in una tabella
                echo "<table class='ltable'>";
                echo "<tr class='title'><th>ID azienda</th><th>Denominazione</th><th>Referente</th><th>Documentazione</th></tr>";
                
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
                    

                    echo "<td>".$riga["ID_azienda"]."</td>";
                    echo "<td>".$riga["denominazione"]."</td>";
                    echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["referente"]."'\">".$riga["nome"]." ".$riga["cognome"]."</td>";
                    
                    echo "<td>";
                    
                    if($riga["percorso_doc"] != "") {
                        // se è specificato il percorso della documentazione, aggiungo un link per visualizzarla
                        echo "<a href='".$riga["percorso_doc"]."' target='_BLANK'>Visualizza</a>";
                    }
                    echo "
                    <form action='sitemanagement.php' method='post' enctype='multipart/form-data'>
                        <input type='hidden' name='site' value='".$riga["ID_azienda"]."'>
                        <input type='file' name='docupload'>
                        <input type='submit' name='carica' value='Carica'>
                    </form>";
                    echo "</td></tr>";
                }
                echo "</table>";
            }
        }
        else if(isset($_GET["classes"])) {
            // L'utente vuole ricevere una tabella contenente i dati delle classi

            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro che l'utente abbia i permessi per richiedere questa tabella
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // l'utente è un amministratore, quindi ha il permesso di richiedere questa tabella

                // richiedo tutte le classi con i relativi dati. Applico anche i filtri richiesti dall'utente
                $sql = "
                    SELECT C.*, I.*, U.nome as snome, U.cognome as scognome
                    FROM (Classi C INNER JOIN Indirizzi I
                    ON C.indirizzo = I.sigla) INNER JOIN Utenti U
                    ON I.sovrintendente = U.ID_utente
                    WHERE C.indirizzo = '".$_GET["classes"]."'
                    OR \"".$_GET["classes"]."\" = \"*\"
                    ORDER BY C.anno, C.indirizzo, C.sezione";

                $ris = $con->query($sql) or die("Errore query classi: " . $con->error);  
                
                // inserisco le classi prese dal database in una tabella
                echo "<table class='ltable'>";
                echo "<tr class='title'><th>ID classe</th><th>Anno</th><th>Indirizzo</th><th>Sezione</th><th>Sovrintendente</th><th>N. Studenti</th></tr>";
                
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
                    

                    echo "<td>".$riga["ID_classe"]."</td>";
                    echo "<td>".$riga["anno"]."</td>";
                    echo "<td>".$riga["indirizzo"]." - ".$riga["nome"]."</td>";
                    echo "<td>".$riga["sezione"]."</td>";
                    echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["sovrintendente"]."'\">".$riga["snome"]." ".$riga["scognome"]."</td>";

                    // calcolo quanti studenti ci sono in una classe
                    $sql = "SELECT COUNT(*) AS nstud FROM Utenti WHERE ID_classe = ".$riga["ID_classe"];
                    $ris2 = $con->query($sql) or die("Errore query num studenti: " . $con->error);
                    
                    // inserisco in un campo della tabella il numero di studenti per classe
                    foreach($ris2 as $riga2) {
                        echo "<td class='clickable' onclick=\"window.location='class.php?class=".$riga["ID_classe"]."'\">".$riga2["nstud"]."</td>";
                    }

                    // inserisco anche il tasto per modificare un record
                    echo "<td class='ext'><button onclick=\"editClassForm(".$riga["ID_classe"].")\">Modifica</button></td>";

                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        else if(isset($_GET["tutoring"])) {
            // l'utente sta richiedendo una tabella contenente i dati delle attività di tutoring
            // con i seguenti parametri:
            $azienda = $_GET["azienda"];
            $tutor = $_GET["tutor"];
            $studente = $_GET["studente"];
            $classe = $_GET["classe"];

            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // verifico il tipo dell'utente
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            switch($usertype) {
                case "Amministratore":
                    // l'amministratore può accedere in ogni caso
                    break;
                case "Referente":
                    // per il referente, per sicurezza, mi assicuro che l'azienda richiesta sia la sua
                    $azienda = "";
                    $sql = "SELECT ID_azienda FROM Aziende WHERE referente = '".$_SESSION["userid"]."'";
                    $ris = $con->query($sql) or die("Errore query azienda referente: " . $con->error);
                    foreach($ris as $riga) {
                        $azienda = $riga["ID_azienda"];
                    }
                    break;
                case "Docente":
                    // per il docente, per sicurezza, mi assicuro che la classe richista sia una di quelle che sovrintende
                    $sql = "SELECT * FROM Indirizzi I INNER JOIN Classi C ON I.sigla = C.indirizzo WHERE I.sovrintendente = '".$_SESSION["userid"]."' AND C.ID_classe = '$classe'";
                    $ris = $con->query($sql) or die("Errore query azienda referente: " . $con->error);
                    if($ris->num_rows == 0) {
                        $classe = "";

                    }
                    break;
                case "Studente":
                    // per lo studente è sufficiente forzare come studente sé stesso, dato che non può vedere i dati di altri studenti
                    $studente = $_SESSION["userid"];
                    break;
                case "Tutor":
                    // per il tutto è sufficiente forzare come tutor sé stesso, dato che non può vedere i dati di altri tutor
                    $tutor = $_SESSION["userid"];

            }

            // richiedo i dati dal database, con i filtri richiesti dall'utente (e opportunamente adattati)
            $sql = "
                SELECT T.*, A.denominazione AS azienda, A.referente, Us.ID_utente as ID_studente, Ut.ID_utente as ID_tutor ,Ut.nome AS tnome, Ut.cognome AS tcognome,
                Us.nome AS snome, Us.cognome AS scognome, C.*
                FROM (((Tutoring T INNER JOIN Utenti Ut 
                ON T.id_tutor = Ut.ID_utente)
                INNER JOIN Utenti Us 
                ON T.id_studente = Us.ID_utente)
                INNER JOIN Classi C
                ON Us.id_classe = C.ID_classe)
                INNER JOIN Aziende A
                ON Ut.id_azienda = A.ID_azienda
                WHERE (A.ID_azienda = '$azienda' OR '$azienda' = '*')
                AND (Ut.ID_utente = '$tutor' OR '$tutor' = '*')
                AND (Us.ID_utente = '$studente' OR '$studente' = '*')
                AND (C.ID_classe = '$classe' OR '$classe' = '*')";

            $ris = $con->query($sql) or die("Errore query tutoring: " . $con->error);  
            
            
            // inserisco le attività di tutoring in una tabella
            echo "<table class='ltable'>";
            echo "<tr class='title'><th>Azienda</th><th>Tutor</th><th>Studente</th><th>Classe</th><th>Data di inizio</th><th>Data di fine</th><th>Ore totali</th><th>Ore di assenza</th></tr>";
            
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
                

                echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["referente"]."'\">".$riga["azienda"]."</td>";
                echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["ID_tutor"]."'\">".$riga["tnome"]." ".$riga["tcognome"]."</td>";
                echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["ID_studente"]."'\">".$riga["snome"]." ".$riga["scognome"]."</td>";
                echo "<td>".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</td>";
                echo "<td>".$riga["data_inizio"]."</td>";
                echo "<td>".$riga["data_fine"]."</td>";
                echo "<td>".$riga["ore_totali"]."</td>";
                echo "<td>".$riga["ore_assenza"]."</td>";
                
                // inserisco anche il tasto per modificare il record
                echo "<td class='ext'><button onclick=\"editTutoringForm(".$riga["ID_studente"].",".$riga["ID_tutor"].",'".$riga["data_inizio"]."')\">Modifica</button></td>";
                
                echo "</tr>";
            }
            echo "</table>";
            $con->close();
        }
        else if(isset($_GET["profedit"])) {
            // l'utente sta richiedendo il form per modificare un utente

            $user = $_GET["utente"];
    
            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }
            
            // verifico che l'utente possa accedere al form
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // L'utente è un amministratore, qundi può accedere al form

                // richiedo i dati dell'utente da modificare
                $sql = "SELECT * FROM (Utenti U LEFT JOIN Classi C ON U.id_classe = C.ID_classe) LEFT JOIN Aziende A ON U.id_azienda = A.ID_azienda WHERE ID_utente = '$user'";
                $ris = $con->query($sql) or die("Errore query dati utente: " . $con->error);  
        
                // inserisco i dati richiesti negli input di un form
                echo "<form action='profile.php' method='post'>";
                echo "<table>";
                foreach($ris as $riga) {
                    echo "<input type='hidden' name='user' value='$user'>";
                    echo "<tr><td>Nome</td><td><input name='nome' type='text' value='".$riga["nome"]."'></td></tr>";
                    echo "<tr><td>Cognome</td><td><input name='cognome' type='text' value='".$riga["cognome"]."'></td></tr>";
                    echo "<tr><td>Email</td><td><input name='email' type='text' value='".$riga["email"]."'></td></tr>";
                    echo "<tr><td>Autorizzato</td><td>".($riga["autorizzato"]?"Sì":"No")."</td></tr>";
                    #if($riga["id_azienda"]!="")echo "<tr><td>Azienda</td><td>".$riga["denominazione"]."</td></tr>";
                    #if($riga["id_classe"]!="")echo "<tr><td>Classe</td><td>".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</td></tr>";
                    echo "<tr><td>Tipo utente</td><td>";
        
                    // richiedo tutti i tipi utente
                    $sql = "SELECT * FROM Tipi_utente";
                    $ris2 = $con->query($sql) or die("Errore query tipi utente: " . $con->error);  
        
                    // inserisco i tipi utente in un select
                    echo "<select name='tipoutente' onchange='updateForm(this.value)'>";
                    foreach($ris2 as $riga2) {
                        echo "<option value='".$riga2["ID_tipoutente"]."' ".($riga2["ID_tipoutente"]==$riga["tipo_utente"]? "selected":"").">".$riga2["tipo"]."</option>";
                        if($riga2["ID_tipoutente"]==$riga["tipo_utente"]) $tipo = $riga2["tipo"];
                    }
                    echo "</select>";
                    echo "</td></tr>";
                    
                    if($tipo == "Studente") {
                        $classe = $riga["id_classe"];
                    }
                    else if($tipo == "Tutor") {
                        $azienda = $riga["id_azienda"];
                    }
                }
                echo "<tr><td></td><td id='toreq'>";
                
                echo $tipo;

                if($tipo == "Studente") {
                    // nel caso degli studenti, aggiungo un select con tutte le classi
                    $sql = "SELECT * FROM Classi";
                    $ris = $con->query($sql) or die("Errore query tipi utente: " . $con->error);  
                    echo "<select name='rsel'>";
                    foreach($ris as $riga) {
                        echo "<option value='".$riga["ID_classe"]."' ".($riga["ID_classe"]==$classe? "selected":"").">".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</option>";
                        
                    }
                    echo "</select>";
                }
                else if($tipo == "Tutor") {
                    // nel caso dei tutor, aggiungo un select con tutte le aziende
                    $sql = "SELECT * FROM Aziende";
                    $ris = $con->query($sql) or die("Errore query tipi utente: " . $con->error);  
                    echo "<select name='rsel'>";
                    foreach($ris as $riga) {
                        echo "<option value='".$riga["ID_azienda"]."' ".($riga["ID_azienda"]==$azienda? "selected":"").">".$riga["denominazione"]."</option>";
                        
                    }
                    echo "</select>";
                }


                echo "</td></tr></table>";
                echo "<input type='submit' name='submit' value='Conferma modifiche'>";
                echo "<input class='cancella' type='submit' name='submit' value='Cancella utente'>";
                echo "<button type='button' onclick=\"document.getElementById('popup').style.display='none'\">Annulla</button>";
                echo "</form>";
            }
        }
        else if(isset($_GET["evaledit"])) {
            // l'utente sta richiedendo il form per modificare una valutazione

            $stud = $_GET["stud"];
            $tutor = $_GET["tutor"];
            $start = $_GET["start"];

            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro, tramite una query, che l'utente sia un docente e che sia sovrintendente dell'indirizzo della classe dello studente a cui vuole modificare la valutazione di una delle sue attività di PCTO
            $sql = "
                SELECT * 
                FROM (Indirizzi I INNER JOIN Classi C
                ON I.sigla  = C.indirizzo)
                INNER JOIN Utenti U
                ON U.id_classe = C.ID_classe
                WHERE U.ID_utente = '$stud'
                AND I.sovrintendente = '".$_SESSION["userid"]."'";


            $ris = $con->query($sql) or die("Errore query verifica studente->docente: " . $con->error);


            if($ris->num_rows != 0) {
                // effettivamente l'utente ha il permesso di modificare questi dati

                // seleziono l'attività di tutoring che si vuole modificare
                $sql = "SELECT * FROM Tutoring WHERE id_tutor = '$tutor' AND id_studente = '$stud' AND data_inizio = '$start'";
                $ris = $con->query($sql) or die("Errore query dati tutoring: " . $con->error);  

                // creo il form per la modifica della valutazione dell'attività di tutoring
                foreach($ris as $riga) {
                    echo "<form action='profile.php' method='post'>";
                    echo "<input type='hidden' name='user' value='$stud'>";
                    echo "<input type='hidden' name='stud' value='$stud'>";
                    echo "<input type='hidden' name='tutor' value='$tutor'>";
                    echo "<input type='hidden' name='start' value='$start'>";
                    echo "<textarea maxlength='512' name='evaluation'>".$riga["valutazione"]."</textarea>";
                    
                    echo "<input type='submit' value='Modifica'>";

                    echo "<button type='button' onclick=\"document.getElementById('popup').style.display='none'\">Annulla</button>";

                    echo "</form>";
                }
            }
        }
        else if(isset($_GET["tutoringedit"])) {
            // l'utente vuole richiedere il form per modificare un'attività di tutoring
            $stud = $_GET["stud"];
            $tutor = $_GET["tutor"];
            $start = $_GET["start"];

            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // mi assicuro che l'utente possa accedere a questo form
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // l'utente è amministratore, quindi può accedere a questi dati

                // richiesto i dati dell'attività da modificare
                $sql = "SELECT * FROM Tutoring WHERE id_tutor = '$tutor' AND id_studente = '$stud' AND data_inizio = '$start'";
                $ris = $con->query($sql) or die("Errore query dati tutoring: " . $con->error);  

                // preparo il form con gli input con i valori dell'attività da modificare
                foreach($ris as $riga) {
                    echo "<form action='tutoringmanagement.php' method='post'>";
                    echo "<input type='hidden' name='stud' value='$stud'>";
                    echo "<input type='hidden' name='tutor' value='$tutor'>";
                    echo "<input type='hidden' name='start' value='$start'>";

                    echo "<input type='date' name='newstart' value='".$riga["data_inizio"]."'>";
                    echo "<input type='date' name='newend' value='".$riga["data_fine"]."'>";
                    echo "<input type='number' name='oretot' value='".$riga["ore_totali"]."'>";
                    echo "<input type='number' name='oreab' value='".$riga["ore_assenza"]."'>";
                    echo "<textarea maxlength='512' name='evaluation'>".$riga["valutazione"]."</textarea>";
                    

                    echo "<input type='submit' name='submit' value='Conferma modifiche'>";
                    echo "<input class='cancella' type='submit' name='submit' value='Cancella tutoring'>";
                    echo "<button type='button' onclick=\"document.getElementById('popup').style.display='none'\">Annulla</button>";

                    echo "</form>";
                }
            }
            
        }
        else if(isset($_GET["classedit"])) {
            // l'utente vuole richiedere il form per modificare una classe
            $class = $_GET["class"];
            
            // mi connetto al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // verifico che l'utente possa accedere al form
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // se l'utente è un amministraore può accedere a questo form

                // richiesto i dati della classe da modificare
                $sql = "SELECT * FROM Classi WHERE ID_classe = '$class'";
                $ris = $con->query($sql) or die("Errore query dati classe: " . $con->error);  


                // preparo il form con gli input con i valori della classe da modificare
                foreach($ris as $riga) {
                    echo "<form action='classmanagement.php' method='post'>";
                    echo "<input type='hidden' name='class' value='$class'>";

                    echo "<input type='number' name='anno' value='".$riga["anno"]."'>";
                    
                    $sql = "SELECT * FROM Indirizzi";
                    $ris2 = $con->query($sql) or die("Errore query indirizzi: " . $con->error);  

                    echo "<select name='indirizzo'>";
                    foreach($ris2 as $riga2) {
                        echo "<option value='".$riga2["sigla"]."' ".($riga2["sigla"]==$riga["indirizzo"]?"selected":"").">" . $riga2["sigla"] . " - " . $riga2["nome"] . "</option>";
                    }
                    echo "</select>";

                    echo "<input type='text' name='sezione' value='".$riga["sezione"]."'>";
                    

                    echo "<input type='submit' name='submit' value='Conferma modifiche'>";
                    echo "<input class='cancella' type='submit' name='submit' value='Cancella classe'>";
                    echo "<button type='button' onclick=\"document.getElementById('popup').style.display='none'\">Annulla</button>";

                    echo "</form>";
                }
            }
        }
        else if(isset($_GET["courseedit"])) {
            // l'utente vuole richiedere il form per modificare un indirizzo
            $course = $_GET["course"];
            
            // mi collego al database
            include_once("config.php");
            $con = new mysqli($hostname, $username, $password, $database);

            if(mysqli_connect_errno()) {
                echo "Errore di connessione database: " . $con->connect_error;
                exit();
            }

            // verifico che l'utente possa accedere al form
            $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];
            $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

            foreach($ris as $riga) {
                $usertype = $riga["tipo"];
            }

            if($usertype == "Amministratore") {
                // l'utente è un amministratore, quindi può acceder al form
                // prendo i dati dell'indirizzo che va modificato
                $sql = "SELECT I.*, U.nome AS unome, U.cognome AS ucognome FROM Indirizzi I INNER JOIN Utenti U ON I.sovrintendente = U.ID_utente WHERE I.sigla = '$course'";
                $ris = $con->query($sql) or die("Errore query dati indirizzo: " . $con->error);  

                // preparo un form con gli input con i value dell'indirizzo da modificare
                foreach($ris as $riga) {
                    echo "<form action='coursemanagement.php' method='post'>";
                    echo "<input type='hidden' name='course' value='$course'>";

                    echo "<input type='text' readonly value='".$riga["sigla"]."'>";
                    echo "<input type='text' name='nome' value='".$riga["nome"]."'>";
                    
                    // scarico tutti i sovrintendenti
                    $sql = "SELECT * FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente = T.ID_tipoutente WHERE T.tipo = 'Docente'";
                    $ris2 = $con->query($sql) or die("Errore query sovrintendenti: " . $con->error);  

                    // creo un select con i sovrintendenti
                    echo "<select name='sovrintendente'>";
                    foreach($ris2 as $riga2) {
                        echo "<option value='".$riga2["ID_utente"]."' ".($riga2["ID_utente"]==$riga["sovrintendente"]?"selected":"").">" . $riga2["nome"] . " " . $riga2["cognome"] . "</option>";
                    }
                    echo "</select>";


                    echo "<input type='submit' name='submit' value='Conferma modifiche'>";
                    echo "<input class='cancella' type='submit' name='submit' value='Cancella indirizzo'>";
                    echo "<button type='button' onclick=\"document.getElementById('popup').style.display='none'\">Annulla</button>";

                    echo "</form>";
                }
            }
        
        }
        
    }

    

?>

