# NGO Tools Newsletter Module - Quick Start Guide

## Installation Steps

1. **Copy the module to your Drupal site**:
   ```bash
   # From this directory, copy to your Drupal modules/custom folder
   cp -r . /path/to/your/drupal/modules/custom/ngo_tools_newsletter
   
   # Or if you're in your Drupal root:
   # cp -r /path/to/this/module ./modules/custom/ngo_tools_newsletter
   ```

2. **Enable the module**:
   ```bash
   # Using Drush
   drush en ngo_tools_newsletter -y
   drush cr
   
   # Or via UI: Admin > Extend > Enable "NGO Tools Newsletter"
   ```

3. **Configure API settings**:
   - Go to: `/admin/config/system/ngo-tools-newsletter`
   - Enter your API Bearer Token (from ngo.tools profile)
   - Enter your organization name (e.g., `yourorg.ngo.tools`)
   - Select the newsletter segment
   - Save configuration

4. **Place the block**:
   - Go to: `/admin/structure/block`
   - Click "Place block" in your desired region
   - Find "NGO Tools Newsletter Signup" and place it
   - Configure and save

## Module Structure

```
ngo_tools_newsletter/
├── config/schema/          # Configuration schema
├── css/                    # Stylesheets
├── js/                     # JavaScript files
├── src/
│   ├── Form/              # Form classes
│   ├── Plugin/Block/      # Block plugin
│   └── Service/           # Services (API, Encryption)
├── templates/             # Twig templates
├── *.info.yml            # Module info
├── *.libraries.yml       # Asset libraries
├── *.module              # Module hooks
├── *.routing.yml         # Routes
├── *.services.yml        # Service definitions
└── README.md             # Full documentation
```

## Key Features

✅ Block plugin for easy placement
✅ Encrypted API token storage
✅ Dynamic segment selection
✅ Spam protection (honeypot)
✅ Theme-independent design
✅ Customizable Twig template
✅ Comprehensive error handling
✅ Form validation

## Testing the Module

After installation and configuration:

1. Visit a page where you placed the block
2. Fill out the form with test data
3. Submit and verify the success message
4. Check your ngo.tools account to confirm the subscription

## Troubleshooting

- **Segments not loading**: Check API token and organization name
- **Form not submitting**: Clear cache (`drush cr`)
- **Styling issues**: Override CSS in your theme
- **API errors**: Check Drupal logs at `/admin/reports/dblog`

## Need Help?

See the full README.md for detailed documentation, customization options, and troubleshooting tips.
