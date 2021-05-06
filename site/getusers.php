<?php

    if($_GET && isset($_GET["users"])) {
        
        include_once("config.php");
        $con = new mysqli($hostname, $username, $password, $database);

        if(mysqli_connect_errno()) {
            echo "Errore di connessione database: " . $con->connect_error;
            exit();
        }

        $sql = "
            SELECT *
            FROM (Utenti U LEFT JOIN Aziende A
            ON U.id_azienda = A.ID_azienda) 
            LEFT JOIN Tipi_utente T
            ON U.tipo_utente = T.ID_tipoutente
            WHERE tipo_utente = '".$_GET["users"]."'
            OR \"".$_GET["users"]."\" = \"*\"";

        $ris = $con->query($sql) or die("Errore query users: " . $con->error);  
        
        
        echo "<table class='ltable'>";
        echo "<tr class='title'><th>ID utente</th><th>Cognome</th><th>Nome</th><th>Email</th><th>Autorizzato</th><th>Azienda</th><th>Tipo utente</th></tr>";
        
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
            

            echo "<td>".$riga["ID_utente"]."</td>";
            echo "<td>".$riga["cognome"]."</td>";
            echo "<td>".$riga["nome"]."</td>";
            echo "<td>".$riga["email"]."</td>";
            echo "<td>".$riga["autorizzato"]."</td>";
            echo "<td>".$riga["denominazione"]."</td>";
            echo "<td>".$riga["tipo"]."</td>";

            echo "</tr>";
        }
        echo "</table>";

    }

?>
