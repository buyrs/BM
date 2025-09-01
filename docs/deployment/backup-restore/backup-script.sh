#!/bin/bash

# Script de sauvegarde complète avant migration
# Usage: ./backup-script.sh [environment] [backup-type]
# 
# environment: production, staging, development
# backup-type: full, database-only, files-only

set -e  # Arrêter le script en cas d'erreur

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
BACKUP_ROOT="/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Paramètres
ENVIRONMENT=${1:-production}
BACKUP_TYPE=${2:-full}
BACKUP_DIR="$BACKUP_ROOT/pre-migration-$ENVIRONMENT-$TIMESTAMP"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions utilitaires
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérification des prérequis
check_prerequisites() {
    log_info "Vérification des prérequis..."
    
    # Vérifier que le répertoire de sauvegarde existe
    if [ ! -d "$BACKUP_ROOT" ]; then
        log_error "Le répertoire de sauvegarde $BACKUP_ROOT n'existe pas"
        exit 1
    fi
    
    # Vérifier l'espace disque disponible
    AVAILABLE_SPACE=$(df "$BACKUP_ROOT" | awk 'NR==2{print $4}')
    REQUIRED_SPACE=5000000  # 5GB en KB
    
    if [ "$AVAILABLE_SPACE" -lt "$REQUIRED_SPACE" ]; then
        log_error "Espace disque insuffisant. Requis: 5GB, Disponible: $(($AVAILABLE_SPACE/1024/1024))GB"
        exit 1
    fi
    
    # Vérifier les outils nécessaires
    for tool in mysqldump tar gzip; do
        if ! command -v $tool &> /dev/null; then
            log_error "L'outil $tool n'est pas installé"
            exit 1
        fi
    done
    
    log_success "Prérequis vérifiés"
}

# Chargement de la configuration Laravel
load_laravel_config() {
    log_info "Chargement de la configuration Laravel..."
    
    cd "$PROJECT_ROOT"
    
    # Charger les variables d'environnement
    if [ -f ".env" ]; then
        export $(grep -v '^#' .env | xargs)
    else
        log_error "Fichier .env non trouvé dans $PROJECT_ROOT"
        exit 1
    fi
    
    # Vérifier les variables critiques
    if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
        log_error "Variables de base de données manquantes dans .env"
        exit 1
    fi
    
    log_success "Configuration Laravel chargée"
}

# Création du répertoire de sauvegarde
create_backup_directory() {
    log_info "Création du répertoire de sauvegarde: $BACKUP_DIR"
    
    mkdir -p "$BACKUP_DIR"
    mkdir -p "$BACKUP_DIR/database"
    mkdir -p "$BACKUP_DIR/files"
    mkdir -p "$BACKUP_DIR/logs"
    
    # Créer un fichier de métadonnées
    cat > "$BACKUP_DIR/backup-info.txt" << EOF
Sauvegarde pré-migration
========================
Date: $(date)
Environnement: $ENVIRONMENT
Type: $BACKUP_TYPE
Projet: $(basename "$PROJECT_ROOT")
Version Laravel: $(cd "$PROJECT_ROOT" && php artisan --version)
Version PHP: $(php --version | head -n1)
Serveur: $(hostname)
Utilisateur: $(whoami)
EOF
    
    log_success "Répertoire de sauvegarde créé"
}

# Sauvegarde de la base de données
backup_database() {
    if [ "$BACKUP_TYPE" = "files-only" ]; then
        log_info "Sauvegarde de la base de données ignorée (type: files-only)"
        return
    fi
    
    log_info "Sauvegarde de la base de données..."
    
    local db_backup_file="$BACKUP_DIR/database/database-$TIMESTAMP.sql"
    local db_structure_file="$BACKUP_DIR/database/structure-$TIMESTAMP.sql"
    
    # Sauvegarde complète avec données
    log_info "Sauvegarde des données..."
    mysqldump \
        --host="$DB_HOST" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-table \
        --add-locks \
        --extended-insert \
        "$DB_DATABASE" > "$db_backup_file"
    
    # Compression de la sauvegarde
    gzip "$db_backup_file"
    
    # Sauvegarde de la structure seule (pour référence rapide)
    log_info "Sauvegarde de la structure..."
    mysqldump \
        --host="$DB_HOST" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --no-data \
        --routines \
        --triggers \
        --events \
        "$DB_DATABASE" > "$db_structure_file"
    
    # Statistiques de la base de données
    mysql \
        --host="$DB_HOST" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --execute="
            SELECT 
                TABLE_NAME,
                TABLE_ROWS,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'SIZE_MB'
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = '$DB_DATABASE'
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;
        " > "$BACKUP_DIR/database/table-statistics.txt"
    
    log_success "Sauvegarde de la base de données terminée"
}

