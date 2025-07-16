# MyVT

l'application myvt est un application web développé a l'aide de php ; elle permet la gestion et plannification des examens à l'ENSAO .
A l'aide de myvt on serai capable de génerer le planning global des devoirs surveillées ainsi que les PV de présence ; les listes des étudiants par salle et finalement les convocation de surveillance et coordination et aussi de controle de présence .
Avec les bibliothèque de PHP on a pu lire et traiter des fichier Excel et les transformer en PDF

## Prérequis

Avant de commencer, assurez-vous d'avoir installé les éléments suivants :
[XAMPP] : https://www.apachefriends.org/download.html (il permet d'avoir à la fois mySql (port 3306) , php (8.0.30 ou plus) et Apache)
[Composer] : https://getcomposer.org/ (pour gérer les dépendances)

## Installation

1. Clonez le dépôt :
    git clone https://github.com/talhaouianas/myVT.git
2. Accédez au répertoire du projet:
    cd myVT
3. installer les dépendance avec composer :
    composer install

## Accédez à l'application :
    via http://localhost/myvt/index.php

## Exécution des tests :
    vendor/bin/phpunit

## Base de données :
    voir connection.php
