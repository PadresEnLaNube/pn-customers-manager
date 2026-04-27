=== PN Customers Manager ===
Contributors: felixmartinez, hamlet237
Donate link: https://padresenlanube.com/
Tags: crm, crm plugin, contact management, lead management, sales pipeline
Requires at least: 3.0
Tested up to: 6.9.1
Stable tag: 1.1.45
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
A powerful CRM with AI WhatsApp/Instagram chat, sales funnels, contact forms, email campaigns, and WooCommerce integration for WordPress.

== Description ==

PN Customers Manager is a powerful and intuitive Customer Relationship Management plugin designed specifically for WordPress. It helps businesses collect, organize, and manage their leads and customers directly from their website — without relying on external platforms.

With an easy-to-use dashboard and a complete set of sales, marketing, and AI-powered communication tools, PN Customers Manager allows you to centralize all interactions, track workflows, and automate repetitive tasks so you can focus on what matters most: growing your business.

Whether you run an online store, a service-based business, an agency, or a membership site, PN Customers Manager provides everything you need to turn visitors into loyal customers — including AI chatbots that talk to your clients on WhatsApp and Instagram.

= AI-Powered Conversations =

* **WhatsApp AI Integration**: Connect your WhatsApp Business account and let an OpenAI-powered chatbot handle customer conversations automatically. The AI knows your products, prices, shipping zones, and business hours. It can send product photos, process orders, and escalate complex queries — all through the WhatsApp Cloud API.

* **Instagram AI Integration**: Same AI-powered conversation engine for Instagram Direct Messages. Receive DMs, respond automatically with context-aware answers, and manage all conversations from your WordPress dashboard.

* **WooCommerce Product Knowledge**: The AI automatically loads your top 50 best-selling products (with variations, prices, stock status, and images) so it can answer product questions, recommend items, and share add-to-cart links.

* **Guided Product Recommendations**: Enable a multi-step recommendation protocol where the AI asks qualifying questions (budget, category, preferences) before suggesting products — resulting in more relevant recommendations and higher conversion rates.

* **Order Acceptance Protocol**: The AI can take orders directly in chat with a two-step confirmation flow (summary + explicit confirmation), then notify you by email with the order details.

* **Special Order Forwarding**: For requests that cannot be fulfilled through a standard purchase link (B2B, bulk orders, custom products), the AI collects all the details and forwards them by email to a configurable list of internal users and external email addresses.

* **Shipping Zone Awareness**: The AI automatically knows your WooCommerce shipping zones, methods, and costs. It can answer shipping questions instantly, including postal code validation and free shipping thresholds.

* **Blog & Page Context**: Inject your WordPress posts and pages into the AI's knowledge base so it can reference your content and share links with customers.

* **Conversation Management Dashboard**: View, search, close, delete, and reset all WhatsApp and Instagram conversations from your admin panel. Full chat history with timestamps and role indicators.

= Core CRM Features =

* **Funnel Management System**: Create and manage sales funnels with customizable stages. Each funnel can include multimedia content (video, audio, images), dates, times, and custom taxonomies for better organization. Funnels support hierarchical structures and can be linked to organizations for complete pipeline tracking.

* **Organization Management**: Comprehensive organization/company management with advanced features including:
  * Owner and collaborator assignment
  * Funnel linking and stage tracking
  * Contact information management (phone, email, website, LinkedIn)
  * Billing information fields
  * Lead source tracking and lifecycle stage management
  * Priority and health scoring
  * Lead scoring system
  * Tags and notes for better organization
  * Geographic data (country, region, city, address, postal code)
  * Industry and segment classification
  * Team size and annual revenue tracking

* **Contact Messages**: A built-in contact form system with inbox management. Messages are organized in Inbox and Spam tabs with unread counters, read/unread status, mark as spam, and full sender details including source page and IP address.

* **Akismet Spam Protection**: Contact form submissions are automatically checked against Akismet for spam detection and filtering.

= Email Campaigns =

* **Campaign Management**: Create and send email campaigns directly from WordPress with template support and audience segmentation. Each campaign includes a content preview with a copy button for manual sending.

* **Mail Statistics**: Track email campaign performance with open rates, click rates, bounce rates, and unsubscribe tracking on a dedicated dashboard.

= Business Projections =

* **Automated Data Collection**: Automatic daily, hourly, or weekly snapshots of your CRM metrics, user stats, email performance, and social media followers via scheduled cron jobs.

* **Social Media Tracking**: Connect Instagram Graph API, Facebook Insights, and Twitter/X API v2 to track followers, impressions, reach, and engagement over time.

* **Manual Projections**: Set future targets with expected values and dates. Compare projections vs actual performance with deviation percentage calculations.

