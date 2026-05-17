<?php
namespace NexusBuilder\Elements\Types;

class FormBuilder extends \NexusBuilder\Elements\Base {

    public function get_name(): string  { return 'form-builder'; }
    public function get_label(): string { return __('Form Builder', 'nexusbuilder'); }
    public function get_icon(): string  { return 'ti-forms'; }

    public function register_controls(): void {

        $this->start_controls_section('fields', ['label' => 'Form fields', 'tab' => 'content']);

        $this->add_control('form_id', [
            'type'    => 'text',
            'label'   => __('Form ID', 'nexusbuilder'),
            'default' => 'nexus-form-' . wp_rand(1000, 9999),
        ]);

        $this->add_control('fields', [
            'type'  => 'repeater',
            'label' => __('Fields', 'nexusbuilder'),
            'item_controls' => [
                ['id' => 'field_type',     'type' => 'select', 'label' => 'Type',
                 'options' => ['text'=>'Text','email'=>'Email','tel'=>'Phone','textarea'=>'Textarea',
                               'select'=>'Select','checkbox'=>'Checkbox','radio'=>'Radio',
                               'file'=>'File upload','date'=>'Date','number'=>'Number','hidden'=>'Hidden']],
                ['id' => 'label',          'type' => 'text',     'label' => 'Label'],
                ['id' => 'placeholder',    'type' => 'text',     'label' => 'Placeholder'],
                ['id' => 'required',       'type' => 'switcher', 'label' => 'Required'],
                ['id' => 'options',        'type' => 'textarea', 'label' => 'Options (one per line, for select/radio/checkbox)'],
                ['id' => 'width',          'type' => 'select',   'label' => 'Field width',
                 'options' => ['100%'=>'Full width','50%'=>'Half','33%'=>'One third','66%'=>'Two thirds']],
                ['id' => 'validation',     'type' => 'select',   'label' => 'Validation',
                 'options' => ['none'=>'None','email'=>'Email','url'=>'URL','number'=>'Number','phone'=>'Phone']],
                ['id' => 'css_class',      'type' => 'text',     'label' => 'CSS class'],
                ['id' => 'conditional_on', 'type' => 'text',     'label' => 'Show if field (field_id:value)'],
            ],
            'default' => [
                ['_id' => 'f1', 'field_type' => 'text',  'label' => 'Your name',  'required' => true,  'width' => '100%'],
                ['_id' => 'f2', 'field_type' => 'email', 'label' => 'Your email', 'required' => true,  'width' => '100%'],
                ['_id' => 'f3', 'field_type' => 'textarea', 'label' => 'Message', 'required' => false, 'width' => '100%'],
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('submit', ['label' => 'Submit button', 'tab' => 'content']);
        $this->add_control('submit_text',       ['type' => 'text',     'label' => 'Button text',     'default' => 'Send message']);
        $this->add_control('submit_loading',    ['type' => 'text',     'label' => 'Loading text',    'default' => 'Sending…']);
        $this->add_control('submit_align',      ['type' => 'choose',   'label' => 'Alignment',
            'options' => ['left'=>['label'=>'Left'],'center'=>['label'=>'Center'],'right'=>['label'=>'Right'],'full'=>['label'=>'Full width']],
            'default' => 'left']);
        $this->end_controls_section();

        $this->start_controls_section('actions', ['label' => 'After submit', 'tab' => 'content']);
        $this->add_control('success_action', [
            'type' => 'select', 'label' => 'Success action',
            'options' => ['message'=>'Show message','redirect'=>'Redirect to URL','none'=>'Do nothing'],
            'default' => 'message',
        ]);
        $this->add_control('success_message', ['type' => 'textarea', 'label' => 'Success message',
            'default' => 'Thank you! Your message has been sent.']);
        $this->add_control('redirect_url', ['type' => 'link', 'label' => 'Redirect URL']);

        $this->add_control('email_notifications', [
            'type'    => 'repeater',
            'label'   => 'Email notifications',
            'item_controls' => [
                ['id' => 'to',          'type' => 'text',     'label' => 'To (comma separated)'],
                ['id' => 'subject',     'type' => 'text',     'label' => 'Subject'],
                ['id' => 'from_name',   'type' => 'text',     'label' => 'From name'],
                ['id' => 'from_email',  'type' => 'text',     'label' => 'From email'],
                ['id' => 'reply_to',    'type' => 'text',     'label' => 'Reply-to field (field_id)'],
                ['id' => 'body',        'type' => 'textarea', 'label' => 'Body (use {{field_id}} tags)'],
            ],
        ]);

        $this->add_control('webhook_url', ['type' => 'text', 'label' => 'Webhook URL (POST on submit)']);
        $this->add_control('store_submissions', ['type' => 'switcher', 'label' => 'Store submissions in DB', 'default' => true]);

        $this->end_controls_section();
    }

    public function render(): void {
        $s       = $this->get_settings_for_display();
        $form_id = sanitize_key($s['form_id'] ?? 'nexus-form');
        $fields  = $s['fields'] ?? [];
        $nonce   = wp_create_nonce("nexusbuilder_form_{$form_id}");

        echo "<form class=\"nexus-form\" id=\"{$form_id}\" data-form-id=\"{$form_id}\" novalidate>";
        echo "<input type=\"hidden\" name=\"_nexus_form_id\" value=\"" . esc_attr($form_id) . "\">";
        echo "<input type=\"hidden\" name=\"_nexus_nonce\"   value=\"" . esc_attr($nonce) . "\">";

        echo '<div class="nexus-form__fields">';
        foreach ($fields as $field) {
            $this->render_field($field);
        }
        echo '</div>';

        $align = sanitize_key($s['submit_align'] ?? 'left');
        $text  = esc_html($s['submit_text'] ?? 'Submit');
        $load  = esc_attr($s['submit_loading'] ?? 'Sending…');

        echo "<div class=\"nexus-form__submit nexus-form__submit--{$align}\">";
        echo "<button type=\"submit\" class=\"nexus-form__btn\" data-loading-text=\"{$load}\">{$text}</button>";
        echo '</div>';

        echo '<div class="nexus-form__response" aria-live="polite"></div>';
        echo '</form>';
    }

    private function render_field(array $field): void {
        $type  = sanitize_key($field['field_type'] ?? 'text');
        $id    = 'field-' . sanitize_key($field['_id'] ?? wp_rand());
        $label = esc_html($field['label'] ?? '');
        $ph    = esc_attr($field['placeholder'] ?? '');
        $req   = !empty($field['required']);
        $width = esc_attr($field['width'] ?? '100%');
        $class = sanitize_html_class($field['css_class'] ?? '');
        $cond  = esc_attr($field['conditional_on'] ?? '');

        $cond_attr = $cond ? " data-condition=\"{$cond}\"" : '';

        echo "<div class=\"nexus-field nexus-field--{$type} {$class}\" style=\"width:{$width}\"{$cond_attr}>";

        if ($type !== 'hidden') {
            echo "<label for=\"{$id}\" class=\"nexus-field__label\">{$label}";
            if ($req) echo ' <span class="nexus-required" aria-label="required">*</span>';
            echo '</label>';
        }

        switch ($type) {
            case 'textarea':
                echo "<textarea id=\"{$id}\" name=\"{$id}\" class=\"nexus-field__input\" placeholder=\"{$ph}\""
                   . ($req ? ' required' : '') . " rows=\"4\"></textarea>";
                break;

            case 'select':
                echo "<select id=\"{$id}\" name=\"{$id}\" class=\"nexus-field__input\"" . ($req ? ' required' : '') . ">";
                echo '<option value="">Select…</option>';
                foreach (explode("\n", $field['options'] ?? '') as $opt) {
                    $opt = trim(esc_html($opt));
                    if ($opt) echo "<option value=\"{$opt}\">{$opt}</option>";
                }
                echo '</select>';
                break;

            case 'checkbox':
            case 'radio':
                echo "<div class=\"nexus-field__options\">";
                foreach (explode("\n", $field['options'] ?? '') as $i => $opt) {
                    $opt     = trim(esc_html($opt));
                    $opt_id  = "{$id}-{$i}";
                    echo "<label class=\"nexus-field__option-label\">";
                    echo "<input type=\"{$type}\" id=\"{$opt_id}\" name=\"{$id}" . ($type === 'checkbox' ? '[]' : '') . "\" value=\"{$opt}\"" . ($req ? ' required' : '') . ">";
                    echo " {$opt}</label>";
                }
                echo '</div>';
                break;

            case 'hidden':
                echo "<input type=\"hidden\" id=\"{$id}\" name=\"{$id}\" value=\"\">";
                break;

            default:
                echo "<input type=\"{$type}\" id=\"{$id}\" name=\"{$id}\" class=\"nexus-field__input\" placeholder=\"{$ph}\""
                   . ($req ? ' required' : '') . ">";
        }

        echo '<span class="nexus-field__error" role="alert"></span>';
        echo '</div>';
    }
}
