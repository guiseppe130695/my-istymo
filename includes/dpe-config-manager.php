<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire de configuration pour l'API DPE ADEME
 */
class DPE_Config_Manager {
    
    private static $instance = null;
    private $config_option_name = 'dpe_api_config';
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Ajouter les hooks pour l'interface d'administration
        add_action('admin_menu', array($this, 'add_config_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Ajoute le menu de configuration DPE dans l'admin
     */
    public function add_config_menu() {
        add_submenu_page(
            'dpe-panel',
            'Configuration API DPE',
            'Configuration DPE',
            'manage_options',
            'dpe-config',
            array($this, 'config_page')
        );
    }
    
    /**
     * Enregistre les paramètres
     */
    public function register_settings() {
        register_setting('dpe_api_settings', $this->config_option_name, array(
            'sanitize_callback' => array($this, 'sanitize_config')
        ));
    }
    
    /**
     * Sanitise les données de configuration
     */
    public function sanitize_config($input) {
        $sanitized = array();
        
        // URLs des pages shortcodes DPE
        if (isset($input['dpe_panel_page_url'])) {
            $sanitized['dpe_panel_page_url'] = esc_url_raw($input['dpe_panel_page_url']);
        }
        
        if (isset($input['dpe_favoris_page_url'])) {
            $sanitized['dpe_favoris_page_url'] = esc_url_raw($input['dpe_favoris_page_url']);
        }
        
        // URL API ADEME
        if (isset($input['ademe_api_url'])) {
            $sanitized['ademe_api_url'] = esc_url_raw($input['ademe_api_url']);
        }
        
        // Paramètres de recherche
        if (isset($input['dpe_default_page_size'])) {
            $sanitized['dpe_default_page_size'] = intval($input['dpe_default_page_size']);
        }
        
        if (isset($input['dpe_max_page_size'])) {
            $sanitized['dpe_max_page_size'] = intval($input['dpe_max_page_size']);
        }
        
        return $sanitized;
    }
    
    /**
     * Page de configuration DPE
     */
    public function config_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
        }
        
        $config = $this->get_config();
        ?>
        <div class="wrap">
            <h1>🏠 Configuration API DPE</h1>
            <p>Configurez ici les paramètres pour l'API DPE de l'ADEME.</p>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('dpe_api_settings');
                do_settings_sections('dpe_api_settings');
                ?>
                
                <!-- URLs des pages shortcodes DPE -->
                <h2>🔗 URLs des pages shortcodes DPE</h2>
                <p>Configurez les liens vers vos pages contenant les shortcodes DPE.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="dpe_panel_page_url">Page principale DPE ([dpe_panel])</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="dpe_panel_page_url" 
                                   name="<?php echo $this->config_option_name; ?>[dpe_panel_page_url]" 
                                   value="<?php echo esc_attr($config['dpe_panel_page_url'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="https://monsite.com/dpe-recherche" />
                            <p class="description">URL complète de la page contenant le shortcode [dpe_panel]</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="dpe_favoris_page_url">Page des favoris DPE ([dpe_favoris])</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="dpe_favoris_page_url" 
                                   name="<?php echo $this->config_option_name; ?>[dpe_favoris_page_url]" 
                                   value="<?php echo esc_attr($config['dpe_favoris_page_url'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="https://monsite.com/mes-favoris-dpe" />
                            <p class="description">URL complète de la page contenant le shortcode [dpe_favoris]</p>
                        </td>
                    </tr>
                </table>
                
                <!-- Configuration API ADEME -->
                <h2>🔧 Configuration API ADEME</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ademe_api_url">URL API ADEME</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="ademe_api_url" 
                                   name="<?php echo $this->config_option_name; ?>[ademe_api_url]" 
                                   value="<?php echo esc_attr($config['ademe_api_url'] ?? 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines'); ?>" 
                                   class="regular-text" />
                            <p class="description">URL de l'API publique DPE de l'ADEME</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="dpe_default_page_size">Taille de page par défaut</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="dpe_default_page_size" 
                                   name="<?php echo $this->config_option_name; ?>[dpe_default_page_size]" 
                                   value="<?php echo esc_attr($config['dpe_default_page_size'] ?? 50); ?>" 
                                   class="small-text" 
                                   min="10" 
                                   max="100" />
                            <p class="description">Nombre de résultats par page par défaut (10-100)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="dpe_max_page_size">Taille de page maximale</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="dpe_max_page_size" 
                                   name="<?php echo $this->config_option_name; ?>[dpe_max_page_size]" 
                                   value="<?php echo esc_attr($config['dpe_max_page_size'] ?? 100); ?>" 
                                   class="small-text" 
                                   min="50" 
                                   max="200" />
                            <p class="description">Nombre maximum de résultats par page (50-200)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Sauvegarder la configuration DPE'); ?>
            </form>
            
            <!-- Bouton de test API -->
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3>🧪 Test de connexion API</h3>
                <p>Testez la connexion à l'API DPE ADEME pour vérifier que tout fonctionne correctement.</p>
                <button type="button" id="test-dpe-api" class="button button-secondary">
                    🔍 Tester la connexion API
                </button>
                <div id="test-result" style="margin-top: 10px; padding: 10px; border-radius: 4px; display: none;"></div>
            </div>
            
            <script>
            document.getElementById('test-dpe-api').addEventListener('click', function() {
                const button = this;
                const resultDiv = document.getElementById('test-result');
                
                button.disabled = true;
                button.textContent = '⏳ Test en cours...';
                resultDiv.style.display = 'none';
                
                // Appel AJAX pour tester l'API
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'test_dpe_api',
                        nonce: '<?php echo wp_create_nonce("test_dpe_api_nonce"); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    button.disabled = false;
                    button.textContent = '🔍 Tester la connexion API';
                    
                    resultDiv.style.display = 'block';
                    if (data.success) {
                        resultDiv.style.backgroundColor = '#d4edda';
                        resultDiv.style.color = '#155724';
                        resultDiv.style.border = '1px solid #c3e6cb';
                        resultDiv.innerHTML = `
                            <strong>✅ ${data.data.message}</strong><br>
                            Structure des données: ${data.data.data_structure.join(', ')}
                        `;
                    } else {
                        resultDiv.style.backgroundColor = '#f8d7da';
                        resultDiv.style.color = '#721c24';
                        resultDiv.style.border = '1px solid #f5c6cb';
                        resultDiv.innerHTML = `
                            <strong>❌ Erreur de test:</strong><br>
                            ${data.data.error}
                        `;
                    }
                })
                .catch(error => {
                    button.disabled = false;
                    button.textContent = '🔍 Tester la connexion API';
                    
                    resultDiv.style.display = 'block';
                    resultDiv.style.backgroundColor = '#f8d7da';
                    resultDiv.style.color = '#721c24';
                    resultDiv.style.border = '1px solid #f5c6cb';
                    resultDiv.innerHTML = `
                        <strong>❌ Erreur de connexion:</strong><br>
                        ${error.message}
                    `;
                });
            });
            </script>
            
            <!-- Informations sur l'API -->
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3>ℹ️ Informations sur l'API DPE ADEME</h3>
                <p><strong>Limitations de l'API :</strong></p>
                <ul>
                    <li>Utilisateur anonyme : 600 requêtes par minute</li>
                    <li>Utilisateur authentifié : 1200 requêtes par minute</li>
                    <li>Vitesse de téléchargement limitée selon le type d'utilisateur</li>
                </ul>
                <p><strong>Données disponibles :</strong></p>
                <ul>
                    <li>Étiquettes DPE et GES</li>
                    <li>Consommations énergétiques</li>
                    <li>Caractéristiques du bâtiment</li>
                    <li>Informations techniques détaillées</li>
                </ul>
                <p><small>Source : <a href="https://data.ademe.fr/datasets/dpe-logements-existants" target="_blank">API publique ADEME</a></small></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Récupère la configuration complète
     */
    public function get_config() {
        $default_config = array(
            'dpe_panel_page_url' => '',
            'dpe_favoris_page_url' => '',
            'ademe_api_url' => 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines',
            'dpe_default_page_size' => 50,
            'dpe_max_page_size' => 100
        );
        
        $saved_config = get_option($this->config_option_name, array());
        return wp_parse_args($saved_config, $default_config);
    }
    
    /**
     * Récupère une valeur de configuration
     */
    public function get($key, $default = '') {
        $config = $this->get_config();
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    /**
     * Vérifie si la configuration est complète
     */
    public function is_configured() {
        $config = $this->get_config();
        return !empty($config['ademe_api_url']);
    }
    
    /**
     * Getters spécifiques
     */
    public function get_dpe_panel_page_url() {
        return $this->get('dpe_panel_page_url');
    }
    
    public function get_dpe_favoris_page_url() {
        return $this->get('dpe_favoris_page_url');
    }
    
    public function get_ademe_api_url() {
        return $this->get('ademe_api_url');
    }
    
    public function get_dpe_default_page_size() {
        return $this->get('dpe_default_page_size', 50);
    }
    
    public function get_dpe_max_page_size() {
        return $this->get('dpe_max_page_size', 100);
    }
}

// Fonction helper pour accéder au gestionnaire
function dpe_config_manager() {
    return DPE_Config_Manager::get_instance();
} 