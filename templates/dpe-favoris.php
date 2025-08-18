<?php
/**
 * Template pour la page des favoris DPE
 * Même design que le DPE panel
 */
if (!defined('ABSPATH')) exit;

$favoris = $context['favoris'] ?? [];
$favoris_handler = $context['favoris_handler'];
$dpe_handler = $context['dpe_handler'];
$atts = $context['atts'] ?? [];
$title = $atts['title'] ?? 'Mes Favoris DPE';

function dpe_class($val) {
    $val = strtoupper(trim($val));
    return in_array($val, ['A','B','C','D','E','F','G']) ? $val : '';
}

/**
 * ✅ NOUVEAU : Fonction pour formater les dates en format dd/MM/YY
 */
function formatDateFr($dateString) {
    if (empty($dateString)) {
        return 'Non spécifié';
    }
    
    // Essayer de parser la date
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    if (!$date) {
        $date = DateTime::createFromFormat('d/m/Y', $dateString);
    }
    if (!$date) {
        $date = new DateTime($dateString);
    }
    
    if (!$date || $date->format('Y') < 1900) {
        return $dateString; // Retourner la chaîne originale si pas de date valide
    }
    
    // Formater en dd/MM/YY
    return $date->format('d/m/Y');
}

/**
 * ✅ NOUVEAU : Fonction pour créer le lien Google Maps
 */
function createGoogleMapsLink($adresse, $codePostal, $commune) {
    if (empty($adresse)) {
        return 'Non disponible';
    }
    
    $adresseSimple = trim($adresse);
    $adresseSimple = preg_replace('/\s+/', ' ', $adresseSimple); // Nettoyer les espaces multiples
    
    if (empty($adresseSimple)) {
        return 'Non disponible';
    }
    
    $mapsUrl = 'https://www.google.com/maps/place/' . urlencode($adresseSimple);
    
    return sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer" class="maps-link" title="Localiser sur Google Maps">Localiser</a>',
        esc_url($mapsUrl)
    );
}
?>

