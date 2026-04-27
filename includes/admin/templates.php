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
            ),
            'newsletter' => array(
                'title' => __('Newsletter Subscription', 'syntekpro-forms'),
                'description' => __('Simple newsletter signup form.', 'syntekpro-forms'),
                'icon' => 'dashicons-email-alt',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'subscriber_name', 'label' => __('Name', 'syntekpro-forms'), 'placeholder' => __('Your name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'subscriber_email', 'label' => __('Email Address', 'syntekpro-forms'), 'placeholder' => __('your@email.com', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_freq', 'type' => 'radio', 'name' => 'frequency', 'label' => __('Email Frequency', 'syntekpro-forms'), 'options' => array('Daily', 'Weekly', 'Monthly'))
                ),
                'settings' => array(
                    'theme' => 'modern',
                    'submit_button_text' => __('Subscribe Now', 'syntekpro-forms'),
                    'success_message' => __('Welcome! You are now subscribed to our newsletter.', 'syntekpro-forms'),
                    'primary_color' => '#ff6b6b',
                    'submit_align' => 'center'
                )
            ),
            'registration' => array(
                'title' => __('Event Registration', 'syntekpro-forms'),
                'description' => __('Register attendees for events and webinars.', 'syntekpro-forms'),
                'icon' => 'dashicons-calendar-alt',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'full_name', 'label' => __('Full Name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'email', 'label' => __('Email Address', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_phone', 'type' => 'text', 'name' => 'phone', 'label' => __('Phone Number', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_company', 'type' => 'text', 'name' => 'company', 'label' => __('Company Name', 'syntekpro-forms')),
                    array('id' => 'field_tickets', 'type' => 'number', 'name' => 'ticket_count', 'label' => __('Number of Tickets', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_dietary', 'type' => 'select', 'name' => 'dietary', 'label' => __('Dietary Requirements', 'syntekpro-forms'), 'options' => array('None', 'Vegetarian', 'Vegan', 'Gluten-Free', 'Other'))
                ),
                'settings' => array(
                    'theme' => 'elegant',
                    'submit_button_text' => __('Register Now', 'syntekpro-forms'),
                    'success_message' => __('Registration successful! Check your email for event details.', 'syntekpro-forms'),
                    'primary_color' => '#9c27b0',
                    'submit_align' => 'center'
                )
            ),
            'survey' => array(
                'title' => __('Customer Survey', 'syntekpro-forms'),
                'description' => __('Gather insights from your customers.', 'syntekpro-forms'),
                'icon' => 'dashicons-chart-bar',
                'fields' => array(
                    array('id' => 'field_age', 'type' => 'select', 'name' => 'age_group', 'label' => __('Age Group', 'syntekpro-forms'), 'options' => array('18-24', '25-34', '35-44', '45-54', '55+')),
                    array('id' => 'field_freq', 'type' => 'radio', 'name' => 'usage_frequency', 'label' => __('How often do you use our service?', 'syntekpro-forms'), 'options' => array('Daily', 'Weekly', 'Monthly', 'Rarely')),
                    array('id' => 'field_rating', 'type' => 'radio', 'name' => 'satisfaction', 'label' => __('Satisfaction Level', 'syntekpro-forms'), 'options' => array('Very Satisfied', 'Satisfied', 'Neutral', 'Unsatisfied')),
                    array('id' => 'field_recommend', 'type' => 'radio', 'name' => 'recommend', 'label' => __('Would you recommend us?', 'syntekpro-forms'), 'options' => array('Yes', 'Maybe', 'No')),
                    array('id' => 'field_comments', 'type' => 'textarea', 'name' => 'additional_comments', 'label' => __('Additional Comments', 'syntekpro-forms'))
                ),
                'settings' => array(
                    'theme' => 'modern',
                    'submit_button_text' => __('Submit Survey', 'syntekpro-forms'),
                    'success_message' => __('Thank you for completing our survey!', 'syntekpro-forms'),
                    'primary_color' => '#00bcd4',
                    'submit_align' => 'center'
                )
            ),
            'support' => array(
                'title' => __('Support Ticket', 'syntekpro-forms'),
                'description' => __('Submit technical support requests.', 'syntekpro-forms'),
                'icon' => 'dashicons-sos',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'customer_name', 'label' => __('Your Name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'email', 'label' => __('Email Address', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_priority', 'type' => 'select', 'name' => 'priority', 'label' => __('Priority Level', 'syntekpro-forms'), 'options' => array('Low', 'Medium', 'High', 'Critical'), 'required' => true),
                    array('id' => 'field_category', 'type' => 'select', 'name' => 'issue_category', 'label' => __('Issue Category', 'syntekpro-forms'), 'options' => array('Technical', 'Billing', 'Account', 'Other'), 'required' => true),
                    array('id' => 'field_subject', 'type' => 'text', 'name' => 'subject', 'label' => __('Subject', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_description', 'type' => 'textarea', 'name' => 'issue_description', 'label' => __('Issue Description', 'syntekpro-forms'), 'placeholder' => __('Describe your issue in detail...', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_attachment', 'type' => 'file', 'name' => 'screenshot', 'label' => __('Attach Screenshot (Optional)', 'syntekpro-forms'))
                ),
                'settings' => array(
                    'theme' => 'classic',
                    'submit_button_text' => __('Submit Ticket', 'syntekpro-forms'),
                    'success_message' => __('Support ticket created. Ticket ID: #' . rand(10000, 99999), 'syntekpro-forms'),
                    'primary_color' => '#dc3545',
                    'submit_align' => 'left'
                )
            ),
            'booking' => array(
                'title' => __('Appointment Booking', 'syntekpro-forms'),
                'description' => __('Schedule appointments and consultations.', 'syntekpro-forms'),
                'icon' => 'dashicons-clock',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'client_name', 'label' => __('Your Name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'email', 'label' => __('Email', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_phone', 'type' => 'text', 'name' => 'phone', 'label' => __('Phone Number', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_service', 'type' => 'select', 'name' => 'service_type', 'label' => __('Service Type', 'syntekpro-forms'), 'options' => array('Consultation', 'Treatment', 'Follow-up', 'Other'), 'required' => true),
                    array('id' => 'field_date', 'type' => 'date', 'name' => 'preferred_date', 'label' => __('Preferred Date', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_time', 'type' => 'select', 'name' => 'preferred_time', 'label' => __('Preferred Time', 'syntekpro-forms'), 'options' => array('9:00 AM', '10:00 AM', '11:00 AM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM'), 'required' => true),
                    array('id' => 'field_notes', 'type' => 'textarea', 'name' => 'additional_notes', 'label' => __('Additional Notes', 'syntekpro-forms'))
                ),
                'settings' => array(
                    'theme' => 'modern',
                    'submit_button_text' => __('Book Appointment', 'syntekpro-forms'),
                    'success_message' => __('Your appointment request has been received. We will confirm shortly.', 'syntekpro-forms'),
                    'primary_color' => '#17a2b8',
                    'submit_align' => 'center'
                )
            ),
            'donation' => array(
                'title' => __('Donation Form', 'syntekpro-forms'),
                'description' => __('Accept donations and contributions.', 'syntekpro-forms'),
                'icon' => 'dashicons-heart',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'donor_name', 'label' => __('Your Name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'email', 'label' => __('Email Address', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_amount', 'type' => 'radio', 'name' => 'donation_amount', 'label' => __('Donation Amount', 'syntekpro-forms'), 'options' => array('$25', '$50', '$100', '$250', 'Custom'), 'required' => true),
                    array('id' => 'field_custom', 'type' => 'number', 'name' => 'custom_amount', 'label' => __('Custom Amount ($)', 'syntekpro-forms')),
                    array('id' => 'field_cause', 'type' => 'select', 'name' => 'donation_cause', 'label' => __('Donation Purpose', 'syntekpro-forms'), 'options' => array('General Fund', 'Education', 'Healthcare', 'Environment', 'Emergency Relief')),
                    array('id' => 'field_anonymous', 'type' => 'checkbox', 'name' => 'anonymous', 'label' => __('Preferences', 'syntekpro-forms'), 'options' => array('Make my donation anonymous', 'Send me updates')),
                    array('id' => 'field_message', 'type' => 'textarea', 'name' => 'message', 'label' => __('Personal Message (Optional)', 'syntekpro-forms'))
                ),
                'settings' => array(
                    'theme' => 'elegant',
                    'submit_button_text' => __('Make Donation', 'syntekpro-forms'),
                    'success_message' => __('Thank you for your generous donation! You will receive a receipt via email.', 'syntekpro-forms'),
                    'primary_color' => '#e91e63',
                    'submit_align' => 'center'
                )
            ),
            'order' => array(
                'title' => __('Order Form', 'syntekpro-forms'),
                'description' => __('Simple product or service order form.', 'syntekpro-forms'),
                'icon' => 'dashicons-cart',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'customer_name', 'label' => __('Full Name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'email', 'label' => __('Email Address', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_phone', 'type' => 'text', 'name' => 'phone', 'label' => __('Phone Number', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_product', 'type' => 'select', 'name' => 'product', 'label' => __('Select Product', 'syntekpro-forms'), 'options' => array('Product A - $99', 'Product B - $149', 'Product C - $199'), 'required' => true),
                    array('id' => 'field_quantity', 'type' => 'number', 'name' => 'quantity', 'label' => __('Quantity', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_address', 'type' => 'textarea', 'name' => 'shipping_address', 'label' => __('Shipping Address', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_notes', 'type' => 'textarea', 'name' => 'order_notes', 'label' => __('Order Notes (Optional)', 'syntekpro-forms'))
                ),
                'settings' => array(
                    'theme' => 'modern',
                    'submit_button_text' => __('Place Order', 'syntekpro-forms'),
                    'success_message' => __('Order received! Your order number is #' . rand(1000, 9999), 'syntekpro-forms'),
                    'primary_color' => '#ff9800',
                    'submit_align' => 'center'
                )
            ),
            'membership' => array(
                'title' => __('Membership Application', 'syntekpro-forms'),
                'description' => __('Apply for membership or club enrollment.', 'syntekpro-forms'),
                'icon' => 'dashicons-groups',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'applicant_name', 'label' => __('Full Name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'email', 'label' => __('Email Address', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_phone', 'type' => 'text', 'name' => 'phone', 'label' => __('Phone Number', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_dob', 'type' => 'date', 'name' => 'date_of_birth', 'label' => __('Date of Birth', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_type', 'type' => 'radio', 'name' => 'membership_type', 'label' => __('Membership Type', 'syntekpro-forms'), 'options' => array('Individual - $50/year', 'Family - $100/year', 'Corporate - $500/year'), 'required' => true),
                    array('id' => 'field_interests', 'type' => 'checkbox', 'name' => 'interests', 'label' => __('Areas of Interest', 'syntekpro-forms'), 'options' => array('Events', 'Workshops', 'Networking', 'Volunteer')),
                    array('id' => 'field_referral', 'type' => 'text', 'name' => 'referral', 'label' => __('How did you hear about us?', 'syntekpro-forms')),
                    array('id' => 'field_reason', 'type' => 'textarea', 'name' => 'reason', 'label' => __('Why do you want to join?', 'syntekpro-forms'), 'required' => true)
                ),
                'settings' => array(
                    'theme' => 'elegant',
                    'submit_button_text' => __('Submit Application', 'syntekpro-forms'),
                    'success_message' => __('Application submitted! We will review and contact you within 3-5 business days.', 'syntekpro-forms'),
                    'primary_color' => '#673ab7',
                    'submit_align' => 'center'
                )
            ),
            'rsvp' => array(
                'title' => __('RSVP Form', 'syntekpro-forms'),
                'description' => __('Collect RSVPs for events and parties.', 'syntekpro-forms'),
                'icon' => 'dashicons-tickets-alt',
                'fields' => array(
                    array('id' => 'field_name', 'type' => 'text', 'name' => 'guest_name', 'label' => __('Your Name', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_email', 'type' => 'email', 'name' => 'email', 'label' => __('Email Address', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_attending', 'type' => 'radio', 'name' => 'attendance', 'label' => __('Will you be attending?', 'syntekpro-forms'), 'options' => array('Yes, I will attend', 'No, I cannot attend'), 'required' => true),
                    array('id' => 'field_guests', 'type' => 'number', 'name' => 'guest_count', 'label' => __('Number of Guests (including you)', 'syntekpro-forms'), 'required' => true),
                    array('id' => 'field_dietary', 'type' => 'text', 'name' => 'dietary_restrictions', 'label' => __('Dietary Restrictions', 'syntekpro-forms')),
                    array('id' => 'field_message', 'type' => 'textarea', 'name' => 'special_message', 'label' => __('Special Message to Host', 'syntekpro-forms'))
                ),
                'settings' => array(
                    'theme' => 'modern',
                    'submit_button_text' => __('Send RSVP', 'syntekpro-forms'),
                    'success_message' => __('Thank you for your RSVP! We look forward to seeing you.', 'syntekpro-forms'),
                    'primary_color' => '#4caf50',
                    'submit_align' => 'center'
                )
            )
        );
    }
}
