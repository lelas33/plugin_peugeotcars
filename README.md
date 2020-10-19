# plugin-PeugeotCars

## Fonctions

Ce plugin permet d'afficher les trajets réalisés par votre véhicule peugeot par importation des fichiers "*.myp" issus de l'application MyPeugeot.
(En attendant la version connectée)

L'importation des fichiers "*.myp" permet:
* D'afficher dans un widget le kilométrage de la voiture, la distance et le nombre de jour jusqu'au prochain entretien.
* D'afficher sur la page "Panel" l'ensemble des trajets réalisés par la voiture:
  * Sélection des trajets entre 2 dates
  * Affichage d'une liste des trajets dans une table
  * Affichage de 3 graphes pour les trajets (Distances, Durées, Vitesse moyenne)

<p align="left">
  <img src="../master/doc/images/widget.png" width="200" title="Widget dashboard">
</p>

## Versions
* Tag pgc_v0.1: Version draft originale du 19/10/2020
* Tag pgc_v0.2: Version draft avec correction plantage Jeedom (20/10/2020)

## Installation
Par source Github:
* Aller dans Jeedom menu Plugins / Gestion des plugins
* Sélectionner le symbole + (Ajouter un plugin)
* Sélectionner le type de source Github (Il faut l'avoir autorisé au préalable dans le menu Réglages / Système / Configuration => Mise à jour/Market)
* Remplir les champs:
**  ID logique du plugin : peugeotcars
**  Utilisateur ou organisation du dépôt : lelas33
**  Nom du dépôt : plugin_peugeotcars
**  Branche : master
* Aller dans le menu "plugins/objets connectés/Voitures Peugeot" de jeedom pour installer le nouveau plugin.
Sur la page configuration du plugin, pas besoin des identifiant pour le moment, et cochez la case :"Afficher le panneau desktop". Cela donne accès à la page du "panel" de l'équipement.

## Configuration
Une fois l'installation effectuée:
* Sur l'onglet "**Equipement**", choisissez l'objet parent, et notez le numéro VIN du véhicule (voir sur la carte grise)

Pour le moment, la photo du véhicule n'est pas récupérée automatiquement.
Il faut la copier depuis le site MyPeugeot, et la sauvegarder dans le dossier "ressources" du plugin, avec le nom : "vin.png" (vin = numéro du véhicule)

## Widget
Le widget est configuré automatiquement par le plugin lors de la création de l'équipement.
La photo du véhicule doit s'afficher sur le widget si le fichier photo précédent est bien présent dans le dossier "ressources"

## Panel
Une page de type "panel" est disponible pour le plugin dans le menu Acceuil de jeedom.
Cette page permet de consulter les informations de trajets du véhicule.

**Mise à jour de la base de trajets:**
Pour importer les fichiers de trajets issus de l'appli "MyPeugeot", il faut exporter les trajets depuis l'appli au format myp, puis copier les fichiers dans le dossier "data\MyPeugeot" du plugin.
Ensuite, sélectionner le bouton "Mise à jour de la base": Cela va importer les trajets trouvés dans tous les fichiers ".myp" présents dans ce dossier.
S'il y a plusieurs utilisateurs de la voiture avec plusieurs téléphones différents, cela permet de combiner les trajets dans une base unique.

**Affichage des trajets:**
Il est possible de définir une période soit par 2 dates, soit par des racourcis ('Aujourd'hui', 'hier', 'les 7 derniers jours' ou 'tout'), puis d'afficher l'ensemble des trajets sur cette période. <br>
La suite de la page est mise à jour avec l'affichage des trajets sélectionnés, en tableau et en graphique.

<p align="left">
  <img src="../master/doc/images/panel1.png" width="600" title="Panel1 dashboard">
  <img src="../master/doc/images/panel2.png" width="600" title="Panel2 dashboard">
</p>
