SyntekPro Forms Add-ons

Place custom PHP add-ons in this folder. Each file will be loaded on plugins_loaded (priority 20).

Guidelines:
- One add-on per file, named with .php extension.
- Avoid naming conflicts with core plugin classes/functions.
- Use WordPress hooks/filters to extend behavior.
- You can add paths programmatically with the filter: syntekpro_forms_addons_paths