# Sauvegarde des fichiers
backup_files() {
    if [ "$BACKUP_TYPE" = "database-only" ]; then
        log_info "Sauvegarde des fichiers ignorée (type: database-only)"
        return
    fi
    
    log_info "Sauvegarde des fichiers..."
    
    cd "$PROJECT_ROOT"
    
    # Sauvegarde du dossier storage (uploads, logs, cache)
    log_info "Sauvegarde du dossier storage..."
    tar -czf "$BACKUP_DIR/files/storage-$TIMESTAMP.tar.gz" \
        --exclude="storage/framework/cache/*" \
        --exclude="storage/framework/sessions/*" \
        --exclude="storage/framework/views/*" \
        --exclude="storage/logs/*.log" \
        storage/
    
    # Sauvegarde des fichiers publics (si ils contiennent des uploads)
    if [ -d "public/storage" ] || [ -d "public/uploads" ]; then
        log_info "Sauvegarde des fichiers publics..."
        tar -czf "$BACKUP_DIR/files/public-uploads-$TIMESTAMP.tar.gz" \
            public/storage/ public/uploads/ 2>/dev/null || true
    fi
    
    # Sauvegarde des fichiers de configuration
    log_info "Sauvegarde des fichiers de configuration..."
    tar -czf "$BACKUP_DIR/files/config-$TIMESTAMP.tar.gz" \
        .env \
        config/ \
        composer.json \
        composer.lock \
        package.json \
        package-lock.json 2>/dev/null || true
    
    # Sauvegarde des migrations et seeders
    log_info "Sauvegarde des migrations et seeders..."
    tar -czf "$BACKUP_DIR/files/database-files-$TIMESTAMP.tar.gz" \
        database/migrations/ \
        database/seeders/ \
        database/factories/ 2>/dev/null || true
    
    log_success "Sauvegarde des fichiers terminée"
}

