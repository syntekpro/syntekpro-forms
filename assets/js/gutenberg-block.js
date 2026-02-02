/**
 * SyntekPro Forms - Gutenberg Block
 */

(function() {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, PanelColorSettings } = wp.blockEditor || wp.editor;
    const { PanelBody, SelectControl, Placeholder, ToggleControl, TextControl, RangeControl, ExternalLink } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el } = wp.element;
    const { ServerSideRender } = wp.serverSideRender || wp.components;

    // Get forms data from localized script
    const formsData = syntekproFormsData || { forms: [] };
    
    // Prepare options for select control
    const formOptions = [
        { value: '', label: __('Select a form', 'syntekpro-forms') },
        ...formsData.forms
    ];

    registerBlockType('syntekpro-forms/form-selector', {
        title: __('SyntekPro Form', 'syntekpro-forms'),
        description: __('Insert a SyntekPro form', 'syntekpro-forms'),
        icon: 'feedback',
        category: 'widgets',
        attributes: {
            formId: { type: 'string', default: '' },
            showTitle: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true },
            theme: { type: 'string', default: 'classic' },
            inputSize: { type: 'string', default: 'medium' },
            inputBgColor: { type: 'string', default: '' },
            inputBorderColor: { type: 'string', default: '' },
            inputTextColor: { type: 'string', default: '' },
            inputAccentColor: { type: 'string', default: '' },
            labelFontSize: { type: 'number', default: 16 },
            labelTextColor: { type: 'string', default: '' },
            descriptionFontSize: { type: 'number', default: 14 },
            descriptionTextColor: { type: 'string', default: '' },
            buttonBgColor: { type: 'string', default: '' },
            buttonTextColor: { type: 'string', default: '' },
            preview: { type: 'boolean', default: false },
            ajax: { type: 'boolean', default: true },
            tabindex: { type: 'number', default: 0 },
            fieldValues: { type: 'string', default: '' },
            primaryColor: { type: 'string', default: '' },
            labelColor: { type: 'string', default: '' },
            bgColor: { type: 'string', default: '' },
            borderRadius: { type: 'number', default: 0 },
            fieldPadding: { type: 'number', default: 0 },
            fontFamily: { type: 'string', default: '' },
            submitAlign: { type: 'string', default: '' }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { 
                formId, showTitle, showDescription, theme, inputSize, 
                inputBgColor, inputBorderColor, inputTextColor, inputAccentColor,
                labelFontSize, labelTextColor, descriptionFontSize, descriptionTextColor,
                    buttonBgColor, buttonTextColor, preview, ajax, tabindex, fieldValues,
                    primaryColor, labelColor, bgColor, borderRadius, fieldPadding, fontFamily, submitAlign
            } = attributes;
            
            function onChangeFormId(newFormId) {
                setAttributes({ formId: newFormId });
            }
            
            const inspectorControls = el(
                InspectorControls,
                {},
                el(
                    PanelBody,
                    { title: __('Form', 'syntekpro-forms') },
                    el(SelectControl, {
                        label: __('Select and display one of your forms.', 'syntekpro-forms'),
                        value: formId,
                        options: formOptions,
                        onChange: onChangeFormId
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Form Settings', 'syntekpro-forms'), initialOpen: false },
                    el(SelectControl, {
                        label: __('Form', 'syntekpro-forms'),
                        value: formId,
                        options: formOptions,
                        onChange: onChangeFormId
                    }),
                    el(ToggleControl, {
                        label: __('Show Form Title', 'syntekpro-forms'),
                        checked: showTitle,
                        onChange: (val) => setAttributes({ showTitle: val })
                    }),
                    el(ToggleControl, {
                        label: __('Show Form Description', 'syntekpro-forms'),
                        checked: showDescription,
                        onChange: (val) => setAttributes({ showDescription: val })
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Form Styles', 'syntekpro-forms'), initialOpen: false },
                    el(SelectControl, {
                        label: __('Form Theme', 'syntekpro-forms'),
                        value: theme,
                        options: [
                            { value: 'inherit', label: __('Use Site Theme', 'syntekpro-forms') },
                            { value: 'classic', label: __('Classic', 'syntekpro-forms') },
                            { value: 'modern', label: __('Modern', 'syntekpro-forms') },
                            { value: 'minimal', label: __('Minimal', 'syntekpro-forms') },
                            { value: 'elegant', label: __('Elegant', 'syntekpro-forms') },
                            { value: 'contrast', label: __('High Contrast', 'syntekpro-forms') },
                            { value: 'pastel', label: __('Pastel', 'syntekpro-forms') },
                            { value: 'outline', label: __('Outline', 'syntekpro-forms') },
                            { value: 'glass', label: __('Glassmorphism', 'syntekpro-forms') },
                        ],
                        onChange: (val) => setAttributes({ theme: val })
                    }),
                    el('div', { className: 'spf-gutenberg-reset-wrap', style: { marginTop: '15px' } },
                        el('button', { 
                            className: 'button button-secondary',
                            onClick: () => setAttributes({
                                theme: 'classic',
                                inputSize: 'medium',
                                labelFontSize: 16,
                                descriptionFontSize: 14,
                                showTitle: true,
                                showDescription: true
                            })
                        }, __('Reset Defaults', 'syntekpro-forms')),
                        el('p', { style: { marginTop: '15px', fontSize: '13px' } },
                            el(ExternalLink, {
                                href: 'https://syntekpro.com/docs/forms-themes'
                            }, __('Learn more about configuring form themes.', 'syntekpro-forms'))
                        )
                    ),
                    el(
                        PanelBody,
                        { title: __('Layout & Colors', 'syntekpro-forms'), initialOpen: false },
                        el(PanelColorSettings, {
                            title: __('Core Colors', 'syntekpro-forms'),
                            initialOpen: true,
                            colorSettings: [
                                {
                                    value: primaryColor,
                                    onChange: (val) => setAttributes({ primaryColor: val }),
                                    label: __('Primary', 'syntekpro-forms')
                                },
                                {
                                    value: labelColor,
                                    onChange: (val) => setAttributes({ labelColor: val }),
                                    label: __('Labels', 'syntekpro-forms')
                                },
                                {
                                    value: bgColor,
                                    onChange: (val) => setAttributes({ bgColor: val }),
                                    label: __('Form Background', 'syntekpro-forms')
                                }
                            ]
                        }),
                        el(SelectControl, {
                            label: __('Font Family', 'syntekpro-forms'),
                            value: fontFamily,
                            options: [
                                { value: '', label: __('Inherit', 'syntekpro-forms') },
                                { value: 'sans-serif', label: __('Sans Serif', 'syntekpro-forms') },
                                { value: 'serif', label: __('Serif', 'syntekpro-forms') },
                                { value: 'monospace', label: __('Monospace', 'syntekpro-forms') }
                            ],
                            onChange: (val) => setAttributes({ fontFamily: val })
                        }),
                        el(RangeControl, {
                            label: __('Field Padding (px)', 'syntekpro-forms'),
                            value: fieldPadding,
                            onChange: (val) => setAttributes({ fieldPadding: val }),
                            min: 4,
                            max: 28
                        }),
                        el(RangeControl, {
                            label: __('Border Radius (px)', 'syntekpro-forms'),
                            value: borderRadius,
                            onChange: (val) => setAttributes({ borderRadius: val }),
                            min: 0,
                            max: 24
                        }),
                        el(SelectControl, {
                            label: __('Submit Alignment', 'syntekpro-forms'),
                            value: submitAlign,
                            options: [
                                { value: '', label: __('Default', 'syntekpro-forms') },
                                { value: 'left', label: __('Left', 'syntekpro-forms') },
                                { value: 'center', label: __('Center', 'syntekpro-forms') },
                                { value: 'right', label: __('Right', 'syntekpro-forms') }
                            ],
                            onChange: (val) => setAttributes({ submitAlign: val })
                        })
                    )
                ),
                el(
                    PanelBody,
                    { title: __('Input Styles', 'syntekpro-forms'), initialOpen: false },
                    el(SelectControl, {
                        label: __('Size', 'syntekpro-forms'),
                        value: inputSize,
                        options: [
                            { value: 'small', label: __('Small', 'syntekpro-forms') },
                            { value: 'medium', label: __('Medium', 'syntekpro-forms') },
                            { value: 'large', label: __('Large', 'syntekpro-forms') }
                        ],
                        onChange: (val) => setAttributes({ inputSize: val })
                    }),
                    el(PanelColorSettings, {
                        title: __('Colors', 'syntekpro-forms'),
                        initialOpen: true,
                        colorSettings: [
                            {
                                value: inputBgColor,
                                onChange: (val) => setAttributes({ inputBgColor: val }),
                                label: __('Background', 'syntekpro-forms')
                            },
                            {
                                value: inputBorderColor,
                                onChange: (val) => setAttributes({ inputBorderColor: val }),
                                label: __('Border', 'syntekpro-forms')
                            },
                            {
                                value: inputTextColor,
                                onChange: (val) => setAttributes({ inputTextColor: val }),
                                label: __('Text', 'syntekpro-forms')
                            },
                            {
                                value: inputAccentColor,
                                onChange: (val) => setAttributes({ inputAccentColor: val }),
                                label: __('Accent', 'syntekpro-forms'),
                                help: __('The accent color is used for aspects such as checkmarks and dropdown choices.', 'syntekpro-forms')
                            }
                        ]
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Label Styles', 'syntekpro-forms'), initialOpen: false },
                    el(RangeControl, {
                        label: __('Font Size', 'syntekpro-forms'),
                        help: __('In pixels.', 'syntekpro-forms'),
                        value: labelFontSize,
                        onChange: (val) => setAttributes({ labelFontSize: val }),
                        min: 10,
                        max: 36
                    }),
                    el(PanelColorSettings, {
                        title: __('Colors', 'syntekpro-forms'),
                        colorSettings: [
                            {
                                value: labelTextColor,
                                onChange: (val) => setAttributes({ labelTextColor: val }),
                                label: __('Text', 'syntekpro-forms')
                            }
                        ]
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Description Styles', 'syntekpro-forms'), initialOpen: false },
                    el(RangeControl, {
                        label: __('Font Size', 'syntekpro-forms'),
                        help: __('In pixels.', 'syntekpro-forms'),
                        value: descriptionFontSize,
                        onChange: (val) => setAttributes({ descriptionFontSize: val }),
                        min: 8,
                        max: 24
                    }),
                    el(PanelColorSettings, {
                        title: __('Colors', 'syntekpro-forms'),
                        colorSettings: [
                            {
                                value: descriptionTextColor,
                                onChange: (val) => setAttributes({ descriptionTextColor: val }),
                                label: __('Text', 'syntekpro-forms')
                            }
                        ]
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Button Styles', 'syntekpro-forms'), initialOpen: false },
                    el(PanelColorSettings, {
                        title: __('Colors', 'syntekpro-forms'),
                        initialOpen: true,
                        colorSettings: [
                            {
                                value: buttonBgColor,
                                onChange: (val) => setAttributes({ buttonBgColor: val }),
                                label: __('Background', 'syntekpro-forms'),
                                help: __('The background color is used for various form elements, such as buttons and progress bars.', 'syntekpro-forms')
                            },
                            {
                                value: buttonTextColor,
                                onChange: (val) => setAttributes({ buttonTextColor: val }),
                                label: __('Text', 'syntekpro-forms')
                            }
                        ]
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Advanced', 'syntekpro-forms'), initialOpen: false },
                    el(TextControl, {
                        label: __('Form ID', 'syntekpro-forms'),
                        value: formId,
                        readOnly: true
                    }),
                    el(ToggleControl, {
                        label: __('Preview', 'syntekpro-forms'),
                        checked: preview,
                        onChange: (val) => setAttributes({ preview: val })
                    }),
                    el(ToggleControl, {
                        label: __('AJAX', 'syntekpro-forms'),
                        checked: ajax,
                        onChange: (val) => setAttributes({ ajax: val })
                    }),
                    el(RangeControl, {
                        label: __('Tabindex', 'syntekpro-forms'),
                        value: tabindex,
                        onChange: (val) => setAttributes({ tabindex: val }),
                        min: 0,
                        max: 100
                    }),
                    el(TextControl, {
                        label: __('Field Values', 'syntekpro-forms'),
                        value: fieldValues,
                        onChange: (val) => setAttributes({ fieldValues: val })
                    })
                )
            );
            
            // If no forms exist
            if (formsData.forms.length === 0) {
                return el(
                    'div',
                    {},
                    inspectorControls,
                    el(
                        Placeholder,
                        {
                            icon: 'feedback',
                            label: __('SyntekPro Forms', 'syntekpro-forms')
                        },
                        el('div', { style: { padding: '20px', textAlign: 'center' } },
                            el('p', {}, __('No forms found.', 'syntekpro-forms')),
                            el('a', {
                                href: formsData.pluginUrl ? '/wp-admin/admin.php?page=syntekpro-forms-new' : '#',
                                className: 'button button-primary',
                                target: '_blank'
                            }, __('Create Your First Form', 'syntekpro-forms'))
                        )
                    )
                );
            }
            
            // If form not selected
            if (!formId) {
                return el(
                    'div',
                    { className: 'syntekpro-forms-block-placeholder' },
                    inspectorControls,
                    el(
                        Placeholder,
                        {
                            icon: 'feedback',
                            label: __('SyntekPro Form', 'syntekpro-forms'),
                            instructions: __('Select a form to display', 'syntekpro-forms')
                        },
                        el(SelectControl, {
                            value: formId,
                            options: formOptions,
                            onChange: onChangeFormId,
                            className: 'syntekpro-forms-select'
                        })
                    )
                );
            }
            
            // Get selected form name
            const selectedForm = formsData.forms.find(f => f.value === formId);
            const formName = selectedForm ? selectedForm.label : __('Unknown Form', 'syntekpro-forms');
            
            // Form is selected - show preview
            return el(
                'div',
                { className: 'syntekpro-forms-block-wrapper' },
                inspectorControls,
                el(
                    'div',
                    { className: 'syntekpro-forms-block-preview' },
                    el('div', { className: 'syntekpro-forms-block-header' },
                        el('span', { className: 'dashicons dashicons-feedback' }),
                        el('strong', {}, __('SyntekPro Form:', 'syntekpro-forms') + ' '),
                        el('span', {}, formName)
                    ),
                    el('div', { className: 'syntekpro-forms-block-content' },
                        el(ServerSideRender, {
                            block: 'syntekpro-forms/form-selector',
                            attributes: attributes
                        })
                    )
                )
            );
        },
        
        save: function() {
            // Server-side rendering, so return null
            return null;
        }
    });
})();