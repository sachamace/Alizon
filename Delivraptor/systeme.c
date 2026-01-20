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

// --- FONCTOIN D'AUTHENTIFICATION ---

// Vérifie si le couple login/hash reçu correspond exactement au fichier login.txt
int verifier_auth(char *login_recu, char *hash_recu, char *fichier_auth, int verbose) {
    FILE *fp = fopen(fichier_auth, "r");
    if (!fp) {
        if (verbose) perror("Erreur ouverture fichier auth");
        return 0;
    }

    char ligne[256];
    char file_login[100];
    char file_hash[100];
    int auth_ok = 0;

    // Lecture du fichier ligne par ligne
    while (fgets(ligne, sizeof(ligne), fp)) {
        ligne[strcspn(ligne, "\r\n")] = 0; // Nettoyer les retours à la ligne
        
        // Le fichier contient : login;hash
        char *ptr = strtok(ligne, ";");
        if (ptr) strcpy(file_login, ptr);
        
        ptr = strtok(NULL, ";");
        if (ptr) strcpy(file_hash, ptr);

        // COMPARISON DIRECTE (CHAÎNE À CHAÎNE)
        // On compare le login ET le hash reçu avec ceux du fichier
        if (strcmp(login_recu, file_login) == 0 && strcmp(hash_recu, file_hash) == 0) {
            auth_ok = 1;
            break;
        }
    }
    fclose(fp);
    return auth_ok;
}

// --- FONCTIONS MÉTIER---
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

void traiter_get_list(int cnx, PGconn *conn, int capacite_max) {
    PGresult *res = PQexec(conn, "SELECT c.id_commande, c.etape, c.statut, c.priorite FROM commande c");
    PGresult *res_count = PQexec(conn, "SELECT COUNT(*) FROM public.commande WHERE etape < 4;");
    
    bool plein = false;
    char buffer_envoi[TAILLE_BUFF]; 
    int nb_commandes = 0;

    if (PQresultStatus(res_count) == PGRES_TUPLES_OK) {
        nb_commandes = atoi(PQgetvalue(res_count, 0, 0));
    }
    PQclear(res_count);

    if(nb_commandes > capacite_max) plein = true;

    snprintf(buffer_envoi, sizeof(buffer_envoi), "%s|", plein ? "true" : "false");

    int rows = PQntuples(res);
    char ligne[256];

    for (int i = 0; i < rows; i++) {
        snprintf(ligne, sizeof(ligne), "%s;%s;%s;%s|",
            PQgetvalue(res, i, 0), PQgetvalue(res, i, 1),
            PQgetvalue(res, i, 2), PQgetvalue(res, i, 3)
        );
        if (strlen(buffer_envoi) + strlen(ligne) < TAILLE_BUFF - 1) {
            strcat(buffer_envoi, ligne);
        }
    }
    PQclear(res);
    send(cnx, buffer_envoi, strlen(buffer_envoi), 0);
}

