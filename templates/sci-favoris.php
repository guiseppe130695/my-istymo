<?php
/**
 * Template pour la page des favoris SCI
 * Variables attendues dans $context :
 * - $favoris : array des favoris de l'utilisateur
 * - $title : titre de la page (optionnel)
 * - $show_empty_message : afficher le message si vide (optionnel)
 */
?>

<div class="sci-frontend-wrapper">
    <!-- Fallback FontAwesome pour garantir le chargement -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <table id="table-favoris" class="data-table sci-results-table">
        <thead>
            <tr>
                <th class="col-entreprise"><i class="fas fa-building"></i> Dénomination</th>
                <th class="col-dirigeant"><i class="fas fa-user-tie"></i> Dirigeant</th>
                <th class="col-siren"><i class="fas fa-hashtag"></i> SIREN</th>
                <th class="col-adresse"><i class="fas fa-map-marker-alt"></i> Adresse</th>
                <th class="col-ville"><i class="fas fa-city"></i> Ville</th>
                <th class="col-code-postal"><i class="fas fa-mail-bulk"></i> Code Postal</th>
                <th class="col-geolocalisation"><i class="fas fa-map"></i> Géolocalisation</th>
                <th class="col-actions"><i class="fas fa-cogs"></i> Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($favoris)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding: 40px; color: #6c757d;">
                        <?php if ($show_empty_message ?? true): ?>
                            <i class="fas fa-heart" style="font-size: 2em; color: #dee2e6; margin-bottom: 10px; display: block;"></i>
                            Aucun favori pour le moment.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($favoris as $fav): ?>
                    <tr>
                        <td class="col-entreprise">
                            <strong><?php echo esc_html($fav['denomination']); ?></strong>
                        </td>
                        <td class="col-dirigeant">
                            <?php echo esc_html($fav['dirigeant']); ?>
                        </td>
                        <td class="col-siren">
                            <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                <?php echo esc_html($fav['siren']); ?>
                            </code>
                        </td>
                        <td class="col-adresse">
                            <?php echo esc_html($fav['adresse']); ?>
                        </td>
                        <td class="col-ville">
                            <?php echo esc_html($fav['ville']); ?>
                        </td>
                        <td class="col-code-postal">
                            <span style="background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                <?php echo esc_html($fav['code_postal']); ?>
                            </span>
                        </td>
                        <td class="col-geolocalisation">
                            <?php 
                            $maps_query = urlencode($fav['adresse'] . ' ' . $fav['code_postal'] . ' ' . $fav['ville']);
                            $maps_url = 'https://www.google.com/maps/place/' . $maps_query;
                            ?>
                            <a href="<?php echo esc_url($maps_url); ?>" 
                               target="_blank" 
                               class="geolocalisation-link"
                               title="Localiser <?php echo esc_attr($fav['denomination']); ?> sur Google Maps">
                                <i class="fas fa-map-marker-alt"></i> Localiser SCI
                            </a>
                        </td>
                        <td class="col-actions">
                            <button class="delete-favori-btn" 
                                    data-siren="<?php echo esc_attr($fav['siren']); ?>"
                                    title="Supprimer des favoris">
                                <i class="fas fa-trash-alt"></i> Supprimer
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Vérifier si les variables AJAX sont disponibles
    if (typeof sci_ajax === 'undefined') {
        console.warn('Variables AJAX non disponibles pour les favoris');
        return;
    }
    
    document.querySelectorAll('button[data-siren]').forEach(btn => {
        btn.addEventListener('click', () => {
            const siren = btn.getAttribute('data-siren');
            const formData = new FormData();
            formData.append('action', 'sci_manage_favoris');
            formData.append('operation', 'remove');
            formData.append('nonce', sci_ajax.nonce);
            formData.append('sci_data', JSON.stringify({siren: siren}));

            fetch(sci_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Recharger la page
                } else {
                    alert('Erreur lors de la suppression : ' + data.data);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            });
        });
    });
});
</script> 