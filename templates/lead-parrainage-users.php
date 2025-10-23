<?php
/**
 * Page de gestion des utilisateurs pour les leads parrainage
 */

if (!defined('ABSPATH')) exit;

function lead_parrainage_users_page() {
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        echo '<div class="wrap"><h1>Accès refusé</h1><p>Vous n\'avez pas les permissions nécessaires pour accéder à cette page.</p></div>';
        return;
    }
    
    $config_manager = lead_parrainage_config_manager();
    $favoris_handler = lead_parrainage_favoris_handler();
    
    // Vérifier si Gravity Forms est actif
    if (!$config_manager->is_gravity_forms_active()) {
        echo '<div class="wrap">';
        echo '<h1>Utilisateurs Lead Parrainage</h1>';
        echo '<div class="notice notice-error"><p><strong>Gravity Forms n\'est pas actif !</strong> Veuillez installer et activer Gravity Forms pour utiliser cette fonctionnalité.</p></div>';
        echo '</div>';
        return;
    }
    
    $config = $config_manager->get_config();
    
    // Si aucun formulaire configuré
    if (empty($config['gravity_form_id']) || !isset($config['gravity_form_id'])) {
        echo '<div class="wrap">';
        echo '<h1>Utilisateurs Lead Parrainage</h1>';
        echo '<div class="notice notice-warning"><p><strong>Configuration requise !</strong> Veuillez d\'abord configurer le formulaire Gravity Forms dans la <a href="' . admin_url('admin.php?page=lead-parrainage-config') . '">page de configuration</a>.</p></div>';
        echo '</div>';
        return;
    }
    
    // Récupérer tous les utilisateurs
    $users = get_users(array(
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    // Récupérer les statistiques par utilisateur
    $user_stats = array();
    foreach ($users as $user) {
        $total_entries = $config_manager->get_form_entries_count_for_user($config['gravity_form_id'], $user->ID);
        $favorites_count = $favoris_handler->count_user_favorites($user->ID, $config['gravity_form_id']);
        
        $user_stats[$user->ID] = array(
            'user' => $user,
            'total_entries' => $total_entries,
            'favorites_count' => $favorites_count
        );
    }
    
    // Trier par nombre d'entrées (décroissant)
    uasort($user_stats, function($a, $b) {
        return $b['total_entries'] - $a['total_entries'];
    });
    
    echo '<div class="wrap">';
    echo '<h1>👥 Utilisateurs Lead Parrainage</h1>';
    
    echo '<div class="my-istymo-container">';
    echo '<div class="my-istymo-card">';
    echo '<h2>📊 Statistiques par Utilisateur</h2>';
    
    if (empty($user_stats)) {
        echo '<p>Aucun utilisateur trouvé.</p>';
    } else {
        echo '<div class="users-stats-table">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Utilisateur</th>';
        echo '<th>Email</th>';
        echo '<th>Rôle</th>';
        echo '<th>Total Leads</th>';
        echo '<th>Favoris</th>';
        echo '<th>Dernière Activité</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($user_stats as $user_id => $stats) {
            $user = $stats['user'];
            $last_activity = get_user_meta($user_id, 'last_activity', true);
            $last_activity_formatted = $last_activity ? date('d/m/Y H:i', strtotime($last_activity)) : 'Jamais';
            
            echo '<tr>';
            echo '<td>';
            echo get_avatar($user_id, 32) . ' ';
            echo '<strong>' . esc_html($user->display_name) . '</strong>';
            echo '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html(implode(', ', $user->roles)) . '</td>';
            echo '<td><span class="badge badge-primary">' . $stats['total_entries'] . '</span></td>';
            echo '<td><span class="badge badge-success">' . $stats['favorites_count'] . '</span></td>';
            echo '<td>' . $last_activity_formatted . '</td>';
            echo '<td>';
            echo '<button class="button button-small view-user-leads" data-user-id="' . $user_id . '">Voir les leads</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // CSS pour les statistiques
    echo '<style>
    .users-stats-table {
        margin: 20px 0;
    }
    .badge {
        display: inline-block;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: bold;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 3px;
    }
    .badge-primary {
        background-color: #0073aa;
    }
    .badge-success {
        background-color: #46b450;
    }
    .badge-warning {
        background-color: #ffb900;
    }
    .badge-danger {
        background-color: #dc3232;
    }
    </style>';
    
    // JavaScript pour les actions
    echo '<script>
    jQuery(document).ready(function($) {
        $(".view-user-leads").on("click", function() {
            var userId = $(this).data("user-id");
            var userName = $(this).closest("tr").find("td:first strong").text();
            
            // Créer un modal pour afficher les leads de l\'utilisateur
            var modal = $("<div class=\"user-leads-modal\"><div class=\"user-leads-modal-content\"><div class=\"loading-container\"><div class=\"spinner\"></div><p>Chargement des leads de " + userName + "...</p></div></div></div>");
            $("body").append(modal);
            
            // Requête AJAX pour récupérer les leads de l\'utilisateur
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "lead_parrainage_get_user_leads",
                    user_id: userId,
                    nonce: "' . wp_create_nonce('lead_parrainage_nonce') . '"
                },
                success: function(response) {
                    if (response.success) {
                        modal.find(".user-leads-modal-content").html(response.data);
                    } else {
                        modal.find(".user-leads-modal-content").html("<p>Erreur lors du chargement des leads.</p>");
                    }
                },
                error: function() {
                    modal.find(".user-leads-modal-content").html("<p>Erreur de connexion.</p>");
                }
            });
            
            // Fermer le modal
            modal.on("click", function(e) {
                if (e.target === this) {
                    modal.remove();
                }
            });
            
            modal.on("click", ".user-leads-modal-close", function() {
                modal.remove();
            });
        });
    });
    </script>';
}

