<?php
/**
 * Gestionnaire d'import CSV pour l'Annuaire Notarial
 * 
 * @package My_Istymo
 * @subpackage Notaires
 * @version 1.0
 * @author Brio Guiseppe
 */

if (!defined('ABSPATH')) {
    exit; // Empêche l'accès direct au fichier
}

class Notaires_Import_Handler {
    
    /**
     * Instance unique de la classe (Singleton)
     */
    private static $instance = null;
    
    /**
     * Colonnes attendues dans le CSV
     */
    private $expected_columns = [
        'nom_office',
        'telephone_office', 
        'langues_parlees',
        'site_internet',
        'email_office',
        'adresse',
        'code_postal',
        'ville',
        'nom_notaire',
        'statut_notaire',
        'url_office',
        'page_source',
        'date_extraction'
    ];
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        // Initialisation si nécessaire
    }
    
    /**
     * Récupère l'instance unique de la classe
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Valide la structure du fichier CSV
     * 
     * @param string $file_path Chemin vers le fichier CSV
     * @return array Résultat de la validation
     */
    public function validate_csv_structure($file_path) {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'columns_found' => [],
            'columns_missing' => [],
            'columns_extra' => []
        ];
        
        if (!file_exists($file_path)) {
            $result['errors'][] = 'Le fichier CSV n\'existe pas';
            return $result;
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $result['errors'][] = 'Impossible d\'ouvrir le fichier CSV';
            return $result;
        }
        
        // Lire la première ligne (en-têtes)
        $headers = fgetcsv($handle);
        fclose($handle);
        
        if (!$headers) {
            $result['errors'][] = 'Le fichier CSV est vide ou corrompu';
            return $result;
        }
        
        // Nettoyer les en-têtes
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        
        $result['columns_found'] = $headers;
        
        // Vérifier les colonnes manquantes
        foreach ($this->expected_columns as $expected_col) {
            if (!in_array($expected_col, $headers)) {
                $result['columns_missing'][] = $expected_col;
            }
        }
        
        // Vérifier les colonnes supplémentaires
        foreach ($headers as $found_col) {
            if (!in_array($found_col, $this->expected_columns)) {
                $result['columns_extra'][] = $found_col;
            }
        }
        
        // Déterminer si la structure est valide
        $result['valid'] = empty($result['columns_missing']);
        
        if (!empty($result['columns_missing'])) {
            $result['errors'][] = 'Colonnes manquantes : ' . implode(', ', $result['columns_missing']);
        }
        
        if (!empty($result['columns_extra'])) {
            $result['warnings'][] = 'Colonnes supplémentaires détectées : ' . implode(', ', $result['columns_extra']);
        }
        
        return $result;
    }
    
    /**
     * Parse le fichier CSV et retourne les données
     * 
     * @param string $file_path Chemin vers le fichier CSV
     * @param int $limit Limite du nombre de lignes à traiter (0 = toutes)
     * @return array Résultat du parsing
     */
    public function parse_csv_data($file_path, $limit = 0) {
        $result = [
            'success' => false,
            'data' => [],
            'errors' => [],
            'warnings' => [],
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0
        ];
        
        if (!file_exists($file_path)) {
            $result['errors'][] = 'Le fichier CSV n\'existe pas';
            return $result;
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $result['errors'][] = 'Impossible d\'ouvrir le fichier CSV';
            return $result;
        }
        
        // Lire les en-têtes
        $headers = fgetcsv($handle);
        if (!$headers) {
            $result['errors'][] = 'Le fichier CSV est vide';
            fclose($handle);
            return $result;
        }
        
        // Nettoyer les en-têtes
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        
        $row_number = 1; // Commencer à 1 car on a déjà lu les en-têtes
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Limite de traitement
            if ($limit > 0 && $row_number > $limit) {
                break;
            }
            
            $result['total_rows']++;
            
            // Valider et nettoyer la ligne
            $cleaned_row = $this->clean_csv_row($row, $headers, $row_number);
            
            if ($cleaned_row['valid']) {
                $result['data'][] = $cleaned_row['data'];
                $result['valid_rows']++;
            } else {
                $result['invalid_rows']++;
                $result['errors'] = array_merge($result['errors'], $cleaned_row['errors']);
            }
        }
        
        fclose($handle);
        
        $result['success'] = $result['valid_rows'] > 0;
        
        if ($result['success']) {
            my_istymo_log("CSV parsé avec succès : {$result['valid_rows']} lignes valides sur {$result['total_rows']}", 'notaires');
        } else {
            my_istymo_log("Échec du parsing CSV : aucune ligne valide trouvée", 'notaires');
        }
        
        return $result;
    }
    
    /**
     * Nettoie et valide une ligne CSV
     * 
     * @param array $row Ligne CSV brute
     * @param array $headers En-têtes du CSV
     * @param int $row_number Numéro de la ligne
     * @return array Résultat du nettoyage
     */
    private function clean_csv_row($row, $headers, $row_number) {
        $result = [
            'valid' => true,
            'data' => [],
            'errors' => []
        ];
        
        // Créer un tableau associatif
        $row_data = [];
        for ($i = 0; $i < count($headers); $i++) {
            $row_data[$headers[$i]] = isset($row[$i]) ? trim($row[$i]) : '';
        }
        
        // Nettoyer chaque champ
        $cleaned_data = [];
        
        // Nom office (obligatoire)
        $cleaned_data['nom_office'] = $this->clean_text($row_data['nom_office'] ?? '');
        if (empty($cleaned_data['nom_office'])) {
            $result['errors'][] = "Ligne $row_number : Nom de l'office manquant";
            $result['valid'] = false;
        }
        
        // Téléphone office
        $cleaned_data['telephone_office'] = $this->clean_phone($row_data['telephone_office'] ?? '');
        
        // Langues parlées
        $cleaned_data['langues_parlees'] = $this->clean_text($row_data['langues_parlees'] ?? '');
        
        // Site internet
        $cleaned_data['site_internet'] = $this->clean_url($row_data['site_internet'] ?? '');
        
        // Email office
        $cleaned_data['email_office'] = $this->clean_email($row_data['email_office'] ?? '');
        
        // Adresse
        $cleaned_data['adresse'] = $this->clean_text($row_data['adresse'] ?? '');
        
        // Code postal (obligatoire)
        $cleaned_data['code_postal'] = $this->clean_postal_code($row_data['code_postal'] ?? '');
        if (empty($cleaned_data['code_postal'])) {
            $result['errors'][] = "Ligne $row_number : Code postal manquant";
            $result['valid'] = false;
        }
        
        // Ville (obligatoire)
        $cleaned_data['ville'] = $this->clean_text($row_data['ville'] ?? '');
        if (empty($cleaned_data['ville'])) {
            $result['errors'][] = "Ligne $row_number : Ville manquante";
            $result['valid'] = false;
        }
        
        // Nom notaire
        $cleaned_data['nom_notaire'] = $this->clean_text($row_data['nom_notaire'] ?? '');
        
        // Statut notaire
        $cleaned_data['statut_notaire'] = $this->clean_status($row_data['statut_notaire'] ?? '');
        
        // URL office
        $cleaned_data['url_office'] = $this->clean_url($row_data['url_office'] ?? '');
        
        // Page source
        $cleaned_data['page_source'] = $this->clean_text($row_data['page_source'] ?? '');
        
        // Date extraction
        $cleaned_data['date_extraction'] = $this->clean_date($row_data['date_extraction'] ?? '');
        
        $result['data'] = $cleaned_data;
        
        return $result;
    }
    
    /**
     * Nettoie un texte
     */
    private function clean_text($text) {
        $text = trim($text);
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        return $text;
    }
    
    /**
     * Nettoie un numéro de téléphone
     */
    private function clean_phone($phone) {
        $phone = preg_replace('/[^0-9+\-\s\(\)]/', '', $phone);
        $phone = trim($phone);
        return substr($phone, 0, 20); // Limiter à 20 caractères
    }
    
    /**
     * Nettoie une URL
     */
    private function clean_url($url) {
        $url = trim($url);
        if (!empty($url) && !preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        return filter_var($url, FILTER_SANITIZE_URL);
    }
    
    /**
     * Nettoie un email
     */
    private function clean_email($email) {
        $email = trim($email);
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Nettoie un code postal
     */
    private function clean_postal_code($postal_code) {
        $postal_code = preg_replace('/[^0-9]/', '', $postal_code);
        return substr($postal_code, 0, 10);
    }
    
    /**
     * Nettoie un statut
     */
    private function clean_status($status) {
        $status = strtolower(trim($status));
        $valid_statuses = ['actif', 'inactif', 'suspendu'];
        
        if (in_array($status, $valid_statuses)) {
            return $status;
        }
        
        return 'actif'; // Valeur par défaut
    }
    
    /**
     * Nettoie une date
     */
    private function clean_date($date) {
        $date = trim($date);
        if (empty($date)) {
            return null;
        }
        
        // Essayer différents formats de date
        $formats = ['Y-m-d H:i:s', 'Y-m-d', 'd/m/Y', 'd-m-Y'];
        
        foreach ($formats as $format) {
            $parsed_date = DateTime::createFromFormat($format, $date);
            if ($parsed_date !== false) {
                return $parsed_date->format('Y-m-d H:i:s');
            }
        }
        
        return null;
    }
    
    /**
     * Importe les données notaires en base
     * 
     * @param array $notaires_data Données des notaires à importer
     * @return array Résultat de l'import
     */
    public function import_notaires($notaires_data) {
        $result = [
            'success' => false,
            'imported_count' => 0,
            'errors' => [],
            'warnings' => []
        ];
        
        if (empty($notaires_data)) {
            $result['errors'][] = 'Aucune donnée à importer';
            return $result;
        }
        
        $notaires_manager = Notaires_Manager::get_instance();
        
        // Vider la table existante
        if (!$notaires_manager->truncate_notaires()) {
            $result['errors'][] = 'Erreur lors du vidage de la table existante';
            return $result;
        }
        
        // Importer les nouvelles données par lots
        $batch_size = 100;
        $batches = array_chunk($notaires_data, $batch_size);
        
        foreach ($batches as $batch) {
            $imported = $notaires_manager->bulk_insert_notaires($batch);
            
            if ($imported === false) {
                $result['errors'][] = 'Erreur lors de l\'import d\'un lot de données';
                continue;
            }
            
            $result['imported_count'] += $imported;
        }
        
        $result['success'] = $result['imported_count'] > 0;
        
        if ($result['success']) {
            my_istymo_log("Import terminé avec succès : {$result['imported_count']} notaires importés", 'notaires');
        } else {
            my_istymo_log('Échec de l\'import : aucun notaire importé', 'notaires');
        }
        
        return $result;
    }
    
    /**
     * Traite un fichier CSV complet (validation + parsing + import)
     * 
     * @param string $file_path Chemin vers le fichier CSV
     * @param int $limit Limite du nombre de lignes à traiter
     * @return array Résultat complet du traitement
     */
    public function process_csv_file($file_path, $limit = 0) {
        $result = [
            'success' => false,
            'validation' => null,
            'parsing' => null,
            'import' => null,
            'total_time' => 0,
            'errors' => [],
            'warnings' => []
        ];
        
        $start_time = microtime(true);
        
        // Étape 1 : Validation de la structure
        $result['validation'] = $this->validate_csv_structure($file_path);
        
        if (!$result['validation']['valid']) {
            $result['errors'] = array_merge($result['errors'], $result['validation']['errors']);
            $result['total_time'] = microtime(true) - $start_time;
            return $result;
        }
        
        // Étape 2 : Parsing des données
        $result['parsing'] = $this->parse_csv_data($file_path, $limit);
        
        if (!$result['parsing']['success']) {
            $result['errors'] = array_merge($result['errors'], $result['parsing']['errors']);
            $result['total_time'] = microtime(true) - $start_time;
            return $result;
        }
        
        // Étape 3 : Import en base
        $result['import'] = $this->import_notaires($result['parsing']['data']);
        
        if (!$result['import']['success']) {
            $result['errors'] = array_merge($result['errors'], $result['import']['errors']);
            $result['total_time'] = microtime(true) - $start_time;
            return $result;
        }
        
        $result['success'] = true;
        $result['total_time'] = microtime(true) - $start_time;
        
        // Ajouter les warnings de toutes les étapes
        $result['warnings'] = array_merge(
            $result['validation']['warnings'] ?? [],
            $result['parsing']['warnings'] ?? [],
            $result['import']['warnings'] ?? []
        );
        
        my_istymo_log("Traitement CSV terminé avec succès en " . round($result['total_time'], 2) . " secondes", 'notaires');
        
        return $result;
    }
    
    /**
     * Génère un rapport d'import
     * 
     * @param array $result Résultat du traitement
     * @return string Rapport formaté
     */
    public function generate_import_report($result) {
        $report = "=== RAPPORT D'IMPORT ANNUAIRE NOTARIAL ===\n\n";
        
        if ($result['success']) {
            $report .= "✅ IMPORT RÉUSSI\n\n";
        } else {
            $report .= "❌ IMPORT ÉCHOUÉ\n\n";
        }
        
        // Temps de traitement
        $report .= "⏱️ Temps de traitement : " . round($result['total_time'], 2) . " secondes\n\n";
        
        // Validation
        if ($result['validation']) {
            $report .= "📋 VALIDATION :\n";
            $report .= "- Colonnes trouvées : " . count($result['validation']['columns_found']) . "\n";
            $report .= "- Colonnes manquantes : " . count($result['validation']['columns_missing']) . "\n";
            $report .= "- Colonnes supplémentaires : " . count($result['validation']['columns_extra']) . "\n\n";
        }
        
        // Parsing
        if ($result['parsing']) {
            $report .= "📊 PARSING :\n";
            $report .= "- Lignes totales : " . $result['parsing']['total_rows'] . "\n";
            $report .= "- Lignes valides : " . $result['parsing']['valid_rows'] . "\n";
            $report .= "- Lignes invalides : " . $result['parsing']['invalid_rows'] . "\n\n";
        }
        
        // Import
        if ($result['import']) {
            $report .= "💾 IMPORT :\n";
            $report .= "- Notaires importés : " . $result['import']['imported_count'] . "\n\n";
        }
        
        // Erreurs
        if (!empty($result['errors'])) {
            $report .= "❌ ERREURS :\n";
            foreach ($result['errors'] as $error) {
                $report .= "- " . $error . "\n";
            }
            $report .= "\n";
        }
        
        // Warnings
        if (!empty($result['warnings'])) {
            $report .= "⚠️ AVERTISSEMENTS :\n";
            foreach ($result['warnings'] as $warning) {
                $report .= "- " . $warning . "\n";
            }
            $report .= "\n";
        }
        
        $report .= "=== FIN DU RAPPORT ===\n";
        
        return $report;
    }
}

