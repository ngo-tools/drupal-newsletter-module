# NGO Tools Newsletter Module

A Drupal 10/11 module that provides newsletter signup functionality using the ngo.tools API. This module includes a configurable block plugin that can be placed in any region of your Drupal site.

## Features

- **Block Plugin**: Easy-to-use newsletter signup block that can be placed in any theme region
- **API Configuration**: Admin interface for configuring API credentials and settings
- **Secure Token Storage**: Bearer tokens are encrypted using AES-256-CBC encryption
- **Segment Selection**: Dynamic dropdown to select newsletter segments from your ngo.tools account
- **Spam Protection**: Built-in honeypot field to prevent spam submissions
- **Theme Independent**: Works with any Drupal 10/11 theme
- **Customizable Template**: Includes Twig template for easy styling customization
- **Form Validation**: Client and server-side validation for all form fields
- **Error Handling**: Comprehensive error handling with user-friendly messages

## Requirements

- Drupal 10 or 11
- PHP 7.4 or higher with OpenSSL extension
- Active ngo.tools account with API access

## Installation

### Manual Installation

1. **Download or clone** this module into your Drupal installation's `modules/custom` directory:
   ```bash
   cd /path/to/drupal/modules/custom
   git clone <repository-url> ngo_tools_newsletter
   ```

2. **Enable the module** using Drush or the Drupal admin interface:
   ```bash
   drush en ngo_tools_newsletter
   ```

   Or navigate to **Administration > Extend** and enable "NGO Tools Newsletter".

3. **Clear the cache**:
   ```bash
   drush cr
   ```

## Configuration

### 1. Configure API Settings

Navigate to **Administration > Configuration > System > NGO Tools Newsletter Settings** (`/admin/config/system/ngo-tools-newsletter`)

#### API Bearer Token
- Generate your API token in your ngo.tools profile under the "API Token" section
- Enter the token in the configuration form
- The token will be encrypted before being stored in the database

#### Organization Name
- Enter your organization name (the subdomain part of your ngo.tools URL)
- Example: If your URL is `examplename.ngo.tools/app/dashboard`, enter `examplename.ngo.tools`
- The organization name must end with `.ngo.tools`

#### Select Newsletter
- Once you've entered valid credentials, a dropdown will appear with available newsletters in your ngo.tools instance
- Select the newsletter where subscriptions should be added
- Save the configuration

### 2. Place the Block

1. Navigate to **Administration > Structure > Block layout** (`/admin/structure/block`)
2. Click **Place block** in the region where you want the newsletter signup form
3. Find **NGO Tools Newsletter Signup** and click **Place block**
4. Configure block settings (title, visibility, etc.) as needed
5. Click **Save block**

## Usage

Once configured and placed, the newsletter signup block will display a form with the following fields:

- **First Name** (required)
- **Last Name** (required)
- **Email** (required)

When a user submits the form:
1. The data is validated (spam check, email format, required fields)
2. A POST request is sent to the ngo.tools API endpoint
3. The user receives a success or error message
4. On success, the contact is added to the selected segment

## API Integration

The module integrates with the ngo.tools API v2:

- **Segments endpoint**: `https://{organization}.ngo.tools/api/v2/contact-segments`
- **Subscribe endpoint**: `https://{organization}.ngo.tools/api/v2/contact-segments/{segment_id}/subscribe`

### Request Format

```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john.doe@example.com"
}
```

### Authentication

All API requests use Bearer token authentication:
```
Authorization: Bearer {your-api-token}
```

## Customization

### Styling

The module includes default CSS in `css/newsletter-form.css`. You can:

1. **Override in your theme**: Add custom CSS in your theme to override default styles
2. **Modify the template**: Copy `templates/ngo-tools-newsletter-form.html.twig` to your theme and customize
3. **Disable default CSS**: Remove the library attachment in your theme's `.info.yml` file

### Template Variables

Available in `ngo-tools-newsletter-form.html.twig`:

- `form.first_name`: First name field
- `form.last_name`: Last name field
- `form.email`: Email field
- `form.actions`: Submit button
- `form.hp`: Honeypot field (hidden)

### Example Theme Override

```twig
{# yourtheme/templates/ngo-tools-newsletter-form.html.twig #}
<div class="custom-newsletter-form">
  <h2>Subscribe to our Newsletter</h2>
  {{ form.hp }}
  <div class="form-row">
    {{ form.first_name }}
    {{ form.last_name }}
  </div>
  {{ form.email }}
  {{ form.actions }}
  {{ form|without('hp', 'first_name', 'last_name', 'email', 'actions') }}
</div>
```

## Security

- **Token Encryption**: API bearer tokens are encrypted using AES-256-CBC with Drupal's hash salt as the key
- **CSRF Protection**: Forms use Drupal's built-in CSRF token protection
- **Honeypot**: Spam prevention using a hidden honeypot field
- **Input Sanitization**: All user input is sanitized before processing
- **SSL Verification**: API requests verify SSL certificates (configurable)

## Troubleshooting

### "The token seems to be expired"
- Generate a new API token in your ngo.tools profile
- Update the token in the module configuration

### "Newsletter segment is not configured"
- Ensure you've selected a segment in the configuration form
- Verify that segments are loading (check if dropdown appears)

### "Failed to subscribe: Endpoint not found"
- Verify your organization name is correct
- Ensure it ends with `.ngo.tools`
- Check that the segment ID is valid

### Segments not loading
- Verify the API bearer token is valid
- Check the organization name format
- Review the Drupal logs at **Reports > Recent log messages**

## Development

### File Structure

```
ngo_tools_newsletter/
├── config/
│   └── schema/
│       └── ngo_tools_newsletter.schema.yml
├── css/
│   └── newsletter-form.css
├── js/
│   └── newsletter-form.js
├── src/
│   ├── Form/
│   │   ├── NewsletterSignupForm.php
│   │   └── SettingsForm.php
│   ├── Plugin/
│   │   └── Block/
│   │       └── NewsletterSignupBlock.php
│   └── Service/
│       ├── EncryptionService.php
│       └── NgoToolsApiService.php
├── templates/
│   └── ngo-tools-newsletter-form.html.twig
├── ngo_tools_newsletter.info.yml
├── ngo_tools_newsletter.libraries.yml
├── ngo_tools_newsletter.module
├── ngo_tools_newsletter.routing.yml
├── ngo_tools_newsletter.services.yml
└── README.md
```

### Services

- `ngo_tools_newsletter.encryption`: Handles encryption/decryption of sensitive data
- `ngo_tools_newsletter.api`: Manages all API communication with ngo.tools

## Support

For issues, questions, or contributions:
- Check the Drupal logs for error messages
- Review the ngo.tools API documentation
- Ensure your API credentials are valid and have proper permissions

## License

This module is released under the GPL-2.0-or-later license, consistent with Drupal core.

## Credits

Developed based on the ngo.tools WordPress plugin functionality, adapted for Drupal 10/11 using best practices and the Drupal API.
