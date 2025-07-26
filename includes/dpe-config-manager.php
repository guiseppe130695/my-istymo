<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire de configuration DPE
 */
class DPE_Config_Manager {
    
    private static $instance = null;
    private $config_option_name = 'dpe_config';
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_config_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Ajouter le menu de configuration
     */
    public function add_config_menu() {
        add_submenu_page(
            'dpe-panel',
            'Configuration DPE',
            'Configuration',
            'manage_options',
            'dpe-config',
            array($this, 'config_page')
        );
    }
    
    /**
     * Enregistrer les paramètres
     */
    public function register_settings() {
        register_setting(
            'dpe_config_group',
            $this->config_option_name,
            array($this, 'sanitize_config')
        );
    }
    
    /**
     * Sanitizer pour la configuration
     */
    public function sanitize_config($input) {
        $sanitized = array();
        
        if (isset($input['laposte_token'])) {
            $sanitized['laposte_token'] = sanitize_text_field($input['laposte_token']);
        }
        
        if (isset($input['laposte_api_url'])) {
            $sanitized['laposte_api_url'] = esc_url_raw($input['laposte_api_url']);
        }
        
        // Paramètres La Poste
        $laposte_fields = array(
            'laposte_type_affranchissement',
            'laposte_type_enveloppe',
            'laposte_enveloppe',
            'laposte_couleur',
            'laposte_recto_verso',
            'laposte_placement_adresse',
            'laposte_surimpression_adresses',
            'laposte_impression_expediteur',
            'laposte_ar_scan',
            'laposte_ar_champ1',
            'laposte_ar_champ2',
            'laposte_reference',
            'laposte_nom_entite',
            'laposte_nom_dossier',
            'laposte_nom_sousdossier'
        );
        
        foreach ($laposte_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Page de configuration
     */
    public function config_page() {
        $config = $this->get_config();
        ?>
        <div class="wrap">
            <h1>Configuration DPE</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('dpe_config_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="laposte_token">Token API La Poste</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="laposte_token" 
                                   name="<?php echo $this->config_option_name; ?>[laposte_token]" 
                                   value="<?php echo esc_attr($config['laposte_token'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="Votre clé API La Poste" />
                            <p class="description">Clé API pour le service postal La Poste</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="laposte_api_url">URL API La Poste</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="laposte_api_url" 
                                   name="<?php echo $this->config_option_name; ?>[laposte_api_url]" 
                                   value="<?php echo esc_attr($config['laposte_api_url'] ?? 'https://sandbox-api.servicepostal.com/lettres'); ?>" 
                                   class="regular-text" />
                            <p class="description">URL de l'API La Poste (sandbox ou production)</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Paramètres d'envoi La Poste</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Type d'affranchissement</th>
                        <td>
                            <select name="<?php echo $this->config_option_name; ?>[laposte_type_affranchissement]">
                                <option value="lrar" <?php selected($config['laposte_type_affranchissement'] ?? 'lrar', 'lrar'); ?>>LRAR</option>
                                <option value="lettre" <?php selected($config['laposte_type_affranchissement'] ?? 'lrar', 'lettre'); ?>>Lettre simple</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Type d'enveloppe</th>
                        <td>
                            <select name="<?php echo $this->config_option_name; ?>[laposte_type_enveloppe]">
                                <option value="auto" <?php selected($config['laposte_type_enveloppe'] ?? 'auto', 'auto'); ?>>Automatique</option>
                                <option value="c4" <?php selected($config['laposte_type_enveloppe'] ?? 'auto', 'c4'); ?>>C4</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Couleur</th>
                        <td>
                            <select name="<?php echo $this->config_option_name; ?>[laposte_couleur]">
                                <option value="nb" <?php selected($config['laposte_couleur'] ?? 'nb', 'nb'); ?>>Noir et blanc</option>
                                <option value="couleur" <?php selected($config['laposte_couleur'] ?? 'nb', 'couleur'); ?>>Couleur</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Recto/verso</th>
                        <td>
                            <select name="<?php echo $this->config_option_name; ?>[laposte_recto_verso]">
                                <option value="rectoverso" <?php selected($config['laposte_recto_verso'] ?? 'rectoverso', 'rectoverso'); ?>>Recto-verso</option>
                                <option value="recto" <?php selected($config['laposte_recto_verso'] ?? 'rectoverso', 'recto'); ?>>Recto uniquement</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Récupérer la configuration
     */
    public function get_config() {
        $config = get_option($this->config_option_name, array());
        
        // Valeurs par défaut
        $defaults = array(
            'laposte_api_url' => 'https://sandbox-api.servicepostal.com/lettres',
            'laposte_type_affranchissement' => 'lrar',
            'laposte_type_enveloppe' => 'auto',
            'laposte_enveloppe' => 'fenetre',
            'laposte_couleur' => 'nb',
            'laposte_recto_verso' => 'rectoverso',
            'laposte_placement_adresse' => 'insertion_page_adresse',
            'laposte_surimpression_adresses' => 1,
            'laposte_impression_expediteur' => 0,
            'laposte_ar_scan' => 1
        );
        
        return wp_parse_args($config, $defaults);
    }
    
    /**
     * Récupérer une valeur de configuration
     */
    public function get($key, $default = '') {
        $config = $this->get_config();
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    /**
     * Vérifier si la configuration est complète
     */
    public function is_configured() {
        $laposte_token = $this->get_laposte_token();
        return !empty($laposte_token);
    }
    
    /**
     * Récupérer le token La Poste
     */
    public function get_laposte_token() {
        return $this->get('laposte_token');
    }
    
    /**
     * Récupérer l'URL de l'API La Poste
     */
    public function get_laposte_api_url() {
        return $this->get('laposte_api_url');
    }
    
    /**
     * Récupérer tous les paramètres La Poste formatés pour l'API
     */
    public function get_laposte_payload_params() {
        $params = array(
            'type_affranchissement' => $this->get_laposte_type_affranchissement(),
            'type_enveloppe' => $this->get_laposte_type_enveloppe(),
            'enveloppe' => $this->get_laposte_enveloppe(),
            'couleur' => $this->get_laposte_couleur(),
            'recto_verso' => $this->get_laposte_recto_verso(),
            'placement_adresse' => $this->get_laposte_placement_adresse(),
            'surimpression_adresses_document' => $this->get_laposte_surimpression_adresses(),
            'impression_expediteur' => $this->get_laposte_impression_expediteur(),
            'ar_scan' => $this->get_laposte_ar_scan()
        );
        
        // Ajouter les champs optionnels s'ils sont remplis
        $ar_champ1 = $this->get_laposte_ar_champ1();
        if (!empty($ar_champ1)) {
            $params['ar_expediteur_champ1'] = $ar_champ1;
        }
        
        $ar_champ2 = $this->get_laposte_ar_champ2();
        if (!empty($ar_champ2)) {
            $params['ar_expediteur_champ2'] = $ar_champ2;
        }
        
        $reference = $this->get_laposte_reference();
        if (!empty($reference)) {
            $params['reference'] = $reference;
        }
        
        $nom_entite = $this->get_laposte_nom_entite();
        if (!empty($nom_entite)) {
            $params['nom_entite'] = $nom_entite;
        }
        
        $nom_dossier = $this->get_laposte_nom_dossier();
        if (!empty($nom_dossier)) {
            $params['nom_dossier'] = $nom_dossier;
        }
        
        $nom_sousdossier = $this->get_laposte_nom_sousdossier();
        if (!empty($nom_sousdossier)) {
            $params['nom_sousdossier'] = $nom_sousdossier;
        }
        
        return $params;
    }
    
    // Méthodes pour récupérer les paramètres individuels
    public function get_laposte_type_affranchissement() {
        return $this->get('laposte_type_affranchissement', 'lrar');
    }
    
    public function get_laposte_type_enveloppe() {
        return $this->get('laposte_type_enveloppe', 'auto');
    }
    
    public function get_laposte_enveloppe() {
        return $this->get('laposte_enveloppe', 'fenetre');
    }
    
    public function get_laposte_couleur() {
        return $this->get('laposte_couleur', 'nb');
    }
    
    public function get_laposte_recto_verso() {
        return $this->get('laposte_recto_verso', 'rectoverso');
    }
    
    public function get_laposte_placement_adresse() {
        return $this->get('laposte_placement_adresse', 'insertion_page_adresse');
    }
    
    public function get_laposte_surimpression_adresses() {
        return (bool) $this->get('laposte_surimpression_adresses', 1);
    }
    
    public function get_laposte_impression_expediteur() {
        return (bool) $this->get('laposte_impression_expediteur', 0);
    }
    
    public function get_laposte_ar_scan() {
        return (bool) $this->get('laposte_ar_scan', 1);
    }
    
    public function get_laposte_ar_champ1() {
        return $this->get('laposte_ar_champ1', '');
    }
    
    public function get_laposte_ar_champ2() {
        return $this->get('laposte_ar_champ2', '');
    }
    
    public function get_laposte_reference() {
        return $this->get('laposte_reference', '');
    }
    
    public function get_laposte_nom_entite() {
        return $this->get('laposte_nom_entite', '');
    }
    
    public function get_laposte_nom_dossier() {
        return $this->get('laposte_nom_dossier', '');
    }
    
    public function get_laposte_nom_sousdossier() {
        return $this->get('laposte_nom_sousdossier', '');
    }
}

// Initialise le gestionnaire de configuration DPE
function dpe_config_manager() {
    return DPE_Config_Manager::get_instance();
} 

// Hook d'initialisation
add_action('plugins_loaded', 'dpe_config_manager');
?> 