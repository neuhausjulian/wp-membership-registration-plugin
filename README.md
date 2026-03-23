# WP Membership Registration Plugin

A WordPress plugin for digital membership registration. Visitors fill in a form on your website, receive a pre-filled PDF membership form by email, and the club admin gets a copy. No paid software, no spreadsheets.

## Requirements

- WordPress 6.0 or later
- PHP 8.0 or later
- An SMTP plugin or hosting SMTP configuration for email delivery

## Installation

1. Download the plugin zip from the [Releases page](../../releases).
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Upload the zip and click **Activate**.

Or place the plugin folder in `wp-content/plugins/wp-membership-registration/` and activate it from the Plugins list.

## Configuration

Go to **Settings → Membership Registration** in your WordPress admin. The settings page has four tabs:

### Form Fields

Define the registration form fields that visitors fill in. Each field has:
- **Label** — the field name shown on the form and in the PDF
- **Type** — text, email, date, tel, textarea, or select
- **Required** — whether the field must be filled before submission

Use the **Add field**, **Remove**, and drag handles to manage fields. Click **Save** when done.

The first field with type **email** is used as the member's email address for the confirmation email.

### PDF Branding

Customize how the generated PDF looks:
- **Club name** — shown as the heading at the top of the PDF
- **Document title** — subtitle below the club name (e.g. "Membership Application")
- **Accent color** — hex color used for headings
- **Club logo** — displayed in the PDF header (upload via Media Library)
- **Form information** — rich text shown below the form fields on both the PDF and the registration form. Use this for GDPR notice, membership conditions, or any explanatory text.
- **Footer text** — small text printed at the bottom of page 1
- **Page 2 content** — rich text printed on page 2 of the PDF (use for AGBs, Datenschutzerklärung, bank details)

### Email Settings

Enter the admin email addresses that receive a copy of each PDF after submission — one address per line. Click **Send test email** to verify your SMTP connection is working before going live.

### Form Settings

- **Consent checkbox text** — the label shown next to the GDPR consent checkbox. Submission is blocked unless this is checked.
- **Success message** — the message shown in-place after a successful form submission.

## Shortcodes

### Registration form

```
[membership_form]
```

Embed the registration form on any page or post. Add the shortcode to a page — the form is rendered automatically from your Form Fields settings.

### Blank PDF download

```
[membership_form download="blank"]
```

Renders a download link for a blank (empty) version of the membership form PDF. Members who prefer to fill in a paper form can download and print this version. The blank PDF contains interactive fillable fields.

## How It Works

1. A visitor fills in and submits the registration form.
2. The plugin generates a pre-filled PDF with the submitted data.
3. The visitor receives the PDF by email as confirmation.
4. All configured admin recipients also receive a copy of the PDF.
5. The PDF is deleted from the server immediately after sending.

## Privacy

- No form submission data is stored in the database — the plugin is stateless.
- The generated PDF exists only in the server's temp directory for the seconds it takes to email it, then it is deleted.
- You are responsible for ensuring your privacy policy covers the data processing described above.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
