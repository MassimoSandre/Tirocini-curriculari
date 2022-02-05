<?php
    if($_POST) {
        // L'utente ha inserito la password e vuole avviare l'installazione

        include_once("config.php");

        // verifico che la password sia corretta
        if(hash("sha512",$_POST["password"]) == $dbinstallpw) {
            if($createDB) {
            // creo la connessione
                $con = new mysqli($hostname, $username, $password);
                // verifico la connessione
                if($con->connect_error) {
                    die ("Connessione fallita: " . $con->connect_error);
                }

                // Creo il DataBase
                $sql = "CREATE DATABASE IF NOT EXISTS $database";
                if($con->query($sql) === TRUE) {
                    echo "DB creato correttamente<br><br>";
                }   
                else{
                    echo "Errore nella creazione del database";
                }

                // Chiudo la connessione
                $con->close();
            }
            // creo la connessione
            $con = new mysqli($hostname, $username, $password, $database);
            // verifico la connessione
            if($con->connect_error) {
                die ("Connessione fallita: " . $con->connect_error);
            }


            // ------ creazione delle tabelle ------
            // Creo la tabella 'Utenti'
            $sql = "
                CREATE TABLE IF NOT EXISTS `Utenti` (
                    `ID_utente` int(8) NOT NULL AUTO_INCREMENT,
                    `nome` varchar(30) NOT NULL,
                    `cognome` varchar(30) NOT NULL,
                    `password` varchar(128) NOT NULL,
                    `email` varchar(50) NOT NULL,
                    `autorizzato` tinyint(1) NOT NULL,
                    `id_azienda` int(8) DEFAULT NULL,
                    `id_classe` int(8) DEFAULT NULL,
                    `tipo_utente` int(8) NOT NULL,
                    PRIMARY KEY (`ID_utente`),

                    CHECK (autorizzato IN (0,1))
                ) ENGINE=InnoDB;";
            if($con->query($sql) === TRUE) {
                echo "Tabella 'Utenti' creata correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione della tabella 'Utenti' " . $con->error);
            }

            // Creo la tabella 'Aziende'
            $sql = "
                CREATE TABLE IF NOT EXISTS `Aziende` (
                    `ID_azienda` int(8) NOT NULL AUTO_INCREMENT,
                    `denominazione` varchar(50) NOT NULL,
                    `referente` int(8) NOT NULL,
                    `percorso_doc` varchar(50),
                    PRIMARY KEY (`ID_azienda`)
                ) ENGINE=InnoDB;";
            if($con->query($sql) === TRUE) {
                echo "Tabella 'Aziende' creata correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione della tabella 'Aziende' " . $con->error);
            }

            // Creo la tabella 'classi'
            $sql = "
                CREATE TABLE IF NOT EXISTS `Classi` (
                    `ID_classe` int(8) NOT NULL AUTO_INCREMENT,
                    `anno` int(11) NOT NULL,
                    `sezione` varchar(4) NOT NULL,
                    `indirizzo` varchar(10) NOT NULL,
                    PRIMARY KEY (`ID_classe`),

                    CHECK (ASCII(sezione) BETWEEN 97 AND 122),
                    CHECK (anno BETWEEN 1 AND 5)
                ) ENGINE=InnoDB;";
            if($con->query($sql) === TRUE) {
                echo "Tabella 'Classi' creata correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione della tabella 'Classi' " . $con->error);
            }

            // Creo la tabella 'Indirizzi'
            $sql = "
                CREATE TABLE IF NOT EXISTS `Indirizzi` (
                    `sigla` varchar(10) NOT NULL,
                    `nome` varchar(30) NOT NULL,
                    `sovrintendente` int(8) NOT NULL,
                    PRIMARY KEY (`sigla`)
                ) ENGINE=InnoDB;";
            if($con->query($sql) === TRUE) {
                echo "Tabella 'Indirizzi' creata correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione della tabella 'Indirizzi' " . $con->error);
            }

            // Creo la tabella 'Tipo_utente'
            $sql = "
                CREATE TABLE IF NOT EXISTS `Tipi_utente` (
                    `ID_tipoutente` int(8) NOT NULL AUTO_INCREMENT,
                    `tipo` varchar(30) NOT NULL,
                    PRIMARY KEY (`ID_tipoutente`)
                ) ENGINE=InnoDB;";
            if($con->query($sql) === TRUE) {
                echo "Tabella 'Tipi_utente' creata correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione della tabella 'Tipi_utente' " . $con->error);
            }

            // Creo la tabella 'Tutoring'
            $sql = "
                CREATE TABLE IF NOT EXISTS `Tutoring` (
                    `id_tutor` int(8) NOT NULL,
                    `id_studente` int(8) NOT NULL,
                    `data_inizio` date NOT NULL,
                    `data_fine` date NOT NULL,
                    `ore_totali` int(8),
                    `ore_assenza` int(8),
                    `valutazione` varchar(512),
                    PRIMARY KEY (`id_tutor`,`id_studente`, `data_inizio`),

                    CHECK (data_inizio <= data_fine),
                    CHECK (ore_totali >= ore_assenza)
                ) ENGINE=InnoDB;";
            if($con->query($sql) === TRUE) {
                echo "Tabella 'Tutoring' creata correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione della tabella 'Tutoring'" . $con->error);
            }


            // ------ creazione delle chiavi esterne ------
            // Creo le chiavi esterne della tabella 'Utenti'
            $sql = "
                ALTER TABLE `Utenti`
                ADD FOREIGN KEY (`id_azienda`) REFERENCES `Aziende` (`ID_azienda`) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD FOREIGN KEY (`id_classe`) REFERENCES `Classi` (`ID_classe`) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD FOREIGN KEY (`tipo_utente`) REFERENCES `Tipi_utente` (`ID_tipoutente`) ON DELETE CASCADE ON UPDATE CASCADE;";
            if($con->query($sql) === TRUE) {
                echo "Chiavi esterne della tabella 'Utenti' create correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione delle chiavi esterne della tabella 'Utenti' " . $con->error);
            }

            // Creo le chiavi esterne della tabella 'Aziende'
            $sql = "
                ALTER TABLE `Aziende`
                ADD FOREIGN KEY (`referente`) REFERENCES `Utenti` (`ID_utente`) ON DELETE CASCADE ON UPDATE CASCADE;";
            if($con->query($sql) === TRUE) {
                echo "Chiavi esterne della tabella 'Aziende' create correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione delle chiavi esterne della tabella 'Aziende' " . $con->error);
            }

            // Creo le chiavi esterne della tabella 'Classi'
            $sql = "
                ALTER TABLE `Classi`
                ADD FOREIGN KEY (`indirizzo`) REFERENCES `Indirizzi` (`sigla`) ON DELETE CASCADE ON UPDATE CASCADE;";
            if($con->query($sql) === TRUE) {
                echo "Chiavi esterne della tabella 'Classi' create correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione delle chiavi esterne della tabella 'Classi' " . $con->error);
            }

            // Creo le chiavi esterne della tabella 'Indirizzi'
            $sql = "
                ALTER TABLE `Indirizzi`
                ADD FOREIGN KEY (`sovrintendente`) REFERENCES `Utenti` (`ID_utente`) ON DELETE CASCADE ON UPDATE CASCADE;";
            if($con->query($sql) === TRUE) {
                echo "Chiavi esterne della tabella 'Indirizzi' create correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione delle chiavi esterne della tabella 'Indirizzi' " . $con->error);
            }

            // Creo le chiavi esterne della tabella 'Tutoring'
            $sql = "
                ALTER TABLE `Tutoring`
                ADD FOREIGN KEY (`id_tutor`) REFERENCES `Utenti` (`ID_utente`) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD FOREIGN KEY (`id_studente`) REFERENCES `Utenti` (`ID_utente`) ON DELETE CASCADE ON UPDATE CASCADE;";
            if($con->query($sql) === TRUE) {
                echo "Chiavi esterne della tabella 'Tutoring' create correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione delle chiavi esterne della tabella 'Tutoring' " . $con->error);
            }


            // ------ Popolazione tabelle ------
            // Popolo la tabella 'Tipi_utente'
            $sql = "
                INSERT INTO Tipi_utente(tipo)
                VALUES  ('Amministratore'),
                        ('Studente'),
                        ('Referente'),
                        ('Tutor'),
                        ('Docente');";
            if($con->query($sql) === TRUE) {
                echo "Tabella 'Tipi_utente' popolata correttamente<br><br>";
            }   
            else{
                die ("Errore nella popolazione della tabella 'Tipi_utente' " . $con->error);
            }


            // Creo l'utente admin default
            $sql = "
                INSERT INTO Utenti(nome, cognome, password, email, autorizzato, tipo_utente)
                VALUES  ('Admin','Admin', '$adminpassword', '$adminmail', '1', '1');";
            if($con->query($sql) === TRUE) {
                echo "Utente admin creato correttamente<br><br>";
            }   
            else{
                die ("Errore nella creazione dell'utente admin " . $con->error);
            }
            
            echo "DataBase Installato correttamente";
            $installed = true;
        }
        else {
            echo "Password errata<br><br>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DBinstall</title>
</head>
<body>
    <?php
    if(!isset($installed) || !$installed) {
        echo "Inserisci la password per installare il database:
            <form action='". $_SERVER["PHP_SELF"] ."' method='POST'>
                <input type='password' name='password' placeholder='password'>
            </form>";
        }
    ?>
</body>
</html>