// Fonction pour parser et exécuter l'UPDATE reçu du PHP
void traiter_update(char *buffer, int capacite_max, PGconn *conn, int verbose) {
    // Protocole attendu: id;etape;statut;priorite;details;raison;image
    // NOTE : Le "UPDATE" a déjà été retiré dans le main() !

    char *saveptr;
    char query[2048];
    
    // Vérifier la capacité maximale.
    PGresult *res_count = PQexec(conn, "SELECT COUNT(*) FROM public.commande WHERE etape <= 4;");
    int nb_commandes = 0;
    if (PQresultStatus(res_count) == PGRES_TUPLES_OK) {
        nb_commandes = atoi(PQgetvalue(res_count, 0, 0));
    }
    PQclear(res_count);

    // --- CORRECTION ICI : ON NE SAUTE PLUS RIEN ---
    // On attaque directement la récupération de l'ID
    char *id = strtok_r(buffer, ";", &saveptr); 
    // ----------------------------------------------

    char *etape = strtok_r(NULL, ";", &saveptr);
    char *statut = strtok_r(NULL, ";", &saveptr);
    char *prio = strtok_r(NULL, ";", &saveptr);
    char *details = strtok_r(NULL, ";", &saveptr);
    char *raison = strtok_r(NULL, ";", &saveptr);
    char *image = strtok_r(NULL, ";", &saveptr);

    if (!id) return;

    // ... Le reste de ta fonction reste identique ...
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
    } else {
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

    if (verbose) log_message(query, NULL, verbose); // Petit bonus : log la requête SQL aussi

    PGresult *res_upd = PQexec(conn, query);

    if (PQresultStatus(res_upd) != PGRES_COMMAND_OK) {
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
void traiter_affiche(char *id_cmd, int cnx, PGconn *conn, int verbose) {
    char message_retour[TAILLE_BUFF] = "";
    // On supprime 'ligne[256]' qui était trop petit et dangereux
    char query[512];
    
    // DEBUG : On vérifie quel ID arrive vraiment (attention aux espaces/sauts de ligne)
    if (verbose) printf("--- DEBUG CONSULTATION --- ID reçu : '%s'\n", id_cmd);

    // Construction de la requête
    snprintf(query, sizeof(query), 
        "SELECT date_commande, montant_total_ht, montant_total_ttc, bordereau, statut, etape, date_maj, details_etape, priorite FROM commande WHERE id_commande = '%s';", 
        id_cmd
    );

    if (verbose) printf("SQL : %s\n", query);

    PGresult *res = PQexec(conn, query);
    
    // Vérification du succès de la requête
    if (PQresultStatus(res) == PGRES_TUPLES_OK && PQntuples(res) > 0) {
        
        snprintf(message_retour, sizeof(message_retour), "%s;%s;%s;%s;%s;%s;%s;%s;%s|",
            PQgetvalue(res, 0, 0), // date_commande
            PQgetvalue(res, 0, 1), // montant_total_ht
            PQgetvalue(res, 0, 2), // montant_total_ttc
            PQgetvalue(res, 0, 3), // bordereau
            PQgetvalue(res, 0, 4), // statut
            PQgetvalue(res, 0, 5), // etape
            PQgetvalue(res, 0, 6), // date_maj
            PQgetvalue(res, 0, 7), // details_etape 
            PQgetvalue(res, 0, 8)  // priorite
        );

        if (verbose) printf("Réponse générée : %s\n", message_retour);

    } else {
        // Cas où l'ID n'existe pas ou erreur SQL
        if (verbose) printf("Erreur ou ID introuvable. Status SQL : %s\n", PQresultErrorMessage(res));
        strcpy(message_retour, "NOT_FOUND");
    }

    PQclear(res);
    
    // Envoi au client
    send(cnx, message_retour, strlen(message_retour), 0);
}

// --- FONCTION DE LOG ---
void log_message(const char *msg, struct sockaddr_in *client_addr, int verbose) { // Généré par Gémini le 20 janvir 15:17 
    FILE *fichier_log;
    time_t now = time(NULL);
    struct tm *t = localtime(&now);
    char date_str[64];
    char ip_str[INET_ADDRSTRLEN] = "SERVER"; // Par défaut si pas de client
    char ligne_log[TAILLE_BUFF + 128]; // Buffer pour la ligne complète

    // 1. Formatage de la date : [YYYY-MM-DD HH:MM:SS]
    strftime(date_str, sizeof(date_str), "%Y-%m-%d %H:%M:%S", t);

    // 2. Récupération de l'IP (si un client est connecté)
    if (client_addr != NULL) {
        inet_ntop(AF_INET, &(client_addr->sin_addr), ip_str, INET_ADDRSTRLEN);
    }

    // 3. Construction de la ligne formatée
    snprintf(ligne_log, sizeof(ligne_log), "[%s] [%s] %s", date_str, ip_str, msg);

    // 4. Écriture dans le fichier (Mode "a" pour append/ajout)
    fichier_log = fopen("simulation.log", "a");
    if (fichier_log != NULL) {
        fprintf(fichier_log, "%s\n", ligne_log);
        fclose(fichier_log);
    } else {
        perror("Impossible d'écrire dans le log");
    }

    // 5. Affichage console (seulement si l'option -s est activée)
    if (verbose) {
        printf("%s\n", ligne_log);
    }
}

int main(int argc, char *argv[]) {
    const char *conninfo = "host=10.253.5.108 port=5432 dbname=postgres user=sae password=bigouden08";
    int sock, cnx, opt, port_ecoute = PORT, capacite_max = 0;
    int verbose = 0, cap_arg = 0;
    char *fichier_auth = "login.txt";
    struct sockaddr_in addr, conn_addr;
    socklen_t size;
    char buf[TAILLE_BUFF];

    srand(time(NULL));

    static struct option long_options[] = {
        {"cap", required_argument, 0, 'c'},
        {"port", required_argument, 0, 'p'},
        {"auth", required_argument, 0, 'f'},
        {"logs", no_argument, 0, 's'},
        {"help", no_argument, 0, 'h'},
        {0, 0, 0}
    };

    while ((opt = getopt_long(argc, argv, "c:p:f:sh", long_options, NULL)) != -1) {
        switch (opt) {
            case 's': verbose = 1; break;
            case 'h': afficher_man(argv[0]); exit(0);
            case 'c': capacite_max = atoi(optarg); cap_arg = 1; break;
            case 'p': port_ecoute = atoi(optarg); break;
            case 'f': fichier_auth = optarg; break;
            default: exit(1);
        }
    }

    if (!cap_arg) { fprintf(stderr, "Erreur: --cap obligatoire\n"); exit(1); }

    PGconn *conn = PQconnectdb(conninfo);
    if (PQstatus(conn) != CONNECTION_OK) {
        fprintf(stderr, "DB Error: %s\n", PQerrorMessage(conn));
        exit(1);
    }

    sock = socket(AF_INET, SOCK_STREAM, 0);
    int reuse = 1;
    setsockopt(sock, SOL_SOCKET, SO_REUSEADDR, &reuse, sizeof(reuse));
    
    addr.sin_family = AF_INET;
    addr.sin_addr.s_addr = INADDR_ANY;
    addr.sin_port = htons(port_ecoute);
    
    if (bind(sock, (struct sockaddr *)&addr, sizeof(addr)) < 0) { perror("bind"); exit(1); }
    listen(sock, 5);

    char start_msg[100];
    snprintf(start_msg, sizeof(start_msg), "Démarrage du service sur le port %d (Capacité: %d)", port_ecoute, capacite_max);
    log_message(start_msg, NULL, verbose);

    while (1) {
        size = sizeof(conn_addr);
        cnx = accept(sock, (struct sockaddr *)&conn_addr, &size);
        if (cnx < 0) continue;

        int len = read(cnx, buf, TAILLE_BUFF - 1);
        if (len > 0) {
            buf[len] = '\0';
            buf[strcspn(buf, "\r\n")] = 0;

            if (verbose) printf("Reçu : %s\n", buf);

            // --- PROTOCOLE STRICT : LOGIN;HASH;COMMANDE;ARGS ---
            char *saveptr;
            char *login = strtok_r(buf, ";", &saveptr);
            char *hash = strtok_r(NULL, ";", &saveptr);
            char *cmd = strtok_r(NULL, ";", &saveptr); // Tu récupères "UPDATE"
            char *reste = strtok_r(NULL, "", &saveptr); // Tu récupères TOUT le reste : "7;2;ENCOURS;..."

            // 1. VÉRIFICATION OBLIGATOIRE DE L'AUTH
            if (login && hash && cmd) {
                if (verifier_auth(login, hash, fichier_auth, verbose)) {
                    // 2. DISPATCH DES COMMANDES
                    if (strcmp(cmd, "GET_LIST") == 0) {
                        traiter_get_list(cnx, conn, capacite_max);
                    } 
                    else if (strcmp(cmd, "UPDATE") == 0) {
                        if (reste) traiter_update(reste, capacite_max, conn, verbose);
                    }
                    else if (strcmp(cmd, "NEW") == 0) {
                        if (reste) traiter_creation(reste, capacite_max, cnx, conn, verbose);
                    }
                    else if (strcmp(cmd, "CHECK") == 0) {
                        if (reste) traiter_affiche(reste, cnx, conn, verbose);
                    }
                    else {
                        send(cnx, "ERROR|Commande Inconnue", 23, 0);
                    }
                } else {
                    if (verbose) printf(" > Echec Auth : %s\n", login);
                    send(cnx, "ERROR|Authentification Echouee", 28, 0);
                }
            } else {
                send(cnx, "ERROR|Format Protocole Invalide", 31, 0);
            }
        }
        close(cnx);
    }
    return 0;
}