# Sauvegarde des logs système
backup_logs() {
    log_info "Sauvegarde des logs..."
    
    # Logs Laravel
    if [ -d "$PROJECT_ROOT/storage/logs" ]; then
        cp -r "$PROJECT_ROOT/storage/logs"/* "$BACKUP_DIR/logs/" 2>/dev/null || true
    fi
    
    # Logs système (si accessibles)
    if [ -r "/var/log/nginx" ]; then
        mkdir -p "$BACKUP_DIR/logs/nginx"
        cp /var/log/nginx/*.log "$BACKUP_DIR/logs/nginx/" 2>/dev/null || true
    fi
    
    if [ -r "/var/log/apache2" ]; then
        mkdir -p "$BACKUP_DIR/logs/apache2"
        cp /var/log/apache2/*.log "$BACKUP_DIR/logs/apache2/" 2>/dev/null || true
    fi
    
    log_success "Sauvegarde des logs terminée"
}

# Vérification de l'intégrité de la sauvegarde
verify_backup() {
    log_info "Vérification de l'intégrité de la sauvegarde..."
    
    local verification_file="$BACKUP_DIR/verification.txt"
    
    echo "Vérification de l'intégrité - $(date)" > "$verification_file"
    echo "========================================" >> "$verification_file"
    
    # Vérifier les fichiers de sauvegarde
    find "$BACKUP_DIR" -type f -name "*.gz" -o -name "*.sql" | while read file; do
        if [ -f "$file" ]; then
            size=$(du -h "$file" | cut -f1)
            echo "✓ $file ($size)" >> "$verification_file"
        else
            echo "✗ $file (manquant)" >> "$verification_file"
        fi
    done
    
    # Test de décompression des archives
    find "$BACKUP_DIR" -name "*.tar.gz" | while read archive; do
        if tar -tzf "$archive" >/dev/null 2>&1; then
            echo "✓ Archive valide: $(basename "$archive")" >> "$verification_file"
        else
            echo "✗ Archive corrompue: $(basename "$archive")" >> "$verification_file"
        fi
    done
    
    # Test de la sauvegarde SQL
    if [ -f "$BACKUP_DIR/database/database-$TIMESTAMP.sql.gz" ]; then
        if zcat "$BACKUP_DIR/database/database-$TIMESTAMP.sql.gz" | head -n 10 | grep -q "MySQL dump"; then
            echo "✓ Sauvegarde SQL valide" >> "$verification_file"
        else
            echo "✗ Sauvegarde SQL invalide" >> "$verification_file"
        fi
    fi
    
    log_success "Vérification terminée - voir $verification_file"
}

# Génération du rapport de sauvegarde
generate_report() {
    log_info "Génération du rapport de sauvegarde..."
    
    local report_file="$BACKUP_DIR/backup-report.txt"
    
    cat > "$report_file" << EOF
RAPPORT DE SAUVEGARDE PRÉ-MIGRATION
===================================

Informations générales:
- Date: $(date)
- Environnement: $ENVIRONMENT
- Type de sauvegarde: $BACKUP_TYPE
- Répertoire: $BACKUP_DIR
- Durée: $(($(date +%s) - START_TIME)) secondes

Taille de la sauvegarde:
$(du -sh "$BACKUP_DIR")

Contenu de la sauvegarde:
$(find "$BACKUP_DIR" -type f -exec ls -lh {} \; | awk '{print $5 " " $9}')

Statistiques de la base de données:
$(cat "$BACKUP_DIR/database/table-statistics.txt" 2>/dev/null || echo "Non disponible")

Vérification d'intégrité:
$(cat "$BACKUP_DIR/verification.txt" 2>/dev/null || echo "Non effectuée")

Instructions de restauration:
1. Base de données: zcat $BACKUP_DIR/database/database-$TIMESTAMP.sql.gz | mysql -u [user] -p [database]
2. Fichiers: tar -xzf $BACKUP_DIR/files/[archive].tar.gz -C [destination]

IMPORTANT: Testez la restauration sur un environnement de test avant utilisation en production.
EOF
    
    log_success "Rapport généré: $report_file"
}

# Nettoyage des anciennes sauvegardes
cleanup_old_backups() {
    log_info "Nettoyage des anciennes sauvegardes..."
    
    # Garder les 5 dernières sauvegardes
    find "$BACKUP_ROOT" -maxdepth 1 -type d -name "pre-migration-$ENVIRONMENT-*" | \
        sort -r | tail -n +6 | while read old_backup; do
        log_warning "Suppression de l'ancienne sauvegarde: $(basename "$old_backup")"
        rm -rf "$old_backup"
    done
    
    log_success "Nettoyage terminé"
}

# Fonction principale
main() {
    START_TIME=$(date +%s)
    
    echo "========================================"
    echo "  SAUVEGARDE PRÉ-MIGRATION"
    echo "========================================"
    echo "Environnement: $ENVIRONMENT"
    echo "Type: $BACKUP_TYPE"
    echo "Destination: $BACKUP_DIR"
    echo "========================================"
    
    check_prerequisites
    load_laravel_config
    create_backup_directory
    
    backup_database
    backup_files
    backup_logs
    
    verify_backup
    generate_report
    cleanup_old_backups
    
    local end_time=$(date +%s)
    local duration=$((end_time - START_TIME))
    
    log_success "Sauvegarde terminée avec succès !"
    log_info "Durée totale: ${duration}s"
    log_info "Emplacement: $BACKUP_DIR"
    
    echo ""
    echo "Prochaines étapes:"
    echo "1. Vérifiez le rapport: $BACKUP_DIR/backup-report.txt"
    echo "2. Testez la restauration sur un environnement de test"
    echo "3. Procédez à la migration"
}

# Gestion des signaux (Ctrl+C)
trap 'log_error "Sauvegarde interrompue par l'\''utilisateur"; exit 1' INT TERM

# Exécution du script principal
main "$@"