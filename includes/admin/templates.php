<?php
/**
 * Form Templates - SyntekPro Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Templates {

    public static function get_templates() {
        return array(
            'blank' => array(
                'title' => __('Blank Form', 'syntekpro-forms'),
                'description' => __('Start from scratch and build your own form.', 'syntekpro-forms'),
                'icon' => 'dashicons-plus-alt',
                'fields' => array(),
                'settings' => array(
                    'theme' => 'classic',
                    'submit_button_text' => __('Submit', 'syntekpro-forms'),
                    'success_message' => __('Thank you! Your form has been submitted successfully.', 'syntekpro-forms'),
                    'primary_color' => '#0073aa',
                    'submit_align' => 'left'
                )
            ),
            'simple_contact' => array(
                'title' => __('Simple Contact Us', 'syntekpro-forms'),
                'description' => __('A basic contact form with name, email, and message.', 'syntekpro-forms'),
                'icon' => 'dashicons-email',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'your_name', 'label' => __('Your Name', 'syntekpro-forms'), 'placeholder' => __('Enter your name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'your_email', 'label' => __('Your Email', 'syntekpro-forms'), 'placeholder' => __('Enter your email', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_message', 'type' => 'textarea', 'name' => 'message', 'label' => __('Message', 'syntekpro-forms'), 'placeholder' => __('How can we help you?', 'syntekpro-forms'), 'required' => true)
                ),
                'settings' => array(
                    'theme' => 'modern',
                    'submit_button_text' => __('Send Message', 'syntekpro-forms'),
                    'success_message' => __('Message sent! We will get back to you soon.', 'syntekpro-forms'),
                    'primary_color' => '#2271b1',
                    'submit_align' => 'center'
                )
            ),
            'advanced_contact' => array(
                'title' => __('Advanced Contact Us', 'syntekpro-forms'),
                'description' => __('Detailed contact form with subject and department options.', 'syntekpro-forms'),
                'icon' => 'dashicons-testimonial',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'your_name', 'label' => __('Full Name', 'syntekpro-forms'), 'placeholder' => __('John Doe', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'your_email', 'label' => __('Email Address', 'syntekpro-forms'), 'placeholder' => __('john@example.com', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_dept', 'type' => 'select', 'name' => 'department', 'label' => __('Department', 'syntekpro-forms'), 'options' => array('Sales', 'Support', 'Billing', 'Other'), 'required' => true),
                    array('id' => 'field_subject', 'type' => 'text', 'name' => 'subject', 'label' => __('Subject', 'syntekpro-forms'), 'placeholder' => __('What is this regarding?', 'syntekpro-forms')),
                    array('id' => 'field_message', 'type' => 'textarea', 'name' => 'message', 'label' => __('Detailed Message', 'syntekpro-forms'), 'placeholder' => __('Provide as much detail as possible', 'syntekpro-forms'), 'required' => true)
                ),
                'settings' => array(
                    'theme' => 'modern',
                    'submit_button_text' => __('Submit Inquiry', 'syntekpro-forms'),
                    'success_message' => __('Your inquiry has been received. Ticket #'.rand(1000, 9999), 'syntekpro-forms'),
                    'primary_color' => '#1d2327',
                    'submit_align' => 'left',
                    'border_radius' => '8'
                )
            ),
            'get_quote' => array(
                'title' => __('Get a Quote', 'syntekpro-forms'),
                'description' => __('Service request form to collect project details.', 'syntekpro-forms'),
                'icon' => 'dashicons-media-spreadsheet',
                'fields' => array(
                    array('id' => 'field_service', 'type' => 'select', 'name' => 'service_type', 'label' => __('Service Needed', 'syntekpro-forms'), 'options' => array('Web Design', 'SEO', 'Marketing', 'Consulting'), 'required' => true),
                    array('id' => 'field_budget', 'type' => 'radio', 'name' => 'budget_range', 'label' => __('Budget Range', 'syntekpro-forms'), 'options' => array('$500 - $1000', '$1000 - $5000', '$5000+'), 'required' => true),
                    array('id' => 'field_deadline', 'type' => 'date', 'name' => 'deadline', 'label' => __('Expected Deadline', 'syntekpro-forms')),
                    array('id' => 'field_details', 'type' => 'textarea', 'name' => 'project_details', 'label' => __('Project Details', 'syntekpro-forms'), 'placeholder' => __('Describe your project goals...', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'contact_email', 'label' => __('Contact Email', 'syntekpro-forms'), 'placeholder' => __('Where should we send the quote?', 'syntekpro-forms'), 'required' => true)
                ),
                'settings' => array(
                    'theme' => 'classic',
                    'submit_button_text' => __('Request Quote', 'syntekpro-forms'),
                    'success_message' => __('Thank you for your request. We will provide a quote within 24 hours.', 'syntekpro-forms'),
                    'primary_color' => '#28a745',
                    'submit_align' => 'right',
                    'bg_color' => '#f9f9f9'
                )
            ),
            'career' => array(
                'title' => __('Start a Career with Us', 'syntekpro-forms'),
                'description' => __('Job application form with file upload for resumes.', 'syntekpro-forms'),
                'icon' => 'dashicons-businessman',
                'fields' => array(
                    array('id' => 'field_pos', 'type' => 'text', 'name' => 'position', 'label' => __('Position Applied For', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'full_name', 'label' => __('Full Name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'email', 'label' => __('Email Address', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_resume', 'type' => 'file', 'name' => 'resume', 'label' => __('Upload Resume (PDF)', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_linked', 'type' => 'text', 'name' => 'linkedin_profile', 'label' => __('LinkedIn Profile URL', 'syntekpro-forms')),
                    array('id' => 'field_cover', 'type' => 'textarea', 'name' => 'cover_letter', 'label' => __('Cover Letter', 'syntekpro-forms'), 'placeholder' => __('Tell us why you are a great fit!', 'syntekpro-forms'))
                ),
                'settings' => array(
                    'theme' => 'modern',
                    'submit_button_text' => __('Submit Application', 'syntekpro-forms'),
                    'success_message' => __('Application received! Our HR team will review it and contact you.', 'syntekpro-forms'),
                    'primary_color' => '#6c63ff',
                    'submit_align' => 'center'
                )
            ),
            'feedback' => array(
                'title' => __('Feedback Form', 'syntekpro-forms'),
                'description' => __('Collect customer feedback and satisfaction ratings.', 'syntekpro-forms'),
                'icon' => 'dashicons-star-filled',
                'fields' => array(
                    array('id' => 'field_rating', 'type' => 'radio', 'name' => 'rating', 'label' => __('Overall Satisfaction', 'syntekpro-forms'), 'options' => array('Excellent', 'Good', 'Average', 'Poor'), 'required' => true),
                    array('id' => 'field_what', 'type' => 'checkbox', 'name' => 'liked_features', 'label' => __('What did you like most?', 'syntekpro-forms'), 'options' => array('Ease of use', 'Design', 'Support', 'Features')),
                    array('id' => 'field_improve', 'type' => 'textarea', 'name' => 'improvement', 'label' => __('How can we improve?', 'syntekpro-forms')),
                    array('id' => 'field_contact', 'type' => 'radio', 'name' => 'allow_contact', 'label' => __('Can we contact you for more details?', 'syntekpro-forms'), 'options' => array('Yes', 'No'))
                ),
                'settings' => array(
                    'theme' => 'minimal',
                    'submit_button_text' => __('Share Feedback', 'syntekpro-forms'),
                    'success_message' => __('Thank you for your valuable feedback!', 'syntekpro-forms'),
                    'primary_color' => '#ffc107',
                    'submit_align' => 'center'
                )
            )
        );
    }
}
