#!/bin/bash

# Script de restauration des données
# Usage: ./restore-script.sh [backup-directory] [restore-type]
# 
# backup-directory: Chemin vers le répertoire de sauvegarde
# restore-type: full, database-only, files-only

set -e  # Arrêter le script en cas d'erreur

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"

# Paramètres
BACKUP_DIR=${1}
RESTORE_TYPE=${2:-full}

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

# Affichage de l'aide
show_help() {
    cat << EOF
Script de Restauration - Système Ops et Bail Mobilité

Usage: $0 [backup-directory] [restore-type]

Arguments:
  backup-directory    Chemin vers le répertoire de sauvegarde
  restore-type       Type de restauration (défaut: full)
                     - full: Restauration complète
                     - database-only: Base de données uniquement
                     - files-only: Fichiers uniquement

Exemples:
  $0 /backups/pre-migration-production-20250109_143025 full
  $0 /backups/pre-migration-staging-20250109_143025 database-only

Options:
  -h, --help         Afficher cette aide
  --dry-run          Simulation sans modifications réelles
  --force            Forcer la restauration sans confirmation

EOF
}

# Vérification des paramètres
check_parameters() {
    if [ "$1" = "-h" ] || [ "$1" = "--help" ]; then
        show_help
        exit 0
    fi
    
    if [ -z "$BACKUP_DIR" ]; then
        log_error "Répertoire de sauvegarde non spécifié"
        show_help
        exit 1
    fi
    
    if [ ! -d "$BACKUP_DIR" ]; then
        log_error "Répertoire de sauvegarde non trouvé: $BACKUP_DIR"
        exit 1
    fi
    
    if [ ! -f "$BACKUP_DIR/backup-info.txt" ]; then
        log_error "Fichier d'information de sauvegarde manquant"
        log_error "Le répertoire spécifié ne semble pas être une sauvegarde valide"
        exit 1
    fi
}

# Affichage des informations de sauvegarde
display_backup_info() {
    log_info "Informations de la sauvegarde:"
    echo "========================================"
    cat "$BACKUP_DIR/backup-info.txt"
    echo "========================================"
    echo ""
}

