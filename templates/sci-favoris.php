<?php
/**
 * Template pour la page des favoris SCI
 * Variables attendues dans $context :
 * - $favoris : array des favoris de l'utilisateur
 */
?>

<div class="wrap">
    <h1>⭐ Mes SCI Favoris</h1>
    <table id="table-favoris" class="widefat fixed striped">
        <thead>
            <tr>
                <th>Dénomination</th>
                <th>Dirigeant</th>
                <th>SIREN</th>
                <th>Adresse</th>
                <th>Ville</th>
                <th>Code Postal</th>
                <th>Géolocalisation</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($favoris)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">Aucun favori pour le moment.</td>
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
                            <!-- ✅ LIEN GOOGLE MAPS -->
                            <?php 
                            $maps_query = urlencode($fav['adresse'] . ' ' . $fav['code_postal'] . ' ' . $fav['ville']);
                            $maps_url = 'https://www.google.com/maps/place/' . $maps_query;
                            ?>
                            <a href="<?php echo esc_url($maps_url); ?>" 
                               target="_blank" 
                               class="maps-link"
                               title="Localiser <?php echo esc_attr($fav['denomination']); ?> sur Google Maps">
                                Localiser SCI
                            </a>
                        </td>
                        <td>
                            <button style="background:#000064!important;" class="remove-fav-btn button button-small" 
                                    data-siren="<?php echo esc_attr($fav['siren']); ?>">
                                🗑️ Supprimer
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
    document.querySelectorAll('.remove-fav-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce favori ?')) {
                return;
            }

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