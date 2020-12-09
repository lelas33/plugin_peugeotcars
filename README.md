# plugin-PeugeotCars

## Fonctions

Ce plugin permet d'accèder aux informations de votre voiture connectée Peugeot, ainsi qu'à la position GPS courante du véhicule.
Il détermine ainsi les trajets réalisés et enregistre ces trajets dans une base de données.

Les informations disponibles dans le widgets sont:
* Charge de la batterie, autonomie et kilométrage de la voiture
* Information sur le chargement de la batterie (Prise connectée, temps de chargement, vitesse de chargement..)
* Nombre de jours et kilomètres jusqu'au prochain entretien du véhicule
* Situation du véhicule sur une carte (Position GPS), et information si le véhicule se déplace.

Ce plugin a été développé et testé avec un véhicule Peugeot 208 électrique. 
Il doit fonctionner pour les autres véhicules électriques ou essence/Diesel de la marque, mais il y aura sans doute quelques infos manquantes.
 

<p align="left">
  <img src="../master/doc/images/widget.png" width="200" title="Widget dashboard">
</p>

## Versions
* Tag pgc_v0.1: Version draft originale du 19/10/2020
* Tag pgc_v0.2: Version draft avec correction plantage Jeedom (20/10/2020)
* Tag pgc_v0.3: Première version connectée du plugin (20/10/2020)

## Installation
Par source Github:
* Aller dans Jeedom menu Plugins / Gestion des plugins
* Sélectionner le symbole + (Ajouter un plugin)
* Sélectionner le type de source Github (Il faut l'avoir autorisé au préalable dans le menu Réglages / Système / Configuration => Mise à jour/Market)
* Remplir les champs:
  * ID logique du plugin : peugeotcars
  * Utilisateur ou organisation du dépôt : lelas33
  * Nom du dépôt : plugin_peugeotcars
  * Branche : master
* Aller dans le menu "plugins/objets connectés/Voitures Peugeot" de jeedom pour installer le nouveau plugin.

Sur la page configuration du plugin, saisir vos identifiants de compte MyPeugeot, et cochez la case :"Afficher le panneau desktop". Cela donne accès à la page du "panel" de l'équipement.

## Configuration
Une fois l'installation effectuée:
* Sur l'onglet "**Equipement**", choisissez l'objet parent, et notez le numéro VIN du véhicule (voir sur la carte grise)
  Indiquez également la capacité de la batterie pour un véhicule électrique. (Cela permet d'évaluer la consommation sur un trajet)
Lors de la sauvegarde de l'équipement, quelques photos du véhicules sont téléchargées et rendues disponibles pour affichage sur le widget.
<p align="left">
  <img src="../master/doc/images/config_equipement.png" width="200" title="Widget dashboard">
</p>

## Widget
Le widget est configuré automatiquement par le plugin lors de la création de l'équipement.
Une photo du véhicule doit s'afficher sur le widget. On peut en changer avec les 2 flèches bleues.
Il est possible d'agencer les éléments dans le widgets par la fonction d'édition du dashboard.
Je propose l'agencement suivant comme exemple, en utilisant la présentation en tableau dans Configuration Avancée=>Disposition (voir ci dessous)
Lorsque l'on clique sur la photo, on bascule sur la page "Panel" du plugin associée au véhicule.
<p align="left">
  <img src="../master/doc/images/config_widget.png" width="200" title="Widget dashboard">
</p>

## Panel
Une page de type "panel" est disponible pour le plugin dans le menu Acceuil de jeedom.
Cette page permet de consulter les informations suivantes sur 4 onglets différents:
* Liste des trajets effectués par le véhicule
* Statistiques sur l'utilisation et la consommation du véhicule. (Pas encore développé) 
* Quelques informations sur le véhicule
* Informations sur les visites d'entretien du véhicule recommandées par Peugeot

**Affichage des trajets:**
Il est possible de définir une période soit par 2 dates, soit par des racourcis ('Aujourd'hui', 'hier', 'les 7 derniers jours' ou 'tout'), puis d'afficher l'ensemble des trajets sur cette période. <br>
La suite de la page est mise à jour avec l'affichage des trajets sélectionnés, en tableau et en affichage sur une carte. (Openstreet map)
On peut sélectionner les trajets 1 par 1 dans le tableau pour afficher un seul trajet dans la liste.
Un résumé sur l'ensemble des trajets sélectionnés et donné également sur cette page.
<p align="left">
  <img src="../master/doc/images/panel1.png" width="600" title="Panel1 dashboard">
</p>

**Informations sur le véhicule:**
Quelques informations sont données sur le véhicule
En particulier la dernière version du logiciel disponible
<p align="left">
  <img src="../master/doc/images/panel2.png" width="600" title="Panel2 dashboard">
</p>

**Visites d'entretien:**
Liste des 3 prochaines opérations d'entretien du véhicule, avec leur date ou kilométrage prévisionels
Les opérations principales d'entretion sont données également.
<p align="left">
  <img src="../master/doc/images/panel3.png" width="600" title="Panel3 dashboard">
</p>

**Bugs connus:**
Cette version 0.3 est encore draft. Il y a quelques bugs connus mais non pénalisants
* Javascript erreur : "ReferenceError: L is not defined" (affichée dans la barre de titre de jeedom)
  Problème de temps de chargement de la librairie Leaflet.
  Ce bug empèche l'affichage de la carte dans le widget lors du rafraichissement de la page Dashboard.
  Mais on peut avoir la carte en faisant un refresh du widget.
* Affichage des trajets sur le pannel: On ne peut pas toujours sélectionner un trajet pour affichage sur la carte.
  Il suffit également de faire un refresh de la page.

**Suite prévue pour ce plugin:**
* Ajouter le pilotage de la wallbox Evbox (si elle est connectée en Wifi) afin de gérer une durée de chargement, ainsi que le courant maxi de chargement.
* Ajouter la page des statistiques d'utilisation
* Ajouter le pilotage du préconditionnement du véhicule.