* **Visual Dashboard**: Interactive Chart.js line charts with projection overlays, metric selectors, source tabs (CRM, Users, Email, Social Media), and status cards showing active integrations.

= Advanced Form Builder =

A powerful form system that supports multiple field types:

* Text inputs (text, email, url, password, date, time, color, hidden)
* Textarea and rich text editor (WYSIWYG)
* Select dropdowns (single and multiple selection)
* Checkboxes and radio buttons
* Range sliders with custom min/max labels
* Star rating system
* File uploads (images, videos, audio, documents)
* Image galleries with multiple image support
* Audio recorder with transcription capabilities
* Tags input with autocomplete suggestions
* Conditional fields (parent/child relationships)
* Multiple field groups with drag-and-drop reordering
* HTML content blocks
* Form validation and sanitization
* Forms can save data to WordPress users, custom post types, or options

= Referral System =

* Enable referral tracking for your organizations
* Top referrers ranking display
* Customizable referral share text
* QR code generation with custom logo
* Configurable reminder frequency and limits
* Business card phrases management

= User Management =

* Custom user roles: Administrator, CRM Manager, Commercial, and Client
* CRM Manager role with full access to CRM features (messages, statistics, projections, conversations, commercial agents) without requiring full admin privileges
* User creation and management functions
* User metadata handling
* User login tracking
* User profile management with custom fields

= Shortcodes =

* `[pn-customers-manager-funnel]` — Display individual funnels with all their details
* `[pn-customers-manager-funnel-list]` — Display lists of funnels with customizable layouts
* `[pn-customers-manager-organization-list]` — Display lists of organizations
* `[pn-customers-manager-client-form]` — Public client registration form (also available as Gutenberg block)
* `[pn-customers-manager-contact-form]` — Contact form with Akismet spam protection
* `[pn-customers-manager-call-to-action]` — Call-to-action blocks with icons, titles, content, and customizable buttons
* `[pn-customers-manager-button]` — Customizable buttons (solid/outline/transparent, size, color, icon, image) with Gutenberg block support
* `[pn-customers-manager-whatsapp-ai]` — WhatsApp conversations front-end panel (admin only)
* `[pn-customers-manager-instagram-ai]` — Instagram conversations front-end panel (admin only)

= Settings & Configuration =

* Dedicated settings page in WordPress admin
* API configuration for OpenAI, WhatsApp, Instagram, and social media platforms
* Built-in API connection testing tools
* Settings export to JSON and import from JSON
* Recommended plugins discovery with one-click install and activate
* Customizable URL slugs for custom post types
* Plugin activation redirect to settings

= More Features =

* **Popup System**: Modal popups for content display, video popups for embedded media, customizable options (close button, overlay close, ESC key), and a JavaScript API for programmatic control.

* **Template System**: Custom single and archive templates for funnels and organizations with a template override system.

* **Internationalization**: Full translation support with Polylang integration for multilingual sites. Spanish translation included.

* **Security**: Nonce verification, input sanitization, capability-based access control, secure AJAX endpoints, and password strength checking.

* **Developer-Friendly**: Clean object-oriented code, extensible hook system, filter and action hooks throughout, and custom capabilities for fine-grained permissions.

= PN Plugin Ecosystem =

PN Customers Manager is part of a modular plugin ecosystem by Padres en la Nube. Each plugin is independent but designed to work seamlessly together. You can discover, install, and activate companion plugins directly from the Settings page.

**MailPN — Email Marketing & Newsletters**
The shared email engine for the entire ecosystem. Create and schedule email campaigns, personalize content with dynamic fields, track open and click-through rates in real time, set up automated drip campaigns, and connect with popular email services or your own SMTP server. PN Customers Manager uses MailPN as its campaign sending and tracking backend.

**UsersPN — User Management & Registration**
Complete frontend user management: customizable registration and login forms, advanced profile editing with tabbed interface, profile completion tracking, custom avatar system with Gravatar fallback, CSV import/export, and auto-login for support. Includes Google reCAPTCHA v3, honeypot, rate limiting, and Akismet integration. Provides the user layer that feeds contacts into the CRM. Notifications are powered by MailPN.

**PN Tasks Manager — Task & Project Management**
Create tasks, assign them to users, track time, and organize workflow with an interactive calendar (day, week, month, year views). Supports recurring tasks, task categories with custom icons and colors, public/joinable tasks, ICS calendar export (Google Calendar, Outlook, Apple Calendar), user rankings based on completed hours, and file attachments. Email notifications powered by MailPN.

**PN Cookies Manager — Cookie Consent & GDPR Compliance**
Lightweight cookie consent plugin supporting GDPR, CCPA, LGPD, and ePrivacy Directive. Three banner layouts (full-width bar, compact box, floating card), a cookie preferences panel with category toggles (Necessary, Functional, Analytics, Performance, Advertising), a cookie registry with quick-add presets for GA4, Google Ads, Facebook Pixel, and more. Built-in Google Consent Mode v2 integration. Ensures your CRM tracking and analytics cookies are properly disclosed and consent is collected.


