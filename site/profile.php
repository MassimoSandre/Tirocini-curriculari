<?php
    // la pagina profile.php permette all'utente di visionare il proprio profilo o quello altrui, purché sia abilitato a farlo
    session_start();

    // controllo se l'utente ha cliccato sul tasto per il logout, nel caso provvedo alla distruzione della sessione
    if($_GET && isset($_GET['logout'])) {
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
    // controllo che sia specificato un utente a cui visionare il profilo
    if(!isset($_GET["user"]) && !isset($_POST["user"])) {
        // se nessuno utente è specificato, reindirizzerò l'utente alla pagina del suo profilo
        $user = $_SESSION["userid"];
    }
    else {
        $user = (isset($_GET["user"])?$_GET["user"]:$_POST["user"]);
    }

    
    // mi collego al database in vista dei controlli
    include_once("config.php");
    $con = new mysqli($hostname, $username, $password, $database);
    if(mysqli_connect_errno()) {
        echo "Errore di connessione database: " . $con->connect_error;
        exit();
    }

    // prendo il tipo dell'utente che sta cercando di accedere
    $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];

    $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

    foreach($ris as $riga) {
        $usertype = $riga["tipo"];
    }

    // controllo che l'utente abbia i privilegi per accedere a questa pagina: 
    $access = false;
    // La possibilità di visionare questa pagina dipende anche dal valore del parametro user
    // Se user è l'id dell'utente corrente, allora potrà accedere in ogni caso a questa pagina
    if($user == $_SESSION["userid"]) {
        $access = true;
    }
    else {
        switch($usertype) {
            case "Amministratore":
                // Se l'utente che vuole accedere è un amministratore, potrà accedere in ogni caso a prescindere a user
                $access = true;
                break;
            case "Docente":
                // Se l'utente che vuole accedere è un docente, è sovrintendente dell'indirizzo della classe dello studente user (purché sia effettivamente uno studente)
                $sql = "
                SELECT I.sovrintendente
                FROM (Utenti U INNER JOIN Classi C 
                    ON U.id_classe = C.ID_classe)
                    INNER JOIN Indirizzi I
                    ON C.indirizzo = I.sigla
                WHERE U.ID_utente = $user";

                $ris = $con->query($sql) or die("Errore query sovrintendente studente: " . $con->error);

                foreach($ris as $riga) {
                    if($riga["sovrintendente"]==$_SESSION["userid"]) {
                        $access = true;
                    }
                }
                break;
            case "Studente":
                // Se l'utente che vuole accedere è uno studente, potrà accedere a questa pagina se user è un suo tutor o se è il docente sovrintendente del suo indirizzo 
                $sql = "
                    SELECT *
                    FROM Tutoring 
                    WHERE id_studente = ".$_SESSION["userid"]." AND id_tutor = $user";
        
                $ris = $con->query($sql) or die("Errore query  verifica tutoring: " . $con->error);
        
                
                if($ris->num_rows != 0) {
                    $access = true;
                }
                    
                
                if(!$access) {
                    $sql = "
                        SELECT I.sovrintendente
                        FROM (Utenti U INNER JOIN Classi C 
                            ON U.id_classe = C.ID_classe)
                            INNER JOIN Indirizzi I
                            ON C.indirizzo = I.sigla
                        WHERE U.ID_utente = ".$_SESSION["userid"];
            
                    $ris = $con->query($sql) or die("Errore query sovrintendente studente: " . $con->error);
            
                    foreach($ris as $riga) {
                        if($riga["sovrintendente"]==$user) {
                            $access = true;
                        }
                    }
                }
                break;
            case "Referente":
                // Se l'utente che vuole accedere è un referente, potrà accedere a questa pagina se user è uno studente che ha effettuato il pcto presso la azienda che rappresenta o se è uno dei tutor della sua azienda
                $sql = "
                    SELECT *
                    FROM (Tutoring T INNER JOIN Utenti U
                    ON T.id_tutor = U.ID_utente)
                    INNER JOIN Aziende A
                    ON U.id_azienda = A.ID_azienda
                    WHERE A.referente = ".$_SESSION["userid"]." AND T.id_studente = $user";
        
                $ris = $con->query($sql) or die("Errore query  verifica tutoring: " . $con->error);
        
                
                if($ris->num_rows != 0) {
                    $access = true;
                }
                
                if(!$access) {
                    $sql = "
                        SELECT *
                        FROM (Utenti U1 INNER JOIN Aziende A
                        ON U1.ID_utente = A.referente)
                        INNER JOIN Utenti U2
                        ON A.ID_azienda = U2.id_azienda
                        WHERE U1.ID_utente = " . $_SESSION["userid"] . " AND U2.ID_utente = $user";
            
                    $ris = $con->query($sql) or die("Errore query verifica referente->tutor: " . $con->error);
            
                    
                    if($ris->num_rows != 0) {
                        $access = true;
                    }
                    
                } 
                break;
            case "Tutor":
                // Se l'utente che vuole accedere è un tutor, potrà accedere a questa pagina se user è uno studente a lui associato (vale anche per pcto passati)
                $sql = "
                    SELECT *
                    FROM Tutoring 
                    WHERE id_tutor = ".$_SESSION["userid"]." AND id_studente = $user";

                $ris = $con->query($sql) or die("Errore query  verifica tutoring: " . $con->error);

                
                if($ris->num_rows != 0) {
                    $access = true;
                }
                break;
        }
    }

    // se l'utente non ha accesso a questa pagina, lo reindirizzo alla sua pagina del profilo
    if(!$access) {
        $user = $_SESSION["userid"];
    }


    if(isset($_GET["action"])) {
        if($_GET["action"] == "deaut") {
            // devo togliere l'autorizzazione all'utente
            $sql = "
                UPDATE Utenti
                SET autorizzato = '0'
                WHERE ID_utente = $user";

            $ris = $con->query($sql) or die("Errore query deautorizzazione: " . $con->error);
        }
        else if($_GET["action"] == "aut") {
            // devo autorizzare l'utente
            $sql = "
                UPDATE Utenti
                SET autorizzato = '1'
                WHERE ID_utente = $user";

            $ris = $con->query($sql) or die("Errore query autorizzazione: " . $con->error);
        }
    }

    if($_POST) {
        if($usertype == "Docente") {
            // effettuo l'aggiornamento della valutazione
            $sql = "
                UPDATE Tutoring
                SET valutazione = '".$_POST["evaluation"]."'
                WHERE id_tutor = '".$_POST["tutor"]."'
                AND id_studente = '".$_POST["stud"]."'
                AND data_inizio = '".$_POST["start"]."'";
                $ris = $con->query($sql) or die("Errore query modifica valutazione tutoring: " . $con->error);
        }
        if($usertype == "Amministratore") {
            if($_POST["submit"] == "Conferma modifiche") {
                if($_POST["tipoutente"] == 2) {
                    // effettuo l'aggiornamento dell'utente di tipologia studente
                    $sql = "
                        UPDATE Utenti
                        SET id_classe='".$_POST["rsel"]."',nome = '".$_POST["nome"]."',cognome = '".$_POST["cognome"]."',email = '".$_POST["email"]."',tipo_utente = '".$_POST["tipoutente"]."'
                        WHERE ID_utente = $user";
                    $ris = $con->query($sql) or die("Errore query modifica utente: " . $con->error);
                }
                else if($_POST["tipoutente"] == 4){
                    // effettuo l'aggiornamento dell'utente di tipologia tutor
                    $sql = "
                        UPDATE Utenti
                        SET id_azienda='".$_POST["rsel"]."',nome = '".$_POST["nome"]."',cognome = '".$_POST["cognome"]."',email = '".$_POST["email"]."',tipo_utente = '".$_POST["tipoutente"]."'
                        WHERE ID_utente = $user";
                    $ris = $con->query($sql) or die("Errore query modifica utente: " . $con->error);
                }
                else  {
                    // effettuo l'aggiornamento dell'utente di tipologia non studente e non tutor
                    $sql = "
                        UPDATE Utenti
                        SET nome = '".$_POST["nome"]."',cognome = '".$_POST["cognome"]."',email = '".$_POST["email"]."',tipo_utente = '".$_POST["tipoutente"]."'
                        WHERE ID_utente = $user";
                    $ris = $con->query($sql) or die("Errore query modifica utente: " . $con->error);
                }
            }
            else if($_POST["submit"] == "Cancella utente") {
                // effettuo la cancellazione dell'utente
                $sql = "
                    DELETE FROM Utenti
                    WHERE ID_utente = $user";
                $ris = $con->query($sql) or die("Errore query eliminazione utente: " . $con->error);
                header("Location: profile.php");
            }
            
        }
    }

    // prendo il nome e il cognome dell'utente di cui si sta cercando di visualizzare il profilo
    $sql = "
        SELECT nome, cognome
        FROM Utenti
        WHERE ID_utente = $user";

    $ris = $con->query($sql) or die("Errore query nome-cognome utente: " . $con->error);

    $title = "";
    foreach($ris as $riga) {
        $title = $riga["nome"] . " " . $riga["cognome"];
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
    <title>
        <?php 
            echo $title;
        ?>
    </title>
</head>
<body>
    <ul class="navbar">
        <li class="left"><a class="active" href="profile.php">Home</a></li>
        <?php
            // creo la navbar, diversa per ogni tipologia di utente
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

    <div class="bg" id="popup">
        
    </div>

    <?php
        echo "<table class='profiletable'>";
        
        // mi collego al database
        include_once("config.php");
        $con = new mysqli($hostname, $username, $password, $database);
        if(mysqli_connect_errno()) {
            echo "Errore di connessione database: " . $con->connect_error;
            exit();
        }

        // richiedo i dati dell'utente di cui si vuole visualizzare il profilo
        $sql = "SELECT * FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = $user";
        $ris = $con->query($sql) or die("Errore query informazioni utente: " . $con->error);

        // metto i dati presi dal database a schermo
        foreach($ris as $riga) {
            echo "<tr><td>$title</td></td>";
            echo "<tr><td class='usertype'>" . $riga["tipo"] . "</td></tr>";
            echo "<tr><td class='info'>  <b>Nome:</b> " . $riga["nome"] . "<br><b>Cognome:</b> " . $riga["cognome"]."<br><b>Email:</b> ".$riga["email"]."</td></tr>";

            $type = $riga["tipo"];
            $isautorized = $riga["autorizzato"];
        }
        
        if($usertype == "Amministratore" && $user != $_SESSION["userid"]) {
            // se l'utente che sta guardando il profilo è admin, aggiungo il tasto "autorizza"/"annulla autorizzazione" 
            if($isautorized) {
                echo "<tr class='adminprofile'><td><a href='profile.php?user=$user&action=deaut'>Annulla autorizzazione</a></td><td><button onclick='profileEditForm()'>Modifica dati</button></td></tr>";
                
            }
            else {
                echo "<tr class='adminprofile'><td><a href='profile.php?user=$user&action=aut'>Autorizza</a></td><td><button onclick='profileEditForm()'>Modifica dati</button></td></tr>";
            }
        }

        echo "</table>";

        
        
        if($type == "Studente") {
            // seleziono la classe dell'utente di cui si vuole visualizare il profilo
            $sql = "SELECT * FROM Classi C INNER JOIN Utenti U ON C.ID_classe = U.id_classe WHERE U.ID_utente = '".$user."'";
            $ris = $con->query($sql) or die("Errore query classe: " . $con->error);  
            
            // metto a schermo, la classe
            foreach($ris as $riga) {                
                echo "<table class=profiletable>";
                echo "<tr class=info><td><b>Classe:</b> ".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"];
                if($user == $_SESSION["userid"]) {
                    // se lo studente sta visualizzando il suo profilo, aggiungo anche il link al profilo del professore di riferimento
                    
                    // ottengo il sovrintendente della classe dello studnete
                    $sql = "SELECT U.* FROM (Indirizzi I INNER JOIN Classi C ON I.sigla = C.indirizzo) INNER JOIN Utenti U ON I.sovrintendente = U.ID_utente WHERE C.ID_classe = '".$riga["ID_classe"]."'";
                    $ris2 = $con->query($sql) or die("Errore query docente di riferimento: " . $con->error);  
                    // metto a schermo il link per visualizzare il profilo del docente di riferimento
                    foreach ($ris2 as $riga2) {
                        echo "<br><b>Docente di riferimento: </b> <a href='profile.php?user=".$riga2["ID_utente"]."'>".$riga2["nome"]." ".$riga2["cognome"]."</a>";
                    }
                }
                echo "</td></tr><table>";
            }

            // in base al tipo di utente che sta visualizzando, si avranno diverse visioni delle attività di PCTO di uno studente
            switch($usertype) {
                case "Studente":
                case "Docente":
                case "Amministratore":
                    // l'amministratore e il docente e lo studente stesso possono vedere tutte le attività di tutoring di un alunno (il docente solo se è sovrintendente dell'indirizzo dello studente, ma questo sarà già stato verificato in fase di accesso alla pagina)
                    $sql = "
                        SELECT T.*, A.denominazione AS azienda,A.percorso_doc, Ut.ID_utente AS ID_tutor,Ut.nome AS tnome, Ut.cognome AS tcognome,
                        Us.nome AS snome, Us.cognome AS scognome, Us.ID_utente as ID_studente, C.*
                        FROM (((Tutoring T INNER JOIN Utenti Ut 
                        ON T.id_tutor = Ut.ID_utente)
                        INNER JOIN Utenti Us 
                        ON T.id_studente = Us.ID_utente)
                        INNER JOIN Classi C
                        ON Us.id_classe = C.ID_classe)
                        INNER JOIN Aziende A
                        ON Ut.id_azienda = A.ID_azienda
                        WHERE Us.ID_utente = '$user'
                        ORDER BY T.data_inizio";
                    break;
                case "Tutor":
                    // il tutor può vedere solo le attività di PCTO seguite da lui
                    $sql = "
                        SELECT T.*, A.denominazione AS azienda, Ut.ID_utente AS ID_tutor,Ut.nome AS tnome, Ut.cognome AS tcognome,
                        Us.nome AS snome, Us.cognome AS scognome, Us.ID_utente as ID_studente, C.*
                        FROM (((Tutoring T INNER JOIN Utenti Ut 
                        ON T.id_tutor = Ut.ID_utente)
                        INNER JOIN Utenti Us 
                        ON T.id_studente = Us.ID_utente)
                        INNER JOIN Classi C
                        ON Us.id_classe = C.ID_classe)
                        INNER JOIN Aziende A
                        ON Ut.id_azienda = A.ID_azienda
                        WHERE Us.ID_utente = '$user'
                        AND Ut.ID_utente = '".$_SESSION["userid"]."'
                        ORDER BY T.data_inizio";
                    break;
                case "Referente":
                    // il referente può vedere solo le attività di pcto svolte nella azienda che rappresenta

                    // oggento l'azienda
                    $sql = "SELECT * FROM Aziende WHERE referente = '".$_SESSION["userid"]."'";
                    $ris = $con->query($sql) or die("Errore query azienda: " . $con->error);          

                    $azienda = "";
                    foreach ($ris as $riga) {
                        $azienda = $riga["ID_azienda"];
                    }


                    // ottengo le attività di PCTO che lo studente ha effettuato nell'azienda del referente
                    $sql = "
                        SELECT T.*, A.denominazione AS azienda, Ut.ID_utente AS ID_tutor,Ut.nome AS tnome, Ut.cognome AS tcognome,
                        Us.nome AS snome, Us.cognome AS scognome, Us.ID_utente as ID_studente, C.*
                        FROM (((Tutoring T INNER JOIN Utenti Ut 
                        ON T.id_tutor = Ut.ID_utente)
                        INNER JOIN Utenti Us 
                        ON T.id_studente = Us.ID_utente)
                        INNER JOIN Classi C
                        ON Us.id_classe = C.ID_classe)
                        INNER JOIN Aziende A
                        ON Ut.id_azienda = A.ID_azienda
                        WHERE Us.ID_utente = '$user'
                        AND A.ID_azienda = '$azienda'
                        ORDER BY T.data_inizio";
                    break;
            } 
            
            
            $ris = $con->query($sql) or die("Errore query attivita' PCTO: " . $con->error);  

            // Mi assicuro che almeno un attività di PCTO sia stata effettuata
            if($ris->num_rows != 0) {
                // inserisco in tabella le attività di PCTO svolte (solo quelle che l'utente che sta visualizzando può effettivametne visionare)
                echo "<table class='ltable'>";
                if($usertype == "Docente") {
                    echo "<tr class='title'><th colspan='7'>Attività di PCTO</th></tr>";
                }
                else {
                    echo "<tr class='title'><th colspan='6'>Attività di PCTO</th></tr>";
                }
                echo "<tr class='title'><th>Azienda</th><th>Tutor</th><th>Data di Inizio</th><th>Data di Fine</th><th>Ore totali</th><th>Ore di assenza</th>";
                if($usertype == "Docente") {
                    // il docente avrà un campo in più, per permettergli di inserire la valutazione dell'esperienza PCTO
                    echo "<th>Valutazione</th>";
                }
                echo "</tr>";
                $i = 0;
                foreach($ris as $riga) {
                    if($i == 0){
                        echo "<tr class='pari'>";
                    }
                    else {
                        echo "<tr class='dispari'>";
                    }
                    $i++;
                    $i%=2;
                    
                    // il docente può anche accedere alla convenzione scuola-azienda se uno studente delle classi che presidia effettua il PCTO presso l'azienda corrispondente
                    if($usertype=="Docente" && $riga["percorso_doc"] != "") {                        
                        echo "<td class='clickable' onclick=\"window.location='".$riga["percorso_doc"]."'\">".$riga["azienda"]."</td>";
                    }
                    else {
                        echo "<td>".$riga["azienda"]."</td>";
                    }
                    
                    echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["ID_tutor"]."'\">".$riga["tnome"]." ".$riga["tcognome"]."</td>";
                    echo "<td>".$riga["data_inizio"]."</td>";
                    echo "<td>".$riga["data_fine"]."</td>";
                    echo "<td>".$riga["ore_totali"]."</td>";
                    echo "<td>".$riga["ore_assenza"]."</td>";
                    if($usertype == "Docente") {
                        if(strtotime($riga["data_fine"]) <= strtotime('now')) {
                            echo "<td><button onclick=\"editEvaluation(".$riga["ID_studente"].",".$riga["ID_tutor"].",'".$riga["data_inizio"]."')\">Visualizza/modifica valutazione</button></td>";
                        }
                        else {
                            echo "<td>attività ancora in corso</td>";
                        }
                        
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        else if ($type == "Tutor") {
            // l'utente che si sta cercando di visualizzare è un tutor

            // ottengo l'azienda per cui lavora il tutor
            $sql = "SELECT * FROM Aziende A INNER JOIN Utenti U ON A.ID_azienda = U.id_azienda WHERE U.ID_utente = '".$user."'";
            $ris = $con->query($sql) or die("Errore query azienda: " . $con->error);  
            
            // metto a video l'azienda in cui lavora il tutor
            foreach($ris as $riga) {                
                echo "<table class=profiletable>";
                echo "<tr class=info><td><b>Azienda:</b> ".$riga["denominazione"];
                
                echo "</td></tr><table>";
            }

            // in base all'utente che accede al profilo ci sarà una vista diversa delle attività di tutoring
            switch($usertype) {
                case "Amministratore":
                case "Referente":
                case "Tutor":
                    // Amministratore e referente possono visionare tutte le attività di tutoring svolte da un tutor
                    $sql = "
                        SELECT T.*, A.denominazione AS azienda, Ut.nome AS tnome, Ut.cognome AS tcognome,
                        Us.nome AS snome, Us.ID_utente AS ID_studente, Us.cognome AS scognome, C.*
                        FROM (((Tutoring T INNER JOIN Utenti Ut 
                        ON T.id_tutor = Ut.ID_utente)
                        INNER JOIN Utenti Us 
                        ON T.id_studente = Us.ID_utente)
                        INNER JOIN Classi C
                        ON Us.id_classe = C.ID_classe)
                        INNER JOIN Aziende A
                        ON Ut.id_azienda = A.ID_azienda
                        WHERE Ut.ID_utente = '$user'
                        ORDER BY T.data_inizio";
                    break;
                case "Studente":
                    // Lo studente può visionare solo le attività di tutoring che lo riguardano
                    $sql = "
                        SELECT T.*, A.denominazione AS azienda, Ut.nome AS tnome, Ut.cognome AS tcognome,
                        Us.nome AS snome, Us.ID_utente AS ID_studente, Us.cognome AS scognome, C.*
                        FROM (((Tutoring T INNER JOIN Utenti Ut 
                        ON T.id_tutor = Ut.ID_utente)
                        INNER JOIN Utenti Us 
                        ON T.id_studente = Us.ID_utente)
                        INNER JOIN Classi C
                        ON Us.id_classe = C.ID_classe)
                        INNER JOIN Aziende A
                        ON Ut.id_azienda = A.ID_azienda
                        WHERE Ut.ID_utente = '$user'
                        AND Us.ID_utente = '".$_SESSION["userid"]."'
                        ORDER BY T.data_inizio";
                    break;
            }
            

            $ris = $con->query($sql) or die("Errore query attivita' tutoring: " . $con->error);  

            // Mi assicuro che almeno un attività di tutoring sia stata effettuata
            if($ris->num_rows != 0) {
                echo "<table class='ltable'>";
                echo "<tr class='title'><th colspan='6'>Attività di tutoring</th></tr>";
                echo "<tr class='title'><th>Studente</th><th>Classe</th><th>Data di Inizio</th><th>Data di Fine</th><th>Ore totali</th><th>Ore di assenza</th></tr>";
                $i = 0;
                foreach($ris as $riga) {
                    if($i == 0){
                        echo "<tr class='pari'>";
                    }
                    else {
                        echo "<tr class='dispari'>";
                    }
                    $i++;
                    $i%=2;
                    echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["ID_studente"]."'\">".$riga["snome"]." ".$riga["scognome"]."</td>";
                    echo "<td>".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</td>";
                    echo "<td>".$riga["data_inizio"]."</td>";
                    echo "<td>".$riga["data_fine"]."</td>";
                    echo "<td>".$riga["ore_totali"]."</td>";
                    echo "<td>".$riga["ore_assenza"]."</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        else if($type == "Referente") {
            // l'utente che si sta cercando di visionare è di tipo referente

            // prendo l'azienda di cui il refente è rappresentante
            $sql = "SELECT * FROM Aziende A WHERE A.referente = '".$user."'";
            $ris = $con->query($sql) or die("Errore query azienda referente: " . $con->error);  
            $azienda = "";

            // metto a video l'azienda di cui il referente è rappresentante
            foreach($ris as $riga) {
                $azienda = $riga["ID_azienda"];
                
                echo "<table class=profiletable>";
                echo "<tr class=info><td><b>Azienda:</b> ".$riga["denominazione"];
                echo "<br><b>Convenzione:</b> ";
                if($riga["percorso_doc"] != "") {
                    echo "<a href='".$riga["percorso_doc"]."' target='_BLANK'>Visualizza</a>";
                }
                else{
                    echo " <i>Convenzione assente</i>";
                }

                echo "</td></tr><table>";
            }

            // Scarico anche le attività di PCTO svolte presso l'azienda
            $sql = "
                SELECT T.*, A.denominazione AS azienda, A.ID_azienda, Ut.nome AS tnome, Ut.cognome AS tcognome,
                Us.nome AS snome, Us.ID_utente AS ID_studente, Us.cognome AS scognome, C.*
                FROM (((Tutoring T INNER JOIN Utenti Ut 
                ON T.id_tutor = Ut.ID_utente)
                INNER JOIN Utenti Us 
                ON T.id_studente = Us.ID_utente)
                INNER JOIN Classi C
                ON Us.id_classe = C.ID_classe)
                INNER JOIN Aziende A
                ON Ut.id_azienda = A.ID_azienda
                WHERE A.ID_azienda = '$azienda'
                ORDER BY T.data_inizio";
            
                $ris = $con->query($sql) or die("Errore query attivita' tutoring: " . $con->error);  

            // Mi assicuro che almeno un attività di tutoring sia stata effettuata
            if($ris->num_rows != 0) {
                echo "<table class='ltable'>";
                echo "<tr class='title'><th colspan='6'>Attività di tutoring</th></tr>";
                echo "<tr class='title'><th>Studente</th><th>Classe</th><th>Data di Inizio</th><th>Data di Fine</th><th>Ore totali</th><th>Ore di assenza</th></tr>";
                $i = 0;
                foreach($ris as $riga) {
                    if($i == 0){
                        echo "<tr class='pari'>";
                    }
                    else {
                        echo "<tr class='dispari'>";
                    }
                    $i++;
                    $i%=2;
                    echo "<td class='clickable' onclick=\"window.location='profile.php?user=".$riga["ID_studente"]."'\">".$riga["snome"]." ".$riga["scognome"]."</td>";
                    echo "<td>".$riga["anno"]."".$riga["indirizzo"]."".$riga["sezione"]."</td>";
                    echo "<td>".$riga["data_inizio"]."</td>";
                    echo "<td>".$riga["data_fine"]."</td>";
                    echo "<td>".$riga["ore_totali"]."</td>";
                    echo "<td>".$riga["ore_assenza"]."</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        else if($type == "Docente") {
            // l'utente a cui si sta cercando di acceder è un docente
            if($usertype == "Amministratore" || $_SESSION["userid"] == $user) {
                // Per l'amministratore e il docente stesso, mostro gli indirizzi che sovrintende
                $sql = "SELECT * FROM Indirizzi WHERE sovrintendente = '".$user."'";
                $ris = $con->query($sql) or die("Errore query indirizzi docente: " . $con->error);  
    
                // Mi assicuro che il docente sia sovrintendente di almeno un indirizzo
                if($ris->num_rows != 0) {
                    // inserisco in tabella li indirizzi
                    echo "<table class='ltable'>";
                    echo "<tr class='title'><th colspan='2'>Indirizzi presidiati</th></tr>";
                    echo "<tr class='title'><th>Indirizzo</th><th>Nome</th></tr>";
                    $i = 0;
                    foreach($ris as $riga) {
                        if($i == 0){
                            echo "<tr class='pari'>";
                        }
                        else {
                            echo "<tr class='dispari'>";
                        }
                        $i++;
                        $i%=2;
                        
                        echo "<td>".$riga["sigla"]."</td>";
                        echo "<td>".$riga["nome"]."</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
        }
        
        // chiudo la connessione
        $con->close();
    ?>

    <script>
        <?php
            if($usertype == "Amministratore") {
                // la funzione profileEditForm() richiede il form per modificare un utente
                // la funzione updateForm() richiede i select extra per la modifica del tipo utente
                echo "    
                    function profileEditForm() {
                        var xhttp = new XMLHttpRequest();
                        xhttp.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                            
                                document.getElementById(\"popup\").innerHTML = xhttp.responseText;
                                document.getElementById(\"popup\").style.display = \"block\";
                            }
                        };
                        xhttp.open(\"GET\", \"gettables.php?profedit=1&utente=$user\", true);
                        xhttp.send();
                    }


                    function updateForm(x) {
                        if(x != '2' && x != '4') {
                            document.getElementById('toreq').innerHTML = '';   
                            return;
                        }
                        var xhttp = new XMLHttpRequest();
                        xhttp.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                            // Typical action to be performed when the document is ready:
                            document.getElementById('toreq').innerHTML = xhttp.responseText;
                            }
                        };
                        xhttp.open('GET', 'getselects.php?type=userins&value='+x, true);
                        xhttp.send();
                    }
                    ";
            }
            else if($usertype == "Docente") {
                // la funzione editEvaluation() richiede il form di modifica di una valutazione di un'attività di PCTO
                echo "
                    function editEvaluation(a,b,c) {
                        var xhttp = new XMLHttpRequest();
                        xhttp.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                            
                                document.getElementById(\"popup\").innerHTML = xhttp.responseText;
                                document.getElementById(\"popup\").style.display = \"block\";
                            }
                        };
                        xhttp.open(\"GET\", \"gettables.php?evaledit=1&stud=\"+a+\"&tutor=\"+b+\"&start=\"+c, true);
                        xhttp.send();
                    }
                    ";
            }
        ?>

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