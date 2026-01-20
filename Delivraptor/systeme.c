#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <signal.h>
#include <fcntl.h>
#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <string.h>
#include <time.h>
#include <stdbool.h> 
#include <getopt.h>
#include <postgresql/libpq-fe.h>

#define TAILLE_BUFF 1024
#define PORT 8080

const char* max_erreur = "ERREUR_PLEIN"; 



void generer_bordereau(char *buffer, int cnx, PGconn *conn, const char *nom_client) {
int nombreAleatoire = rand() % 10000;
    // Nettoyage du nom pour le bordereau
    char nom_clean[50];
    strncpy(nom_clean, nom_client, 49);
    nom_clean[49] = '\0';
    sprintf(buffer, "%s-%04d", nom_clean, nombreAleatoire);
}

void afficher_man(char *nom_programme) {
    printf("\nUsage: %s [OPTIONS]\n", nom_programme);
    printf("\nDESCRIPTION:\n");
    printf("  Serveur de simulation de livraison 'Délivraptor'.\n");
    printf("\nOPTIONS:\n");
    printf("  -c, --cap <nb>      (Obligatoire) Capacité maximale de traitement.\n");
    printf("  -p, --port <port>   Port d'écoute (Défaut: 8080).\n");
    printf("  -f, --auth <file>   Chemin du fichier d'authentification (Défaut: login.txt).\n");
    printf("  -s, --logs          Active l'affichage des logs (Verbose).\n");
    printf("  -h, --help          Affiche cette aide.\n");
}

// Fonction pour récupérer la liste des commandes et l'envoyer au PHP
void traiter_get_list(int cnx, PGconn *conn,int capacite_max) {
    const char *query = "SELECT c.id_commande, c.etape, c.statut, c.priorite FROM commande c";
    PGresult *res = PQexec(conn, query);
    PGresult *res_count = PQexec(conn, "SELECT COUNT(*) FROM public.commande WHERE etape < 4;");
    bool statut = false;
    char buffer_envoi[TAILLE_BUFF]; 
    int nb_commandes = 0;
    char ligne[256];

    if (PQresultStatus(res) != PGRES_TUPLES_OK) {
        perror("Erreur SELECT");
        PQclear(res);
        return;
    }
    
    
    if (PQresultStatus(res_count) == PGRES_TUPLES_OK) {
        nb_commandes = atoi(PQgetvalue(res_count, 0, 0));
    }
    PQclear(res_count);

    if(nb_commandes > capacite_max){
        statut = true;
    }

    // On écrit le booléen au tout début du buffer avec un séparateur '|'
    // Résultat dans le buffer : "false|"
    snprintf(buffer_envoi, sizeof(buffer_envoi), "%s|", statut ? "true" : "false");

    // --- PARTIE 2 : AJOUT DES DONNÉES À LA SUITE ---
    int rows = PQntuples(res);

    for (int i = 0; i < rows; i++) {
        snprintf(ligne, sizeof(ligne), "%s;%s;%s;%s|",
            PQgetvalue(res, i, 0),
            PQgetvalue(res, i, 1),
            PQgetvalue(res, i, 2),
            PQgetvalue(res, i, 3)
        );

        // strcat va automatiquement ajouter 'ligne' APRÈS "false|"
        if (strlen(buffer_envoi) + strlen(ligne) < TAILLE_BUFF - 1) {
            strcat(buffer_envoi, ligne);
        }
    }
    
    PQclear(res);
    send(cnx, buffer_envoi, strlen(buffer_envoi), 0);
}