== Credits ==
This plugin stands on the shoulders of giants

FancyBox v3.5.7
Licensed GPLv3 for open source use or fancyBox Commercial License for commercial use
Copyright 2019 fancyApps
http://fancyapps.com/fancybox/
https://github.com/fancyapps/fancybox/blob/master/dist/jquery.fancybox.js
https://github.com/fancyapps/fancybox/blob/master/dist/jquery.fancybox.css

Owl Carousel v2.3.4
Licensed under: SEE LICENSE IN https://github.com/OwlCarousel2/OwlCarousel2/blob/master/LICENSE
Copyright 2013-2018 David Deutsch
https://owlcarousel2.github.io/OwlCarousel2/
https://github.com/OwlCarousel2/OwlCarousel2/blob/develop/dist/owl.carousel.js

Trumbowyg v2.27.3 - A lightweight WYSIWYG editor
alex-d.github.io/Trumbowyg/
License MIT - Author : Alexandre Demode (Alex-D)
https://github.com/Alex-D/Trumbowyg/blob/develop/src/ui/sass/trumbowyg.scss
https://github.com/Alex-D/Trumbowyg/blob/develop/src/trumbowyg.js

Chart.js
Licensed under the MIT License
https://www.chartjs.org/


== Installation ==

1. Upload the `pn-customers-manager` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the PN Customers Manager settings page to configure your API keys and preferences

== Frequently Asked Questions ==

= How do I install the PN Customers Manager plugin? =

You can either upload the plugin files to the /wp-content/plugins/pn-customers-manager directory, or install the plugin through the WordPress plugins screen directly. After uploading, activate the plugin through the 'Plugins' screen in WordPress.

= How do I set up the WhatsApp AI chatbot? =

Go to PN Customers Manager > Settings > WhatsApp AI section. Enter your WhatsApp Business Access Token, Phone Number ID, and Verify Token. Use the provided Webhook URL to configure your Meta app. You can test the connection with the built-in test buttons.

= How do I set up the Instagram AI chatbot? =

Go to PN Customers Manager > Settings > Instagram AI section. Enter your Instagram Access Token (with instagram_manage_messages permission), Instagram Page ID, and Verify Token. Configure the Webhook URL in your Meta app settings.

= Does the AI chatbot work with WooCommerce? =

Yes. When you enable the WooCommerce integration in the funnel builder, the AI automatically loads your product catalog (up to 50 best-selling products) with prices, variations, images, and stock status. It can recommend products, share add-to-cart links, send product photos, and even take orders.

= Can I customize the AI's behavior? =

Yes. Each funnel node can have its own system prompt, AI model selection (GPT-4o, GPT-4o-mini, GPT-4 Turbo), and temperature setting. You can also add business context, opening hours, shipping information, and knowledge base content that the AI will use in conversations.

= Can I customize the look and feel of my listings? =

Yes, you can customize the appearance of your listings by modifying the CSS styles provided in the plugin. Additionally, you can enqueue your own custom styles to override the default plugin styles.

= Can I use this plugin with any WordPress theme? =

Yes, the PN Customers Manager plugin is designed to be compatible with any WordPress theme. However, some themes may require additional customization to ensure the plugin's styles integrate seamlessly.

= Is the plugin translation-ready? =

Yes, the PN Customers Manager plugin is fully translation-ready. Spanish translation is included. You can use translation plugins such as Loco Translate to translate the plugin into your desired language.

= How do I export and import my settings? =

Use the Export and Import buttons in the settings footer bar. Export saves all your plugin settings as a JSON file. Import lets you restore settings from a previously exported file.

= How do I track my business projections? =

Go to PN Customers Manager > Projections. Connect your social media APIs in Settings, and the plugin will automatically collect snapshots of your CRM metrics, user stats, email performance, and social media data. You can also create manual projections with target values and dates.

= How do I get support for the PN Customers Manager plugin? =

For support, you can visit the plugin's support forum on the WordPress.org website or contact the plugin author directly at info@padresenlanube.com.

= Is the plugin compatible with the latest version of WordPress? =

The PN Customers Manager plugin is tested with the latest version of WordPress. However, it is always a good practice to check for any compatibility issues before updating WordPress or the plugin.

= How do I uninstall the plugin? =

Go to the 'Plugins' screen in WordPress, find the PN Customers Manager plugin, and click 'Deactivate'. After deactivating, you can click 'Delete' to remove the plugin and its files from your site. Note that this will not delete your data, but you should back up your data before uninstalling any plugin.


== Changelog ==

= 1.0.0 =

Hello world!
