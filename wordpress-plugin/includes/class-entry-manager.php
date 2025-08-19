<?php
/**
 * Entry Manager Class
 * Handles form submissions and entry data management
 */

if (!defined('ABSPATH')) {
    exit;
}

class InnovativeForms_Entry_Manager {
    
    /**
     * Create a new entry
     */
    public static function create_entry($form_id, $entry_data, $user_ip = '', $user_agent = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        // Sanitize entry data
        $sanitized_data = array();
        foreach ($entry_data as $key => $value) {
            if (is_array($value)) {
                $sanitized_data[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_data[$key] = sanitize_textarea_field($value);
            }
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'form_id' => intval($form_id),
                'entry_data' => wp_json_encode($sanitized_data),
                'user_ip' => sanitize_text_field($user_ip),
                'user_agent' => sanitize_text_field($user_agent),
                'status' => 'unread',
                'spam_score' => 0
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get an entry by ID
     */
    public static function get_entry($entry_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        $entry = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $entry_id)
        );
        
        if ($entry) {
            $entry->entry_data = json_decode($entry->entry_data, true);
        }
        
        return $entry;
    }
    
    /**
     * Get entries for a form
     */
    public static function get_form_entries($form_id, $limit = 20, $offset = 0, $status = 'all') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        $where_clause = $wpdb->prepare("WHERE form_id = %d", $form_id);
        
        if ($status !== 'all') {
            $where_clause .= $wpdb->prepare(" AND status = %s", $status);
        }
        
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name $where_clause 
                ORDER BY submission_date DESC 
                LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
        
        // Decode entry data
        foreach ($entries as $entry) {
            $entry->entry_data = json_decode($entry->entry_data, true);
        }
        
        return $entries;
    }
    
    /**
     * Get total entry count for a form
     */
    public static function get_form_entry_count($form_id, $status = 'all') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        $where_clause = $wpdb->prepare("WHERE form_id = %d", $form_id);
        
        if ($status !== 'all') {
            $where_clause .= $wpdb->prepare(" AND status = %s", $status);
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where_clause");
    }
    
    /**
     * Update entry status
     */
    public static function update_entry_status($entry_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        return $wpdb->update(
            $table_name,
            array('status' => sanitize_text_field($status)),
            array('id' => $entry_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Delete an entry
     */
    public static function delete_entry($entry_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        return $wpdb->delete($table_name, array('id' => $entry_id), array('%d'));
    }
    
    /**
     * Delete multiple entries
     */
    public static function delete_entries($entry_ids) {
        global $wpdb;
        
        if (empty($entry_ids) || !is_array($entry_ids)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        $placeholders = implode(',', array_fill(0, count($entry_ids), '%d'));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE id IN ($placeholders)",
                $entry_ids
            )
        );
    }
    
    /**
     * Export entries to CSV
     */
    public static function export_entries_csv($form_id, $filename = '') {
        $form = InnovativeForms_Form_Manager::get_form($form_id);
        
        if (!$form) {
            return false;
        }
        
        $entries = self::get_form_entries($form_id, 999999); // Get all entries
        
        if (empty($filename)) {
            $filename = sanitize_file_name($form->name) . '_entries_' . date('Y-m-d') . '.csv';
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Create header row
        $headers = array('Submission Date', 'Status');
        foreach ($form->fields as $field) {
            $headers[] = $field['label'];
        }
        $headers[] = 'IP Address';
        
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($entries as $entry) {
            $row = array(
                $entry->submission_date,
                ucfirst($entry->status)
            );
            
            foreach ($form->fields as $field) {
                $field_name = $field['name'];
                $value = isset($entry->entry_data[$field_name]) ? $entry->entry_data[$field_name] : '';
                
                // Handle array values (checkboxes)
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                $row[] = $value;
            }
            
            $row[] = $entry->user_ip;
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get entry statistics
     */
    public static function get_entry_statistics($form_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        $where_clause = '';
        $params = array();
        
        if ($form_id) {
            $where_clause = 'WHERE form_id = %d';
            $params[] = $form_id;
        }
        
        $stats = array();
        
        // Total entries
        $stats['total'] = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table_name $where_clause", $params)
        );
        
        // Entries by status
        $status_counts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, COUNT(*) as count FROM $table_name $where_clause GROUP BY status",
                $params
            )
        );
        
        $stats['by_status'] = array();
        foreach ($status_counts as $status) {
            $stats['by_status'][$status->status] = $status->count;
        }
        
        // Entries by date (last 30 days)
        $daily_counts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(submission_date) as date, COUNT(*) as count 
                FROM $table_name 
                $where_clause AND submission_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                GROUP BY DATE(submission_date) 
                ORDER BY date",
                $params
            )
        );
        
        $stats['daily'] = array();
        foreach ($daily_counts as $day) {
            $stats['daily'][$day->date] = $day->count;
        }
        
        return $stats;
    }
    
    /**
     * Search entries
     */
    public static function search_entries($form_id, $search_term, $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE form_id = %d AND entry_data LIKE %s 
                ORDER BY submission_date DESC 
                LIMIT %d OFFSET %d",
                $form_id,
                $search_term,
                $limit,
                $offset
            )
        );
        
        // Decode entry data
        foreach ($entries as $entry) {
            $entry->entry_data = json_decode($entry->entry_data, true);
        }
        
        return $entries;
    }
    
    /**
     * Get entries by date range
     */
    public static function get_entries_by_date_range($form_id, $start_date, $end_date, $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE form_id = %d 
                AND submission_date >= %s 
                AND submission_date <= %s 
                ORDER BY submission_date DESC 
                LIMIT %d OFFSET %d",
                $form_id,
                $start_date,
                $end_date,
                $limit,
                $offset
            )
        );
        
        // Decode entry data
        foreach ($entries as $entry) {
            $entry->entry_data = json_decode($entry->entry_data, true);
        }
        
        return $entries;
    }
    
    /**
     * Mark entries as read
     */
    public static function mark_entries_read($entry_ids) {
        global $wpdb;
        
        if (empty($entry_ids) || !is_array($entry_ids)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'innovative_form_entries';
        $placeholders = implode(',', array_fill(0, count($entry_ids), '%d'));
        
        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name SET status = 'read' WHERE id IN ($placeholders)",
                $entry_ids
            )
        );
    }
}

