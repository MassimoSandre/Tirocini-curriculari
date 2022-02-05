<?php
    // impostazioni del database
    $hostname="localhost";
    $username="root";
    $password="";
    $database="db_pcto_def";

    // password per avviare l'installazione (sha512): 12345
    $dbinstallpw = "3627909a29c31381a071ec27f7c9ca97726182aed29a7ddd2e54353322cfb30abb9e3a6df2ac2c20fe23436311d678564d0c8d305930575f60e2d3d048184d79";

    // credenziali primo account (password in sha512: 12345)
    $adminmail="admin@admin";
    $adminpassword = "3627909a29c31381a071ec27f7c9ca97726182aed29a7ddd2e54353322cfb30abb9e3a6df2ac2c20fe23436311d678564d0c8d305930575f60e2d3d048184d79";


    // se posta a false non verrà creato il database, se posta a true verrà creato
    $createDB = true;

    // cartella dove salvare i documenti
    $uploaddir = $_SERVER['DOCUMENT_ROOT']."/sito_elaborato/2705/doc/";
?>