# Confirmation de l'utilisateur
confirm_restore() {
    if [ "$FORCE_RESTORE" = "true" ]; then
        return 0
    fi
    
    log_warning "ATTENTION: Cette opération va restaurer les données depuis la sauvegarde."
    log_warning "Toutes les données actuelles seront remplacées."
    echo ""
    
    read -p "Êtes-vous sûr de vouloir continuer ? (oui/non): " -r
    if [[ ! $REPLY =~ ^[Oo][Uu][Ii]$ ]]; then
        log_info "Restauration annulée."
        exit 0
    fi
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

# Mise en mode maintenance
enable_maintenance_mode() {
    log_info "Activation du mode maintenance..."
    
    cd "$PROJECT_ROOT"
    php artisan down --message="Restauration en cours" --retry=60
    
    log_success "Mode maintenance activé"
}

# Désactivation du mode maintenance
disable_maintenance_mode() {
    log_info "Désactivation du mode maintenance..."
    
    cd "$PROJECT_ROOT"
    php artisan up
    
    log_success "Mode maintenance désactivé"
}

# Restauration de la base de données
restore_database() {
    if [ "$RESTORE_TYPE" = "files-only" ]; then
        log_info "Restauration de la base de données ignorée (type: files-only)"
        return
    fi
    
    log_info "Restauration de la base de données..."
    
    # Trouver le fichier de sauvegarde SQL
    local sql_backup=$(find "$BACKUP_DIR/database" -name "database-*.sql.gz" | head -n1)
    
    if [ -z "$sql_backup" ]; then
        log_error "Fichier de sauvegarde SQL non trouvé dans $BACKUP_DIR/database"
        exit 1
    fi
    
    log_info "Fichier de sauvegarde trouvé: $(basename "$sql_backup")"
    
    # Créer une sauvegarde de sécurité de la base actuelle
    log_info "Création d'une sauvegarde de sécurité..."
    local safety_backup="/tmp/safety_backup_$(date +%Y%m%d_%H%M%S).sql"
    mysqldump \
        --host="$DB_HOST" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --single-transaction \
        "$DB_DATABASE" > "$safety_backup"
    
    log_info "Sauvegarde de sécurité créée: $safety_backup"
    
    # Restaurer la base de données
    log_info "Restauration en cours..."
    
    # Vider la base de données actuelle
    mysql \
        --host="$DB_HOST" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --execute="DROP DATABASE IF EXISTS $DB_DATABASE; CREATE DATABASE $DB_DATABASE;"
    
    # Restaurer depuis la sauvegarde
    zcat "$sql_backup" | mysql \
        --host="$DB_HOST" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        "$DB_DATABASE"
    
    log_success "Base de données restaurée"
    log_info "Sauvegarde de sécurité conservée: $safety_backup"
}

# Restauration des fichiers
restore_files() {
    if [ "$RESTORE_TYPE" = "database-only" ]; then
        log_info "Restauration des fichiers ignorée (type: database-only)"
        return
    fi
    
    log_info "Restauration des fichiers..."
    
    cd "$PROJECT_ROOT"
    
    # Créer une sauvegarde de sécurité des fichiers actuels
    local safety_dir="/tmp/safety_files_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$safety_dir"
    
    log_info "Création d'une sauvegarde de sécurité des fichiers..."
    if [ -d "storage" ]; then
        cp -r storage "$safety_dir/"
    fi
    if [ -d "public/storage" ]; then
        cp -r public/storage "$safety_dir/public_storage"
    fi
    
    # Restaurer le dossier storage
    local storage_backup=$(find "$BACKUP_DIR/files" -name "storage-*.tar.gz" | head -n1)
    if [ -n "$storage_backup" ]; then
        log_info "Restauration du dossier storage..."
        rm -rf storage/*
        tar -xzf "$storage_backup" --strip-components=1 -C .
    fi
    
    # Restaurer les fichiers publics
    local public_backup=$(find "$BACKUP_DIR/files" -name "public-uploads-*.tar.gz" | head -n1)
    if [ -n "$public_backup" ]; then
        log_info "Restauration des fichiers publics..."
        tar -xzf "$public_backup" -C .
    fi
    
    # Restaurer les fichiers de configuration (optionnel)
    local config_backup=$(find "$BACKUP_DIR/files" -name "config-*.tar.gz" | head -n1)
    if [ -n "$config_backup" ]; then
        log_warning "Fichiers de configuration trouvés dans la sauvegarde"
        read -p "Voulez-vous restaurer les fichiers de configuration ? (oui/non): " -r
        if [[ $REPLY =~ ^[Oo][Uu][Ii]$ ]]; then
            log_info "Restauration des fichiers de configuration..."
            tar -xzf "$config_backup" -C .
        fi
    fi
    
    log_success "Fichiers restaurés"
    log_info "Sauvegarde de sécurité conservée: $safety_dir"
}

# Restauration des permissions
restore_permissions() {
    log_info "Restauration des permissions..."
    
    cd "$PROJECT_ROOT"
    
    # Permissions pour Laravel
    chmod -R 755 storage/
    chmod -R 755 bootstrap/cache/
    
    # Permissions pour les uploads
    if [ -d "public/storage" ]; then
        chmod -R 755 public/storage/
    fi
    
    # Propriétaire (si exécuté en tant que root)
    if [ "$EUID" -eq 0 ]; then
        chown -R www-data:www-data storage/
        chown -R www-data:www-data bootstrap/cache/
        if [ -d "public/storage" ]; then
            chown -R www-data:www-data public/storage/
        fi
    fi
    
    log_success "Permissions restaurées"
}

# Nettoyage et optimisation post-restauration
post_restore_cleanup() {
    log_info "Nettoyage post-restauration..."
    
    cd "$PROJECT_ROOT"
    
    # Nettoyer les caches
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    php artisan route:clear
    
    # Recréer les liens symboliques
    php artisan storage:link
    
    # Optimiser pour la production
    if [ "$APP_ENV" = "production" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi
    
    # Redémarrer les queues
    php artisan queue:restart
    
    log_success "Nettoyage terminé"
}

# Vérification post-restauration
verify_restore() {
    log_info "Vérification de la restauration..."
    
    cd "$PROJECT_ROOT"
    
    local verification_file="/tmp/restore_verification_$(date +%Y%m%d_%H%M%S).txt"
    
    echo "Vérification de la restauration - $(date)" > "$verification_file"
    echo "========================================" >> "$verification_file"
    
    # Test de connexion à la base de données
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';" 2>/dev/null | grep -q "DB OK"; then
        echo "✓ Connexion base de données: OK" >> "$verification_file"
    else
        echo "✗ Connexion base de données: ÉCHEC" >> "$verification_file"
    fi
    
    # Vérifier les tables principales
    local tables=("users" "missions" "bail_mobilites" "contract_templates")
    for table in "${tables[@]}"; do
        local count=$(php artisan tinker --execute="echo DB::table('$table')->count();" 2>/dev/null || echo "0")
        echo "✓ Table $table: $count enregistrements" >> "$verification_file"
    done
    
    # Vérifier les fichiers critiques
    local files=("storage/app" "storage/logs" "bootstrap/cache")
    for file in "${files[@]}"; do
        if [ -d "$file" ]; then
            echo "✓ Répertoire $file: présent" >> "$verification_file"
        else
            echo "✗ Répertoire $file: manquant" >> "$verification_file"
        fi
    done
    
    # Test de l'application
    if php artisan route:list >/dev/null 2>&1; then
        echo "✓ Routes Laravel: OK" >> "$verification_file"
    else
        echo "✗ Routes Laravel: ÉCHEC" >> "$verification_file"
    fi
    
    log_success "Vérification terminée - voir $verification_file"
    
    # Afficher un résumé
    echo ""
    log_info "Résumé de la vérification:"
    grep "✓\|✗" "$verification_file"
}

# Fonction principale
main() {
    START_TIME=$(date +%s)
    
    echo "========================================"
    echo "  RESTAURATION DES DONNÉES"
    echo "========================================"
    echo "Sauvegarde: $BACKUP_DIR"
    echo "Type: $RESTORE_TYPE"
    echo "========================================"
    
    check_parameters "$@"
    display_backup_info
    confirm_restore
    load_laravel_config
    
    enable_maintenance_mode
    
    # Piège pour désactiver le mode maintenance en cas d'interruption
    trap 'log_error "Restauration interrompue"; disable_maintenance_mode; exit 1' INT TERM
    
    restore_database
    restore_files
    restore_permissions
    post_restore_cleanup
    
    disable_maintenance_mode
    
    verify_restore
    
    local end_time=$(date +%s)
    local duration=$((end_time - START_TIME))
    
    log_success "Restauration terminée avec succès !"
    log_info "Durée totale: ${duration}s"
    
    echo ""
    echo "Prochaines étapes:"
    echo "1. Vérifiez le fonctionnement de l'application"
    echo "2. Testez les fonctionnalités critiques"
    echo "3. Surveillez les logs d'erreur"
    echo "4. Informez les utilisateurs de la fin de maintenance"
}

# Gestion des options
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --force)
            FORCE_RESTORE=true
            shift
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            if [ -z "$BACKUP_DIR" ]; then
                BACKUP_DIR="$1"
            elif [ -z "$RESTORE_TYPE" ]; then
                RESTORE_TYPE="$1"
            fi
            shift
            ;;
    esac
done

# Exécution du script principal
main "$@"