<div class="sci-frontend-wrapper">
    <h1>🏠 <?php echo esc_html($title); ?></h1>

    <!-- ✅ INFORMATION POUR LES UTILISATEURS -->
    <div class="sci-info" style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
        <p style="margin: 0; font-size: 16px; line-height: 1.5;">
            💡 Consultez vos diagnostics de performance énergétique favoris.
        </p>
    </div>
    
    <!-- ✅ STATISTIQUES DES FAVORIS -->
    <div class="sci-default-postal" style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px; margin-bottom: 15px; color: #155724;">
        <p style="margin: 0; font-size: 14px; line-height: 1.4;">
            📊 <strong>Total des favoris :</strong> <?php echo count($favoris); ?> diagnostic(s) sauvegardé(s)
        </p>
    </div>

    <!-- ✅ ZONE DES RÉSULTATS -->
    <div id="dpe-favoris-results">
        <?php if (empty($favoris)): ?>
            <div class="sci-info" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #856404;">
                <p style="margin: 0; font-size: 16px; line-height: 1.5;">
                    📭 Vous n'avez pas encore de favoris DPE. 
                    <a href="<?php echo admin_url('admin.php?page=dpe-panel'); ?>" style="color: #0073aa; text-decoration: underline;">
                        Recherchez des diagnostics DPE
                    </a> pour en ajouter à vos favoris.
                </p>
            </div>
        <?php else: ?>
            <!-- ✅ TABLEAU DES FAVORIS -->
            <table class="sci-table" id="dpe-favoris-table">
                <thead>
                    <tr>
                        <th>Adresse</th>
                        <th>Ville</th>
                        <th>Type d'habitation</th>
                        <th>Surface</th>
                        <th>Étiquette DPE</th>
                        <th>Étiquette GES</th>
                        <th>Date DPE</th>
                        <th>Géolocalisation</th>
                        <th>Supprimer</th>
                    </tr>
                </thead>
                <tbody id="dpe-favoris-tbody">
                    <?php foreach ($favoris as $favori): ?>
                        <tr data-dpe-id="<?php echo esc_attr($favori->dpe_id); ?>">
                            <td class="adresse"><?php echo esc_html($favori->adresse_ban ?: 'Non spécifié'); ?></td>
                            <td class="commune"><?php echo esc_html($favori->nom_commune_ban ?: 'Non spécifié'); ?></td>
                            <td class="type-batiment"><?php echo esc_html($favori->type_batiment ?: 'Non spécifié'); ?></td>
                            <td class="surface"><?php echo $favori->surface_habitable_logement ? esc_html($favori->surface_habitable_logement . ' m²') : 'Non spécifié'; ?></td>
                            <td>
                                <span class="dpe-label <?php echo dpe_class($favori->etiquette_dpe); ?>">
                                    <?php echo esc_html($favori->etiquette_dpe ?: 'Non spécifié'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="ges-label <?php echo dpe_class($favori->etiquette_ges); ?>">
                                    <?php echo esc_html($favori->etiquette_ges ?: 'Non spécifié'); ?>
                                </span>
                            </td>
                            <td class="date-dpe"><?php echo formatDateFr($favori->date_etablissement_dpe); ?></td>
                            <td class="geolocalisation">
                                <?php 
                                // ✅ NOUVEAU : Utiliser la fonction pour créer le lien Google Maps
                                echo createGoogleMapsLink(
                                    $favori->adresse_ban,
                                    $favori->code_postal_ban,
                                    $favori->nom_commune_ban
                                );
                                ?>
                            </td>
                            <td style="text-align:center;">
                                <button type="button" class="btn-remove-favori" title="Supprimer ce favori" onclick="removeFavori('<?php echo esc_js($favori->dpe_id); ?>')" style="background:none;border:none;cursor:pointer;font-size:18px;color:#e30613;">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
/* ✅ NOUVEAU : Styles pour les liens de géolocalisation */
.maps-link {
    color: #0073aa;
    text-decoration: none;
    font-weight: 500;
    font-size: 12px;
}

.maps-link:hover {
    text-decoration: underline;
    color: #005a87;
}

/* Styles pour les étiquettes DPE et GES */
.dpe-label, .ges-label {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 12px;
    text-align: center;
    min-width: 30px;
    display: inline-block;
}

/* Étiquettes DPE */
.dpe-label.A { background-color: #009639 !important; color: white !important; }
.dpe-label.B { background-color: #85bb2f !important; color: white !important; }
.dpe-label.C { background-color: #ffcc02 !important; color: black !important; }
.dpe-label.D { background-color: #f68b1f !important; color: white !important; }
.dpe-label.E { background-color: #e30613 !important; color: white !important; }
.dpe-label.F { background-color: #8b0000 !important; color: white !important; }
.dpe-label.G { background-color: #4a4a4a !important; color: white !important; }

/* Étiquettes GES */
.ges-label.A { background-color: #009639 !important; color: white !important; }
.ges-label.B { background-color: #85bb2f !important; color: white !important; }
.ges-label.C { background-color: #ffcc02 !important; color: black !important; }
.ges-label.D { background-color: #f68b1f !important; color: white !important; }
.ges-label.E { background-color: #e30613 !important; color: white !important; }
.ges-label.F { background-color: #8b0000 !important; color: white !important; }
.ges-label.G { background-color: #4a4a4a !important; color: white !important; }

/* Styles pour les boutons favoris */
.btn-favori {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.2s ease;
    padding: 5px;
    border-radius: 4px;
}

.btn-favori:hover {
    background: #f0f0f0;
}

.btn-favori.is-favori {
    color: #ffd700;
}

.btn-favori:not(.is-favori) {
    color: #ccc;
}

/* Responsive pour le tableau */
@media (max-width: 768px) {
    .sci-table {
        font-size: 12px;
    }
    
    .sci-table th,
    .sci-table td {
        padding: 6px 4px;
    }
    
    .dpe-label, .ges-label {
        font-size: 10px;
        padding: 2px 4px;
    }
    
    .maps-link {
        font-size: 10px;
    }
}
</style>

<script>
function removeFavori(dpeId) {
    if (!confirm('Êtes-vous sûr de vouloir retirer ce diagnostic de vos favoris ?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'dpe_manage_favoris');
    formData.append('operation', 'remove');
    formData.append('nonce', '<?php echo wp_create_nonce("dpe_favoris_nonce"); ?>');
    formData.append('dpe_data', JSON.stringify({numero_dpe: dpeId}));
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Supprimer la ligne du tableau
            const row = document.querySelector(`tr[data-dpe-id="${dpeId}"]`);
            if (row) {
                row.remove();
                
                // Mettre à jour le compteur
                const totalRows = document.querySelectorAll('#dpe-favoris-tbody tr').length;
                const statsElement = document.querySelector('.sci-default-postal p');
                if (statsElement) {
                    statsElement.innerHTML = `📊 <strong>Total des favoris :</strong> ${totalRows} diagnostic(s) sauvegardé(s)`;
                }
                
                // Si plus de favoris, afficher le message
                if (totalRows === 0) {
                    location.reload(); // Recharger pour afficher le message "aucun favori"
                }
            }
        } else {
            alert('Erreur lors de la suppression : ' + (data.data || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        alert('Erreur de connexion : ' + error.message);
    });
}
</script> 