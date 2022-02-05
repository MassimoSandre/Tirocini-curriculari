<?php 
    session_start();

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

    // controllo che l'utente abbia i privilegi per accedere a questa pagina: Amministratore o Referente
    include_once("config.php");
    $con = new mysqli($hostname, $username, $password, $database);

    if(mysqli_connect_errno()) {
        echo "Errore di connessione database: " . $con->connect_error;
        exit();
    }
    
    $sql = "SELECT T.tipo FROM Utenti U INNER JOIN Tipi_utente T ON U.tipo_utente  = T.ID_tipoutente WHERE U.ID_utente = ".$_SESSION["userid"];

    $ris = $con->query($sql) or die("Errore query verifica privilegi: " . $con->error);

    foreach($ris as $riga) {
        if($riga["tipo"]!="Amministratore" && $riga["tipo"]!="Referente") {
            header("Location: index.php");
            exit();
        }
        else {
            $usertype = $riga["tipo"];
        }
    }
    
    $con->close();

    if($_POST) {
        include_once("config.php");
        $con = new mysqli($hostname, $username, $password, $database);

        if(mysqli_connect_errno()) {
            echo "Errore di connessione database: " . $con->connect_error;
            exit();
        }
        
        if($usertype == "Referente") {
            $sql = "SELECT ID_tipoutente FROM Tipi_utente WHERE tipo = 'Tutor'";
            $ris = $con->query($sql) or die("Errore query ricerca id tipo tutor: " . $con->error);
            foreach($ris as $riga) {
                $user_type = $riga["ID_tipoutente"];
            }            
        }
        else {
            $user_type = $_POST["usertype"];
        }

        
        $user_name = $_POST["name"];
        $user_surname = $_POST["surname"];
        $user_email = $_POST["email"];

        $user_pass = "";
        for($i = 0; $i < 10; $i++) {
            $number = random_int(0, 36);
            $character = base_convert($number, 10, 36);
            $user_pass .= $character;
        }

        /// INVIARE UNA MAIL A $user_email CON LE CREDENZIALI DI ACCESSO
        $message = "
            Usa questi dati per accedere al portale PCTO:\n
            mail: $user_email\n
            password: $user_pass
        ";

        if(!@mail($user_email, "Credenziali per portale PCTO", $message)) {
            echo "si è verificato un errore con l'invio della mail. Ecco il testo del messaggio:\n$message";
        }
        

        $user_site = "";
        $user_class = "";
        if($user_type == "2") {
            $user_class = $_POST["rsel"];
            $sql = "
            INSERT INTO Utenti (nome, cognome, password, email, autorizzato, id_classe, tipo_utente)
            VALUES('$user_name', '$user_surname', '".hash("sha512", $user_pass)."','$user_email','1', '$user_class', '$user_type')
            ";
        }
        else if($user_type == "4"){
            if($usertype == "Amministratore") {
                $user_site = $_POST["rsel"];
                $authorized = 1;
            }
            else {
                $sql = "SELECT ID_azienda FROM Aziende WHERE referente = ".$_SESSION["userid"];
                $ris = $con->query($sql) or die("Errore query ricerca azienda sessione: " . $con->error);
                foreach($ris as $riga) {
                    $user_site = $riga["ID_azienda"];
                    $authorized = 0;
                }
            }
            $sql = "
            INSERT INTO Utenti (nome, cognome, password, email, autorizzato, id_azienda, tipo_utente)
            VALUES('$user_name', '$user_surname', '".hash("sha512", $user_pass)."','$user_email','$authorized', '$user_site', '$user_type')
            ";
        }
        else {
            $sql = "
            INSERT INTO Utenti (nome, cognome, password, email, autorizzato, tipo_utente)
            VALUES('$user_name', '$user_surname', '".hash("sha512", $user_pass)."','$user_email','1', '$user_type')
            ";
        }

        $ris = $con->query($sql) or die("Errore query inserimento utente: " . $con->error);
        
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
    <title>Inserimento Utente</title>
</head>
<body>
    <ul class="navbar">
        <li class="left"><a href="profile.php">Home</a></li>
        <?php
            if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
                switch($_SESSION["privilegi"]) {
                    case "Amministratore":

                        echo "
                            <li class='left dropdown'>
                                <a class='active' href='#'>Gestione Utenti</a>
                                <div class='dropdown-content'>
                                    <a href='usermanagement.php'>Visualizza Utenti</a>
                                    <a class='active' href='userinsertion.php'>Inserisci Utente</a>
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
                                <a class='active' href='#'>Gestione Tutor</a>
                                <div class='dropdown-content'>
                                    <a href='usermanagement.php'>Visualizza Tutor</a>
                                    <a class='active' href='userinsertion.php'>Inserisci Tutor</a>
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
    <form action=<?php echo $_SERVER["PHP_SELF"];?> method="POST">
        <?php
            echo "<div class='filseldiv'>";
            if($usertype == "Amministratore") {
                echo "<label for='usertype'>Tipo utente</label>";
                echo "<select name='usertype' onchange='updateForm(this.value)'>";
            
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
            
                echo "</select><br>";
            }
        ?>
    
        <label for='name'>Nome</label>
        <input type="text" name="name" placeholder="Nome" required><br>
        <label for='surname'>Cognome</label>
        <input type="text" name="surname" placeholder="Cognome" required><br>
        <label for='email'>Email</label>
        <input type="email" name="email" placeholder="Email" required><br>

        <?php if($usertype == "Amministratore") echo "<div id='toreq'></div>";?>

        <input type="submit" value="Inserisci">

        </div>
        
    </form>

    <script>
        <?php
            if($usertype == "Amministratore") {
                echo "
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