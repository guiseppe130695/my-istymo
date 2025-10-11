<?php
/**
 * Page de configuration pour les leads vendeur
 */

if (!defined('ABSPATH')) exit;

function lead_vendeur_config_page() {
    $config_manager = lead_vendeur_config_manager();
    $config = $config_manager->get_config();
    
    // Traitement du formulaire de configuration
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['lead_vendeur_config_nonce'], 'lead_vendeur_config')) {
        $new_config = array(
            'gravity_form_id' => intval($_POST['gravity_form_id']),
            'display_fields' => isset($_POST['display_fields']) ? $_POST['display_fields'] : array(),
            'title_field' => isset($_POST['title_field']) ? sanitize_text_field($_POST['title_field']) : '',
            'description_field' => isset($_POST['description_field']) ? sanitize_text_field($_POST['description_field']) : ''
        );
        
        $config_manager->save_config($new_config);
        $config = $new_config;
        
        echo '<div class="notice notice-success"><p>Configuration sauvegardée avec succès !</p></div>';
    }
    
    // Vérifier si Gravity Forms est actif
    if (!$config_manager->is_gravity_forms_active()) {
        echo '<div class="notice notice-error"><p><strong>Gravity Forms n\'est pas actif !</strong> Veuillez installer et activer Gravity Forms pour utiliser cette fonctionnalité.</p></div>';
        return;
    }
    
    $available_forms = $config_manager->get_available_forms();
    $form_fields = array();
    
    if (isset($config['gravity_form_id']) && $config['gravity_form_id'] > 0) {
        $form_fields = $config_manager->get_form_fields($config['gravity_form_id']);
        
        // Debug : afficher les informations sur les champs récupérés
        if (empty($form_fields)) {
            echo '<div class="notice notice-warning"><p><strong>Debug :</strong> Aucun champ trouvé pour le formulaire ID ' . $config['gravity_form_id'] . '. Vérifiez que le formulaire existe et contient des champs.</p></div>';
        } else {
            echo '<div class="notice notice-info"><p><strong>Debug :</strong> ' . count($form_fields) . ' champ(s) trouvé(s) pour le formulaire ID ' . $config['gravity_form_id'] . '.</p></div>';
        }
    }
    ?>
    
    <div class="wrap">
        <h1>⚙️ Configuration Lead Vendeur</h1>
        
        <div class="my-istymo-container">
            <div class="my-istymo-card">
                <h2>📋 Configuration du Formulaire</h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('lead_vendeur_config', 'lead_vendeur_config_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gravity_form_id">Formulaire Gravity Forms</label>
                            </th>
                            <td>
                                <select name="gravity_form_id" id="gravity_form_id" onchange="loadFormFields()">
                                    <option value="0">-- Sélectionner un formulaire --</option>
                                    <?php foreach ($available_forms as $form_id => $form_title): ?>
                                        <option value="<?php echo esc_attr($form_id); ?>" 
                                                <?php selected(isset($config['gravity_form_id']) ? $config['gravity_form_id'] : 0, $form_id); ?>>
                                            <?php echo esc_html($form_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Sélectionnez le formulaire Gravity Forms à utiliser pour les leads vendeur.</p>
                            </td>
                        </tr>
                        
                        <?php if (!empty($form_fields)): ?>
                        <tr>
                            <th scope="row">
                                <label>Champs à afficher dans le tableau</label>
                            </th>
                            <td>
                                <div id="form-fields-container">
                                    <?php foreach ($form_fields as $field_id => $field): ?>
                                        <label style="display: block; margin-bottom: 5px;">
                                            <input type="checkbox" name="display_fields[]" value="<?php echo esc_attr($field_id); ?>"
                                                   <?php checked(in_array($field_id, isset($config['display_fields']) ? $config['display_fields'] : array())); ?>>
                                            <strong><?php echo esc_html($field['label']); ?></strong>
                                            <small>(ID: <?php echo esc_html($field_id); ?>, Type: <?php echo esc_html($field['type']); ?>)</small>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description">Cochez les champs que vous souhaitez afficher dans le tableau des leads.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="title_field">Champ pour le titre</label>
                            </th>
                            <td>
                                <select name="title_field" id="title_field">
                                    <option value="">-- Aucun --</option>
                                    <?php foreach ($form_fields as $field_id => $field): ?>
                                        <option value="<?php echo esc_attr($field_id); ?>" 
                                                <?php selected(isset($config['title_field']) ? $config['title_field'] : '', $field_id); ?>>
                                            <?php echo esc_html($field['label']); ?> (ID: <?php echo esc_html($field_id); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Champ qui servira de titre principal pour chaque lead.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="description_field">Champ pour la description</label>
                            </th>
                            <td>
                                <select name="description_field" id="description_field">
                                    <option value="">-- Aucun --</option>
                                    <?php foreach ($form_fields as $field_id => $field): ?>
                                        <option value="<?php echo esc_attr($field_id); ?>" 
                                                <?php selected(isset($config['description_field']) ? $config['description_field'] : '', $field_id); ?>>
                                            <?php echo esc_html($field['label']); ?> (ID: <?php echo esc_html($field_id); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Champ qui servira de description pour chaque lead.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <?php submit_button('Sauvegarder la configuration'); ?>
                </form>
            </div>
            
            <?php if (!empty($form_fields)): ?>
            <div class="my-istymo-card">
                <h2>📊 Aperçu des champs disponibles</h2>
                <div class="form-fields-preview">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Label</th>
                                <th>Type</th>
                                <th>Admin Label</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($form_fields as $field_id => $field): ?>
                                <tr>
                                    <td><code><?php echo esc_html($field_id); ?></code></td>
                                    <td><?php echo esc_html($field['label']); ?></td>
                                    <td><?php echo esc_html($field['type']); ?></td>
                                    <td><?php echo esc_html($field['adminLabel']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function loadFormFields() {
        var formId = document.getElementById('gravity_form_id').value;
        if (formId > 0) {
            location.href = '<?php echo admin_url('admin.php?page=lead-vendeur-config'); ?>&form_id=' + formId;
        }
    }
    </script>
    <?php
}