// Fonction pour parser et exécuter l'UPDATE reçu du PHP
void traiter_update(char *buffer,int capacite_max, PGconn *conn, int verbose) {

    // Protocole attendu: UPDATE;id;etape;statut;priorite;details;raison;image
    char *saveptr;
    char query[2048];
    // Vérifier la capacité maximale.
    PGresult *res_count = PQexec(conn, "SELECT COUNT(*) FROM public.commande WHERE etape <= 4;");
    int nb_commandes = 0;
    if (PQresultStatus(res_count) == PGRES_TUPLES_OK) {
        nb_commandes = atoi(PQgetvalue(res_count, 0, 0));
    }
    PQclear(res_count);

    // On saute le mot clé "UPDATE"
    strtok_r(buffer, ";", &saveptr); 

    char *id = strtok_r(NULL, ";", &saveptr);
    char *etape = strtok_r(NULL, ";", &saveptr);
    char *statut = strtok_r(NULL, ";", &saveptr);
    char *prio = strtok_r(NULL, ";", &saveptr);
    char *details = strtok_r(NULL, ";", &saveptr);
    char *raison = strtok_r(NULL, ";", &saveptr);
    char *image = strtok_r(NULL, ";", &saveptr);

    if (!id) return;

    query[0] = '\0';
    // Construction de la requête SQL dynamique
    if(!(nb_commandes > capacite_max)){
        snprintf(query, sizeof(query), 
            "UPDATE public.commande SET etape=%s, statut='%s', priorite=%s, details_etape='%s', raison='%s', chemin_image_refuse='%s', date_maj=NOW() WHERE id_commande=%s;",
            etape ? etape : "0",
            statut ? statut : "ENCOURS",
            prio ? prio : "0",
            details ? details : "",
            (raison && strcmp(raison, "NULL") != 0) ? raison : "", 
            (image && strcmp(image, "NULL") != 0) ? image : "",
            id
        );

    }else{
        snprintf(query, sizeof(query), 
            "UPDATE public.commande SET etape=%s, statut='%s', priorite=%s, details_etape='%s', raison='%s', chemin_image_refuse='%s', date_maj=NOW() WHERE id_commande=%s;",
            etape ? etape : "0",
            statut ? statut : "EN ATTENTE",
            prio ? prio : "0",
            details ? details : "",
            (raison && strcmp(raison, "NULL") != 0) ? raison : "", 
            (image && strcmp(image, "NULL") != 0) ? image : "",
            id
        );
    }

    if (verbose) printf("Exécution SQL: %s\n", query);

    PGresult *res_upd = PQexec(conn, query);

    if (PQresultStatus(res_upd) != PGRES_COMMAND_OK) {
        // On affiche l'erreur textuelle précise fournie par PostgreSQL
        fprintf(stderr, "Erreur lors de la mise à jour : %s\n", PQerrorMessage(conn));
    }
    PQclear(res_upd);
}

