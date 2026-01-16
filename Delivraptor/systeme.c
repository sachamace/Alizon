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

#define TAILLE_BUFF 1024

#define TAILLE_BUFF 1024
#define PORT 8080

const char* max_erreur = "ERREUR_PLEIN"; 



void generer_bordereau(char *buffer, int cnx, PGconn *conn, const char *nom_client) {
int nombreAleatoire = rand() % 10000;
    // Nettoyage du nom pour le bordereau (simple)
    char nom_clean[50];
    strncpy(nom_clean, nom_client, 49);
    nom_clean[49] = '\0';
    sprintf(buffer, "%s-%04d", nom_clean, nombreAleatoire);
}

void afficher_man(char *nom_programme) {
    printf("\nUsage: %s [OPTIONS]\n", nom_programme);
    printf("\nDESCRIPTION:\n");
    printf("  Serveur de simulation de livraison 'Délivraptor'.\n");
    printf("  Gère les étapes de transit et créer un bordereau pour la livraison.\n");
    printf("\nOPTIONS:\n");
    printf("  -s, --silent        Désactive l'affichage des logs dans la console.\n");
    printf("  -h, --help          Affiche cette aide et quitte le programme.\n");
    printf("  -c, --cap           Définit la capacité maximale des commandes à étape 1 - 4.\n");

}

// Fonction pour récupérer la liste des commandes et l'envoyer au PHP
void traiter_get_list(int cnx, PGconn *conn) {
    const char *query = "SELECT c.id_commande, c.etape, c.statut, c.priorite FROM commande c";
    PGresult *res = PQexec(conn, query);

    if (PQresultStatus(res) != PGRES_TUPLES_OK) {
        perror("Erreur SELECT");
        PQclear(res);
        return;
    }

    int rows = PQntuples(res);
    char buffer_envoi[TAILLE_BUFF] = "";
    char ligne[256];

    // Format: id;etape;statut;priorite|id;etape...
    for (int i = 0; i < rows; i++) {
        snprintf(ligne, sizeof(ligne), "%s;%s;%s;%s|",
            PQgetvalue(res, i, 0),
            PQgetvalue(res, i, 1),
            PQgetvalue(res, i, 2),
            PQgetvalue(res, i, 3)
        );
        // Vérification débordement tampon (simplifié)
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
    if(!(nb_commandes >= capacite_max)){
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
void traiter_affiche(char *id_str , char*login , int cnx , PGconn*conn ,int verbose){
    char query[1024];
}
int main(int argc, char *argv[]){

    // Variables initialisé pour la base de données 
    const char *conninfo = "host=10.253.5.108 port=5432 dbname=postgres user=sae password=bigouden08";
    int sock;
    int size;
    int ret;
    int cnx;
    int capacite_max ; // Capacité par défault.
    char buf[TAILLE_BUFF];
    struct sockaddr_in conn_addr;
    struct sockaddr_in addr;
    int opt;
    int verbose = false;
    bool cap_argument = false;
    srand(time(NULL));

    static struct option long_options[] = {
        {"time", required_argument,0, 't'},
        {"cap",required_argument,0,'c'},
        {"logs", no_argument,0, 's'},
        {"help", no_argument,0, 'h'},
        {0, 0, 0}
    };


    // 2. Établir la connexion
    PGconn *conn = PQconnectdb(conninfo);

    // Vérifier si la connexion a réussi
    if (PQstatus(conn) != CONNECTION_OK) {
        fprintf(stderr, "Erreur de connexion : %s\n", PQerrorMessage(conn));
        PQfinish(conn);
        exit(1);
    }

    if(verbose)printf("Connexion réussie !\n");


    // Choix des options qu'on peut choisir pour lancer le système.
    while ((opt = getopt_long(argc, argv, "c:sh",long_options, NULL)) != -1) {
        switch (opt) {
            case 's':
                verbose = true; // Affiche les logs .
                break;
            case 'h':
                afficher_man(argv[0]); // Affiche le man du service. 
                exit(EXIT_SUCCESS);
            case 'c':
                capacite_max = atoi(optarg);
                cap_argument = true;
                break;
            default:    
                fprintf(stderr, "Tapez '%s --help' pour plus d'informations.\n", argv[0]); // Scénario d'érreur
                exit(EXIT_FAILURE);
        }
    }
    if(!cap_argument){
        fprintf(stderr, "Erreur : Les options --cap (-c) sont obligatoires.\n");
        fprintf(stderr, "Utilisez --help pour voir l'usage.\n");
        exit(EXIT_FAILURE);
    }
    // --- Vérification des paramètres (Log de démarrage) ---
    if (verbose) {
        printf("=== DÉMARRAGE DU SERVEUR ===\n");
        printf("Capacité Maximale : %d\n", capacite_max);
        printf("============================\n\n");
    }

    // Fonction Socket() - Client et Serveur 
    sock = socket(AF_INET, SOCK_STREAM, 0);
    if(verbose) printf("Création du socket\n");
    if (setsockopt(sock, SOL_SOCKET, SO_REUSEADDR, &opt, sizeof(opt))) {
        perror("setsockopt");
        exit(EXIT_FAILURE);
    }
    // Permet de faire un appel à tout les ips donc 127.0.0.1 et 10.253.5.108
    addr.sin_addr.s_addr = INADDR_ANY;
    addr.sin_family = AF_INET;
    addr.sin_port = htons(PORT);
    ret = bind(sock, (struct sockaddr *)&addr, sizeof(addr));
    if (ret < 0) { perror("bind"); exit(EXIT_FAILURE); } // Utilisation de ret

    ret = listen(sock, 5);

    if (ret < 0) { perror("listen"); exit(EXIT_FAILURE); } // Utilisation de ret
    if(verbose) printf("Serveur C en écoute sur le port %d...\n", PORT);

       while(1){
        if (verbose) printf("\nAttente connexion...\n");
        size = sizeof(conn_addr);
        cnx = accept(sock, (struct sockaddr *)&conn_addr, (socklen_t *)&size);

        if (cnx == -1) { perror("accept"); continue; }

        int len = read(cnx, buf, TAILLE_BUFF - 1);
        if(len > 0){
            buf[len] = '\0';
            buf[strcspn(buf,"\r\n")] = 0;
        }

        if (verbose) printf("Reçu : %s\n", buf);

        char *fin;
        strtol(buf, &fin, 10);
        if (strcmp(buf, "GET_LIST") == 0) {
            // Cas 1 : Le PHP demande la liste des commandes
            traiter_get_list(cnx, conn);
        } 
        else if (strncmp(buf, "UPDATE", 6) == 0) {
            // Cas 2 : Le PHP demande une mise à jour
            traiter_update(buf,capacite_max, conn, verbose);
        }
        else if(buf != fin && strcmp(*fin,"\0") == 0){
            // C'est un ID, on lance la création
            traiter_creation(buf, capacite_max, cnx, conn, verbose);
        }
        else {
            // SI c'est un autre truc que get_list , update ou un int alors envoyer les données pour afficher la commande 
            char *parties[2];
            int i = 0;
            char *token = strtok(buf,"|");
            while (token != NULL && i < 2) {
                parties[i] = token; // On stocke l'adresse de la partie trouvée
                printf("Partie %d : %s\n", i, parties[i]);
                
                i++;
                token = strtok(NULL, "|");
            }  
            traiter_affiche(parties[0],parties[1],cnx,conn,verbose);
        }
        close(cnx);
    }
    return EXIT_SUCCESS;
}