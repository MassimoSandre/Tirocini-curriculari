<?php
    include_once("config.php");

    // creo la connessione
    $con = new mysqli($hostname, $username, $password);
    // verifico la connessione
    if($con->connect_error) {
        die ("Connessione fallita: " . $con->connect_error);
    }

    // Creo il DataBase
    $sql = "CREATE DATABASE $database";
    if($con->query($sql) === TRUE) {
        echo "DB creato correttamente<br><br>";
    }   
    else{
        die ("Errore nella creazione del database");
    }

    // Chiudo la connessione
    $con->close();

    // creo la connessione
    $con = new mysqli($hostname, $username, $password, $database);
    // verifico la connessione
    if($con->connect_error) {
        die ("Connessione fallita: " . $con->connect_error);
    }

    // ------ creazione delle tabelle ------
    // Creo la tabella 'Utenti'
    $sql = "
        CREATE TABLE `Utenti` (
            `ID_utente` int(8) NOT NULL AUTO_INCREMENT,
            `nome` varchar(30) NOT NULL,
            `cognome` varchar(30) NOT NULL,
            `password` varchar(128) NOT NULL,
            `email` varchar(50) NOT NULL,
            `autorizzato` tinyint(1) NOT NULL,
            `id_azienda` int(8) DEFAULT NULL,
            `tipo_utente` int(8) NOT NULL,
            PRIMARY KEY (`ID_utente`)
        ) ENGINE=InnoDB;";
    if($con->query($sql) === TRUE) {
        echo "Tabella 'Utenti' creata correttamente<br><br>";
    }   
    else{
        die ("Errore nella creazione della tabella 'Utenti' " . $con->error);
    }

    // Creo la tabella 'Aziende'
    $sql = "
        CREATE TABLE `Aziende` (
            `ID_azienda` int(8) NOT NULL AUTO_INCREMENT,
            `denominazione` varchar(50) NOT NULL,
            `referente` int(8) NOT NULL,
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
        CREATE TABLE `Classi` (
            `ID_classe` int(8) NOT NULL AUTO_INCREMENT,
            `anno` int(11) NOT NULL,
            `sezione` varchar(4) NOT NULL,
            `indirizzo` varchar(10) NOT NULL,
            PRIMARY KEY (`ID_classe`)
        ) ENGINE=InnoDB;";
    if($con->query($sql) === TRUE) {
        echo "Tabella 'Classi' creata correttamente<br><br>";
    }   
    else{
        die ("Errore nella creazione della tabella 'Classi' " . $con->error);
    }

    // Creo la tabella 'Indirizzi'
    $sql = "
        CREATE TABLE `Indirizzi` (
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
        CREATE TABLE `Tipi_utente` (
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
        CREATE TABLE `Tutoring` (
            `id_tutor` int(8) NOT NULL,
            `id_studente` int(8) NOT NULL,
            `data_inizio` date NOT NULL,
            `data_fine` date NOT NULL,
            PRIMARY KEY (`id_tutor`,`id_studente`)
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
        ADD FOREIGN KEY (`id_azienda`) REFERENCES `Aziende` (`ID_azienda`),
        ADD FOREIGN KEY (`tipo_utente`) REFERENCES `Tipi_utente` (`ID_tipoutente`);";
    if($con->query($sql) === TRUE) {
        echo "Chiavi esterne della tabella 'Utenti' create correttamente<br><br>";
    }   
    else{
        die ("Errore nella creazione delle chiavi esterne della tabella 'Utenti' " . $con->error);
    }

    // Creo le chiavi esterne della tabella 'Aziende'
    $sql = "
        ALTER TABLE `Aziende`
        ADD FOREIGN KEY (`referente`) REFERENCES `Utenti` (`ID_utente`);";
    if($con->query($sql) === TRUE) {
        echo "Chiavi esterne della tabella 'Aziende' create correttamente<br><br>";
    }   
    else{
        die ("Errore nella creazione delle chiavi esterne della tabella 'Aziende' " . $con->error);
    }

    // Creo le chiavi esterne della tabella 'Classi'
    $sql = "
        ALTER TABLE `Classi`
        ADD FOREIGN KEY (`indirizzo`) REFERENCES `Indirizzi` (`sigla`);";
    if($con->query($sql) === TRUE) {
        echo "Chiavi esterne della tabella 'Classi' create correttamente<br><br>";
    }   
    else{
        die ("Errore nella creazione delle chiavi esterne della tabella 'Classi' " . $con->error);
    }

    // Creo le chiavi esterne della tabella 'Indirizzi'
    $sql = "
        ALTER TABLE `Indirizzi`
        ADD FOREIGN KEY (`sovrintendente`) REFERENCES `Utenti` (`ID_utente`);";
    if($con->query($sql) === TRUE) {
        echo "Chiavi esterne della tabella 'Indirizzi' create correttamente<br><br>";
    }   
    else{
        die ("Errore nella creazione delle chiavi esterne della tabella 'Indirizzi' " . $con->error);
    }

    // Creo le chiavi esterne della tabella 'Tutoring'
    $sql = "
        ALTER TABLE `Tutoring`
        ADD FOREIGN KEY (`id_tutor`) REFERENCES `Utenti` (`ID_utente`),
        ADD FOREIGN KEY (`id_studente`) REFERENCES `Utenti` (`ID_utente`);";
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
        VALUES  ('Admin','Admin', '".hash("sha512","admin")."', '$adminmail', '1', '1');";
    if($con->query($sql) === TRUE) {
        echo "Utente admin creato correttamente<br><br>";
    }   
    else{
        die ("Errore nella creazione dell'utente admin " . $con->error);
    }
    
    echo "DataBase Installato correttamente";

?>