void traiter_creation(char *id_str, int capacite_max, int cnx, PGconn *conn, int verbose){
    char bordereau[50];
    char query[1024];
    char message_retour[256];
    char nom_client[100] = "Client";

    // 1. Trouver l'id du client de la commande 
    // Construction de la requête
    snprintf(query, sizeof(query), "SELECT nom FROM public.compte_client JOIN public.commande ON compte_client.id_client = commande.id_client WHERE id_commande = '%s';", id_str);
    PGresult *res = PQexec(conn, query);
    if (PQntuples(res) > 0) {
        // On récupère la valeur sous forme de chaîne de caractères
        strncpy(nom_client, PQgetvalue(res, 0, 0), sizeof(nom_client) - 1);
        if (verbose) printf("Le nom du client est : %s\n", nom_client); 
    } 
    PQclear(res);

    // 2. Générer un bordereau
    generer_bordereau(bordereau,cnx,conn,nom_client);

    // 3. Vérifier la capacité 
    res = PQexec(conn, "SELECT COUNT(*) FROM public.commande WHERE etape <= 4;");
    int nb_commandes = 0;
    if (PQresultStatus(res) == PGRES_TUPLES_OK) {
        nb_commandes = atoi(PQgetvalue(res, 0, 0));
    }
    PQclear(res);

    // 4.Vérifier les cas de la création 
    if(nb_commandes >= capacite_max){
        // --- CAS PLEIN : EN ATTENTE ---
        if (verbose) printf("SYSTÈME PLEIN (%d/%d) -> %s en attente.\n", nb_commandes, capacite_max, id_str);

        // Calculer nouvelle priorité (Max + 1)
        res = PQexec(conn, "SELECT MAX(priorite) FROM public.commande;");
        int max_prio = 0;
        if (PQresultStatus(res) == PGRES_TUPLES_OK){
            max_prio = atoi(PQgetvalue(res, 0, 0));
        }
        PQclear(res);

        snprintf(query, sizeof(query), 
            "UPDATE commande SET etape=1, bordereau='%s', details_etape='Création bordereau (File d''attente)', statut='EN ATTENTE', priorite=%d, date_maj=NOW() WHERE id_commande=%s;",
            bordereau, max_prio + 1, id_str
        );
        snprintf(message_retour, sizeof(message_retour), "EN ATTENTE|%s", bordereau);

    } else {
        // --- CAS NORMAL : ENCOURS ---
        if (verbose) printf("AJOUT OK (%d/%d) -> %s encours.\n", nb_commandes, capacite_max, id_str);

        snprintf(query, sizeof(query), 
            "UPDATE commande SET etape=1, bordereau='%s', details_etape='Création bordereau', statut='ENCOURS', priorite=0, date_maj=NOW() WHERE id_commande=%s;",
            bordereau, id_str
        );

        snprintf(message_retour, sizeof(message_retour), "OK|%s", bordereau);
    }

    // 5.Exécution de l'INSERT
    res = PQexec(conn, query);
    if (PQresultStatus(res) != PGRES_COMMAND_OK) {
        fprintf(stderr, "Erreur INSERT : %s\n", PQerrorMessage(conn));
        snprintf(message_retour, sizeof(message_retour), "ERROR|Erreur SQL");
    }
    PQclear(res);

    send(cnx, message_retour, strlen(message_retour), 0);
}
// Modification de la signature : ajout de char *fichier_auth
void traiter_affiche(char *id_str, char *login, int cnx, PGconn *conn, int verbose, char *fichier_auth){
    char message_retour[TAILLE_BUFF] = ""; 
    FILE* fichier = NULL;
    char chaine[50] = ""; 
    char ligne[256];
    char query[512]; 

    // Utilisation de la variable passée en option
    fichier = fopen(fichier_auth, "r");
    
    if(fichier != NULL){
        if (fgets(chaine, sizeof(chaine), fichier) != NULL) {
            chaine[strcspn(chaine, "\r\n")] = 0;
        }
        fclose(fichier);
    } else {
        if(verbose) printf("ERREUR: Impossible d'ouvrir le fichier d'auth : %s\n", fichier_auth);
        // On continue quand même pour renvoyer une erreur propre au client
    }

    // Le reste de la logique reste identique...
    if(strcmp(chaine, login) == 0){
        // ... (Ton code existant pour la requête SELECT) ...
        snprintf(query, sizeof(query), 
            "SELECT c.bordereau, c.statut, c.etape, c.date_maj, c.details_etape, c.priorite FROM commande c WHERE id_commande = '%s';", 
            id_str
        );
        PGresult *res = PQexec(conn, query);
        if (PQresultStatus(res) != PGRES_TUPLES_OK) {
            if(verbose) perror("Erreur SELECT");
            PQclear(res);
            return;
        }
        int rows = PQntuples(res);

        for (int i = 0; i < rows; i++) {
            snprintf(ligne, sizeof(ligne), "%s;%s;%s;%s;%s;%s|",
                PQgetvalue(res, i, 0),
                PQgetvalue(res, i, 1),
                PQgetvalue(res, i, 2),
                PQgetvalue(res, i, 3),
                PQgetvalue(res, i, 4),
                PQgetvalue(res, i, 5)
            );
            if (strlen(message_retour) + strlen(ligne) < TAILLE_BUFF - 1) {
                strcat(message_retour, ligne);
            }
        }
        PQclear(res);

    } else {
        if(verbose) printf("Echec Auth: Reçu '%s' vs Attendu '%s'\n", login, chaine);
        snprintf(message_retour, sizeof(message_retour), "Echec dans la transaction");
    }
    send(cnx, message_retour, strlen(message_retour), 0);
}
int main(int argc, char *argv[]){

    // Variables initialisées
    const char *conninfo = "host=10.253.5.108 port=5432 dbname=postgres user=sae password=bigouden08";
    int sock, size, ret, cnx;
    int capacite_max = 0; 
    char buf[TAILLE_BUFF];
    struct sockaddr_in conn_addr, addr;
    int opt;
    int verbose = false; // silencieux de base 
    bool cap_argument = false;
    
    
    int port_ecoute = 8080; // Valeur par défaut
    char *fichier_auth = "login.txt"; // Valeur par défaut

    srand(time(NULL));

    // Ajout des options p (port) et f (file/auth)
    static struct option long_options[] = {
        {"cap", required_argument, 0, 'c'},
        {"port", required_argument, 0, 'p'}, 
        {"auth", required_argument, 0, 'f'},
        {"logs", no_argument, 0, 's'},
        {"help", no_argument, 0, 'h'},
        {0, 0, 0}
    };

    // Connexion BDD (Code existant)...
    PGconn *conn = PQconnectdb(conninfo);
    if (PQstatus(conn) != CONNECTION_OK) {
        fprintf(stderr, "Erreur de connexion : %s\n", PQerrorMessage(conn));
        PQfinish(conn);
        exit(1);
    }

    // Gestion des arguments : ajout de p: et f: dans la chaîne courte
    while ((opt = getopt_long(argc, argv, "c:p:f:sh", long_options, NULL)) != -1) {
        switch (opt) {
            case 's': // Afficher les logs en direct.
                verbose = true; 
                break;
            case 'h': // Affiche le man
                afficher_man(argv[0]); 
                exit(EXIT_SUCCESS);
            case 'c': // Gestion de la capacité 
                capacite_max = atoi(optarg);
                cap_argument = true;
                break;
            case 'p': // Gestion du PORT
                port_ecoute = atoi(optarg);
                break;
            case 'f': // Gestion du FICHIER AUTH
                fichier_auth = optarg;
                break;
            default:    
                afficher_man(argv[0]);
                exit(EXIT_FAILURE);
        }
    }

    if(!cap_argument){
        fprintf(stderr, "Erreur : L'option --cap (-c) est obligatoire.\n");
        afficher_man(argv[0]);
        exit(EXIT_FAILURE);
    }

    if (verbose) {
        printf("=== DÉMARRAGE DU SERVEUR ===\n");
        printf("Capacité : %d | Port : %d | Auth : %s\n", capacite_max, port_ecoute, fichier_auth);
        printf("============================\n");
    }

    // Création du socket
    sock = socket(AF_INET, SOCK_STREAM, 0);
    // ... (setsockopt reste pareil) ...
    if (setsockopt(sock, SOL_SOCKET, SO_REUSEADDR, &opt, sizeof(opt))) {
        perror("setsockopt");
        exit(EXIT_FAILURE);
    }

    addr.sin_addr.s_addr = INADDR_ANY;
    addr.sin_family = AF_INET;
    
    // UTILISATION DU PORT VARIABLE
    addr.sin_port = htons(port_ecoute); 
    
    ret = bind(sock, (struct sockaddr *)&addr, sizeof(addr));
    if (ret < 0) { perror("bind"); exit(EXIT_FAILURE); } 

    ret = listen(sock, 5);
    if (ret < 0) { perror("listen"); exit(EXIT_FAILURE); } 
    
    if(verbose) printf("Serveur en écoute sur le port %d...\n", port_ecoute);

    while(1){
        // ... (Accept et Read restent pareils) ...
        size = sizeof(conn_addr);
        cnx = accept(sock, (struct sockaddr *)&conn_addr, (socklen_t *)&size);
        if (cnx == -1) { perror("accept"); continue; }

        int len = read(cnx, buf, TAILLE_BUFF - 1);
        if(len > 0){
            buf[len] = '\0';
            buf[strcspn(buf,"\r\n")] = 0;
        }

        // ... (Switch de traitement) ...
        char *fin;
        strtol(buf, &fin, 10); // Vérif si c'est un entier (ID)
        
        if (strcmp(buf, "GET_LIST") == 0) {
            traiter_get_list(cnx, conn, capacite_max);
        } 
        else if (strncmp(buf, "UPDATE", 6) == 0) {
            traiter_update(buf, capacite_max, conn, verbose);
        }
        else if(buf != fin && *fin == '\0'){
            traiter_creation(buf, capacite_max, cnx, conn, verbose);
        }
        else {
            // APPEL À TRAITER AFFICHE AVEC LE FICHIER
            char *parties[2];
            int i = 0;
            char *token = strtok(buf,"|"); 
            while (token != NULL && i < 2) {
                parties[i] = token; 
                i++;
                token = strtok(NULL, "|");
            }  
            if (i >= 2) { 
                // Passage de fichier_auth ici
                traiter_affiche(parties[0], parties[1], cnx, conn, verbose, fichier_auth);
            } else {
                if(verbose) printf("Format incorrect.\n");
            }
        }
        close(cnx);
    }
    return EXIT_SUCCESS;
}