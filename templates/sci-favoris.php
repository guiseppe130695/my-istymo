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

    <table id="table-favoris" class="sci-table">
        <thead>
            <tr>
                <th>Dénomination</th>
                <th>Dirigeant</th>
                <th>SIREN</th>
                <th>Adresse</th>
                <th>Ville</th>
                <th>Code Postal</th>
                <th>Géolocalisation</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($favoris)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">
                        <?php if ($show_empty_message ?? true): ?>
                            Aucun favori pour le moment.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($favoris as $fav): ?>
                    <tr>
                        <td><?php echo esc_html($fav['denomination']); ?></td>
                        <td><?php echo esc_html($fav['dirigeant']); ?></td>
                        <td><?php echo esc_html($fav['siren']); ?></td>
                        <td><?php echo esc_html($fav['adresse']); ?></td>
                        <td><?php echo esc_html($fav['ville']); ?></td>
                        <td><?php echo esc_html($fav['code_postal']); ?></td>
                        <td>
                            <!-- LIEN GOOGLE MAPS -->
                            <?php 
                            $maps_query = urlencode($fav['adresse'] . ' ' . $fav['code_postal'] . ' ' . $fav['ville']);
                            $maps_url = 'https://www.google.com/maps/place/' . $maps_query;
                            ?>
                            <a href="<?php echo esc_url($maps_url); ?>" 
                               target="_blank" 
                               style="color: #4285f4; text-decoration: none; font-size: 12px;"
                               title="Localiser <?php echo esc_attr($fav['denomination']); ?> sur Google Maps">
                                Localiser SCI
                            </a>
                        </td>
                        <td>
                            <button data-siren="<?php echo esc_attr($fav['siren']); ?>"
                                    style="background: none!important; border: none!important; outline: none!important; box-shadow: none !important; color: #dc3545; font-size: 18px; cursor: pointer; padding: 0;">
                                Supprimer
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