<?php
namespace NexusBuilder\Forms;

class SubmissionHandler {

    public function init(): void {
        add_action('wp_ajax_nexusbuilder_submit_form',        [$this, 'handle']);
        add_action('wp_ajax_nopriv_nexusbuilder_submit_form', [$this, 'handle']);
    }

    public function handle(): void {
        $form_id = sanitize_key($_POST['_nexus_form_id'] ?? '');
        $nonce   = sanitize_text_field($_POST['_nexus_nonce'] ?? '');

        if (!wp_verify_nonce($nonce, "nexusbuilder_form_{$form_id}")) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }

        // Get form settings from postmeta/DB (stored when page is saved)
        $form_settings = $this->get_form_settings($form_id);
        if (!$form_settings) {
            wp_send_json_error(['message' => 'Form not found.'], 404);
        }

        // Build submission data
        $data = [];
        foreach ($form_settings['fields'] as $field) {
            $field_key = 'field-' . $field['_id'];
            $raw       = $_POST[$field_key] ?? ($_FILES[$field_key] ?? null);
            $value     = is_array($raw)
                ? array_map('sanitize_text_field', $raw)
                : sanitize_text_field((string)$raw);
            $data[$field['label']] = $value;

            // Server-side validation
            if (!empty($field['required']) && empty($value)) {
                wp_send_json_error(['field' => $field_key, 'message' => $field['label'] . ' is required.']);
            }
            if (($field['validation'] ?? '') === 'email' && !is_email($value)) {
                wp_send_json_error(['field' => $field_key, 'message' => 'Please enter a valid email address.']);
            }
        }

        // Store submission in DB
        if ($form_settings['store_submissions'] ?? true) {
            $this->store_submission($form_id, $data);
        }

        // Send email notifications
        foreach ($form_settings['email_notifications'] ?? [] as $notif) {
            $this->send_notification($notif, $data);
        }

        // Fire webhook
        if (!empty($form_settings['webhook_url'])) {
            wp_remote_post($form_settings['webhook_url'], [
                'body'    => wp_json_encode(['form_id' => $form_id, 'data' => $data]),
                'headers' => ['Content-Type' => 'application/json'],
                'blocking' => false,
            ]);
        }

        wp_send_json_success([
            'action'  => $form_settings['success_action'] ?? 'message',
            'message' => wp_kses_post($form_settings['success_message'] ?? 'Thank you!'),
            'url'     => esc_url($form_settings['redirect_url']['url'] ?? ''),
        ]);
    }

    private function send_notification(array $notif, array $data): void {
        $to      = sanitize_text_field($notif['to'] ?? get_option('admin_email'));
        $subject = sanitize_text_field($notif['subject'] ?? 'New form submission');
        $body    = wp_kses_post($notif['body'] ?? '');

        // Replace {{field_label}} placeholders in body
        foreach ($data as $label => $value) {
            $body = str_replace('{{' . $label . '}}', is_array($value) ? implode(', ', $value) : $value, $body);
        }

        // Fallback body: list all fields
        if (empty(trim($body))) {
            $body = '';
            foreach ($data as $label => $value) {
                $body .= esc_html($label) . ': ' . esc_html(is_array($value) ? implode(', ', $value) : $value) . "\n";
            }
        }

        wp_mail($to, $subject, $body, [
            'From: ' . sanitize_text_field($notif['from_name'] ?? get_bloginfo('name'))
            . ' <' . sanitize_email($notif['from_email'] ?? get_option('admin_email')) . '>',
        ]);
    }

    private function store_submission(string $form_id, array $data): void {
        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}nexusbuilder_form_submissions",
            [
                'form_id'    => $form_id,
                'data'       => wp_json_encode($data),
                'ip_address' => $this->get_ip(),
                'created_at' => current_time('mysql'),
            ],
            ['%s','%s','%s','%s']
        );
    }

    private function get_ip(): string {
        // Anonymize last octet for GDPR compliance
        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
            return implode('.', $parts);
        }
        return 'unknown';
    }

    private function get_form_settings(string $form_id): ?array {
        // Look up the form settings stored in postmeta when the page was saved
        global $wpdb;
        $row = $wpdb->get_var($wpdb->prepare(
            "SELECT form_settings FROM {$wpdb->prefix}nexusbuilder_forms WHERE form_id = %s",
            $form_id
        ));
        return $row ? json_decode($row, true) : null;
    }
}
