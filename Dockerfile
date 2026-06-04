# ============================================
# CORRECTION DES PERMISSIONS POUR WWW-DATA
# ============================================

# Changer le propriétaire des dossiers storage et cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# S'assurer que PHP-FPM utilise le bon utilisateur
RUN sed -i 's/user = www-data/user = www-data/g' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = www-data/g' /usr/local/etc/php-fpm.d/www.conf

# Créer le fichier log avec les bonnes permissions
RUN touch /var/www/html/storage/logs/laravel.log && \
    chown www-data:www-data /var/www/html/storage/logs/laravel.log && \
    chmod 664 /var/www/html/storage/logs/laravel.log
