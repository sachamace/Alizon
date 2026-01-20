<?php
// Fonction utilitaire pour parler au serveur C
function envoyer_au_c($message) {
    $host = "10.253.5.108";
    $port = 8080;
    $response = "";
    $login = "alizon";
    $hash_md5 = "e54d588ca06c9f379b36d0b616421376"; 

    // On préfixe le message original (qui contient déjà "CMD;ARGS")
    $message_complet = "$login;$hash_md5;$message";
    // ------------------------------

    $socket = @fsockopen($host, $port, $errno, $errstr, 2);
    if (!$socket) {
        echo "Erreur connexion C: $errstr ($errno)\n";
        return null;
    }

    // On envoie le message formaté avec l'auth
    fwrite($socket, $message_complet);

    // Si on demande la liste, on attend une grosse réponse
    if ($message === "GET_LIST") {
        while (!feof($socket)) {
            $response .= fread($socket, 4096);
        }
    }
    
    fclose($socket);
    return $response;
}

try{
    // 1. Récupérer la liste des commandes via le serveur C
    // Le C renvoie une chaine formatée: "id;etape;statut;priorite|id;etape..."
    $raw_data = envoyer_au_c("GET_LIST");
    
    if (empty($raw_data)) {
        die("Aucune donnée reçue du serveur ou base vide.\n");
    }
    $parties = explode('|', $raw_data, 2);

    // 2. On récupère et "supprime" le booléen
    $monBool = $parties[0]; // Contient "false" ou "true"
    $resteDesDonnees = isset($parties[1]) ? $parties[1] : ""; // Contient la liste des commandes

    // Conversion propre en vrai booléen PHP si besoin
    $estRenvoie = ($monBool === "true");
    // On transforme la string en tableau 
    $rows = explode('|', $resteDesDonnees);
    $commandes = [];
    
    foreach($rows as $row) {
        if(empty($row)) continue;
        $cols = explode(';', $row);
        if(count($cols) >= 4) {
            $commandes[] = [
                'id_commande' => $cols[0],
                'etape'       => (int)$cols[1],
                'statut'      => $cols[2],
                'priorite'    => (int)$cols[3]
            ];
        }
    }
    foreach ($commandes as $commande) {

        $statut = $commande['statut'];
        $details_etape = "NULL";
        $raison = "NULL";
        $chemin_image_refuse = "NULL";
        $priorite = $commande['priorite'];
        $need_update = false;
        $id = $commande['id_commande'];
        $nouvelle_etape = $commande['etape'] + 1;


        if($commande['statut'] === "EN ATTENTE"){
            if(!$estRenvoie){
                $statut = "ENCOURS";
                echo "Commande $id : Mise à jour du statut ENCOURS\n";
                $need_update = true;
                $details_etape = "Création d’un bordereau de livraison";
            }
            else{
                $statut = "EN ATTENTE";
                echo "Commande $id : En Attente \n";
            }
            $priorite = $commande['priorite'] - 1;
            
            $nouvelle_etape = $commande['etape']; 
        }
        elseif ($commande['statut'] === "ENCOURS"){
            $need_update = true;
            $statut = "ENCOURS";
            if($nouvelle_etape == 9){
                
                $choix = rand(1,3);
                switch ($choix) {
                    case 1:
                        $details_etape = "Colis livré en mains propres";
                        $statut = "ACCEPTER";
                        $raison = "";
                        break;
                    case 2:
                        $details_etape = "Colis livré en l’absence du destinataire";
                        $chemin_image_refuse = "test.png";
                        $statut = "ACCEPTER";
                        $raison = "";
                        break;
                    case 3:
                        $raisons = ["Colis endommagé", "Destinataire inconnu", "Refusé par le client"];
                        $details_etape = "Colis refusé";
                        $raison = "Réfusé par le destinataire - Raison : " . $raisons[array_rand($raisons)];
                        $statut = "REFUS";
                        break;
                }
                echo "Commande $id : Passage à l'étape 9 ($details_etape)\n";

            }
            else{
                $etapes_list = ["Création d’un bordereau de livraison", "Prise en charge du colis chez Alizon", "Arrivée chez le transporteur","Départ vers la plateforme régionale.","Arrivée sur la plateforme régionale","Départ vers le centre local","Arrivée au centre local","Départ pour la livraison finale"];
                
                $details_etape = $etapes_list[$nouvelle_etape - 1];

            }
        }

        // Que si on appel il ya une update dans la base de données à envoyer au programme php 
        if ($need_update) {
            
            $details_etape = str_replace(';', ',', $details_etape);
            $raison = str_replace(';', ',', $raison);
            
            // Format: UPDATE;id;etape;statut;priorite;details;raison;image
            $msg = sprintf("UPDATE;%d;%d;%s;%d;%s;%s;%s", 
                $id, 
                $nouvelle_etape, 
                $statut, 
                $priorite, 
                $details_etape, 
                empty($raison) ? "NULL" : $raison, 
                empty($chemin_image_refuse) ? "NULL" : $chemin_image_refuse
            );
            
            envoyer_au_c($msg);
        }
    }
}catch (Exception $e) {
    die("Erreur simulation : " . $e->getMessage());
}


?>