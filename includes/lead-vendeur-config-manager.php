<?php
/**
 * Gestionnaire de configuration pour les leads vendeur
 * Intégration avec Gravity Forms
 */

if (!defined('ABSPATH')) exit;

class Lead_Vendeur_Config_Manager {
    private static $instance = null;
    private $config_key = 'my_istymo_lead_vendeur_config';
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Enregistrer les paramètres de configuration
     */
    public function register_settings() {
        register_setting(
            'my_istymo_lead_vendeur_settings',
            $this->config_key,
            array($this, 'sanitize_config')
        );
    }
    
    /**
     * Nettoyer et valider la configuration
     */
    public function sanitize_config($input) {
        $sanitized = array();
        
        if (isset($input['gravity_form_id'])) {
            $sanitized['gravity_form_id'] = intval($input['gravity_form_id']);
        }
        
        if (isset($input['display_fields'])) {
            $sanitized['display_fields'] = array_map('sanitize_text_field', $input['display_fields']);
        }
        
        if (isset($input['title_field'])) {
            $sanitized['title_field'] = sanitize_text_field($input['title_field']);
        }
        
        if (isset($input['description_field'])) {
            $sanitized['description_field'] = sanitize_text_field($input['description_field']);
        }
        
        return $sanitized;
    }
    
    /**
     * Récupérer la configuration
     */
    public function get_config() {
        return get_option($this->config_key, array(
            'gravity_form_id' => 0,
            'display_fields' => array(),
            'title_field' => '',
            'description_field' => ''
        ));
    }
    
    /**
     * Récupérer tous les formulaires Gravity Forms disponibles
     */
    public function get_available_forms() {
        if (!class_exists('GFFormsModel')) {
            return array();
        }
        
        $forms = GFFormsModel::get_forms();
        $form_options = array();
        
        foreach ($forms as $form) {
            $form_options[$form->id] = $form->title;
        }
        
        return $form_options;
    }
    
    /**
     * Récupérer les champs d'un formulaire Gravity Forms
     */
    public function get_form_fields($form_id) {
        if (!class_exists('GFAPI')) {
            error_log('Lead Vendeur Debug: GFAPI class not found');
            return array();
        }
        
        // Essayer d'abord avec GFAPI::get_form()
        $form = GFAPI::get_form($form_id);
        if (is_wp_error($form)) {
            error_log('Lead Vendeur Debug: Error getting form - ' . $form->get_error_message());
            return array();
        }
        
        if (!$form) {
            error_log('Lead Vendeur Debug: Form not found for ID ' . $form_id);
            return array();
        }
        
        $fields = array();
        
        // Debug : afficher la structure du formulaire
        error_log('Lead Vendeur Debug: Form structure - ' . print_r($form, true));
        
        // Essayer différentes approches pour récupérer les champs
        if (isset($form['fields']) && is_array($form['fields'])) {
            error_log('Lead Vendeur Debug: Found ' . count($form['fields']) . ' fields in form[fields]');
            foreach ($form['fields'] as $field) {
                if (is_object($field)) {
                    $field_id = isset($field->id) ? $field->id : (method_exists($field, 'get_id') ? $field->get_id() : null);
                    if ($field_id) {
                        $fields[$field_id] = array(
                            'id' => $field_id,
                            'label' => isset($field->label) ? $field->label : (method_exists($field, 'get_label') ? $field->get_label() : ''),
                            'type' => isset($field->type) ? $field->type : (method_exists($field, 'get_type') ? $field->get_type() : ''),
                            'adminLabel' => isset($field->adminLabel) ? $field->adminLabel : (method_exists($field, 'get_admin_label') ? $field->get_admin_label() : '')
                        );
                        error_log('Lead Vendeur Debug: Added field ID ' . $field_id . ' - ' . $fields[$field_id]['label']);
                    }
                }
            }
        } else {
            error_log('Lead Vendeur Debug: No fields found in form[fields] or not an array');
        }
        
        // Si aucun champ trouvé, essayer avec GFFormsModel
        if (empty($fields) && class_exists('GFFormsModel')) {
            error_log('Lead Vendeur Debug: Trying GFFormsModel approach');
            $form_model = GFFormsModel::get_form($form_id);
            if ($form_model && isset($form_model['fields'])) {
                error_log('Lead Vendeur Debug: Found ' . count($form_model['fields']) . ' fields via GFFormsModel');
                foreach ($form_model['fields'] as $field) {
                    if (is_object($field) && isset($field->id)) {
                        $fields[$field->id] = array(
                            'id' => $field->id,
                            'label' => isset($field->label) ? $field->label : '',
                            'type' => isset($field->type) ? $field->type : '',
                            'adminLabel' => isset($field->adminLabel) ? $field->adminLabel : ''
                        );
                        error_log('Lead Vendeur Debug: Added field ID ' . $field->id . ' - ' . $fields[$field->id]['label']);
                    }
                }
            }
        }
        
        error_log('Lead Vendeur Debug: Returning ' . count($fields) . ' fields');
        return $fields;
    }
    
    /**
     * Récupérer les entrées d'un formulaire
     */
    public function get_form_entries($form_id, $limit = 50) {
        if (!class_exists('GFAPI')) {
            return array();
        }
        
        $search_criteria = array();
        $sorting = array('key' => 'date_created', 'direction' => 'DESC');
        
        $entries = GFAPI::get_entries($form_id, $search_criteria, $sorting, array('page_size' => $limit));
        
        return $entries;
    }
    
    /**
     * ✅ NOUVEAU : Récupérer les entrées d'un formulaire avec pagination
     */
    public function get_form_entries_paginated($form_id, $page = 1, $per_page = 20) {
        if (!class_exists('GFAPI')) {
            return array();
        }
        
        $search_criteria = array();
        $sorting = array('key' => 'date_created', 'direction' => 'DESC');
        
        // Calculer l'offset pour la pagination
        $offset = ($page - 1) * $per_page;
        
        $entries = GFAPI::get_entries($form_id, $search_criteria, $sorting, array(
            'page_size' => $per_page,
            'offset' => $offset
        ));
        
        return $entries;
    }
    
    /**
     * ✅ NOUVEAU : Compter le nombre total d'entrées d'un formulaire
     */
    public function get_form_entries_count($form_id) {
        if (!class_exists('GFAPI')) {
            return 0;
        }
        
        $search_criteria = array();
        $total_count = GFAPI::count_entries($form_id, $search_criteria);
        
        return $total_count;
    }
    
    /**
     * Vérifier si Gravity Forms est actif
     */
    public function is_gravity_forms_active() {
        return class_exists('GFFormsModel') && class_exists('GFAPI');
    }
    
    /**
     * Récupérer la configuration d'un champ spécifique
     */
    public function get_field_config($key, $default = '') {
        $config = $this->get_config();
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    /**
     * Sauvegarder la configuration
     */
    public function save_config($config) {
        return update_option($this->config_key, $config);
    }
    
    /**
     * Vérifier si la configuration est complète
     */
    public function is_configured() {
        $config = $this->get_config();
        return !empty($config['gravity_form_id']) && $config['gravity_form_id'] > 0;
    }
}

// Fonction utilitaire pour récupérer l'instance
function lead_vendeur_config_manager() {
    return Lead_Vendeur_Config_Manager::get_instance();
}
