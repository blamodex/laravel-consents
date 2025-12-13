# Blamodex Laravel Consents

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blamodex/laravel-consents.svg?style=flat-square)](https://packagist.org/packages/blamodex/laravel-consents)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/blamodex/laravel-consents/ci.yml?label=tests&style=flat-square)](https://github.com/blamodex/laravel-consents/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/blamodex/laravel-consents.svg?style=flat-square)](https://packagist.org/packages/blamodex/laravel-consents)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg?style=flat-square)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg?style=flat-square)](https://www.php.net/)

A lightweight Laravel package to add GDPR-compliant consent management capabilities to any Eloquent model using polymorphic relationships.

---

## Table of Contents

- [Features](#-features)
- [Installation](#-installation)
- [Configuration](#️-configuration)
- [Usage](#-usage)
- [Testing](#-testing)
- [Project Structure](#-project-structure)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🚀 Features

- Attach consent functionality to any model using a trait
- Polymorphic support for multiple model types
- Consent types (email-marketing, sms-marketing, data-processing, etc.)
- Consent sources (web-form, mobile-app, email, phone, in-person)
- Automatic UUID generation for consents
- Soft deletes with revocation tracking
- Auto-timestamps for consent and revocation dates
- Clean architecture: trait, models, service, validator
- GDPR-compliant consent tracking

---

## 📦 Installation

Install the package with Composer:

\`\`\`bash
composer require blamodex/laravel-consents
\`\`\`

Publish the config file:

\`\`\`bash
php artisan vendor:publish --tag=blamodex-consent-config
\`\`\`

Run the migrations:

\`\`\`bash
php artisan migrate
\`\`\`

---

## ⚙️ Configuration

Configuration lives in \`config/consent.php\`:

\`\`\`php
return [
    'tables' => [
        'consents' => 'consents',
        'consent_types' => 'consent_types',
        'consent_sources' => 'consent_sources',
    ],
    'default_types' => [
        'email-marketing' => 'Email Marketing',
        'sms-marketing' => 'SMS Marketing',
        'data-processing' => 'Data Processing',
        'third-party-sharing' => 'Third Party Sharing',
    ],
    'default_sources' => [
        'web-form' => 'Web Form',
        'mobile-app' => 'Mobile App',
        'email' => 'Email',
        'phone' => 'Phone',
        'in-person' => 'In Person',
    ],
    'retention_days' => null,
];
\`\`\`

---

## 🧩 Usage

### 1. Use the Consentable trait

\`\`\`php
use Blamodex\Consent\Traits\Consentable;
use Blamodex\Consent\Contracts\ConsentableInterface;

class User extends Model implements ConsentableInterface
{
    use Consentable;
}
\`\`\`

### 2. Create consents

\`\`\`php
$user = User::find(1);

$consents = $user->createConsents([
    'consent-source' => 'web-form',
    'consents' => [
        ['slug' => 'email-marketing', 'state' => 'accepted'],
        ['slug' => 'sms-marketing', 'state' => 'rejected']
    ]
]);
\`\`\`

### 3. Check consent status

\`\`\`php
if ($user->consentedTo('email-marketing')) {
    // User has consented to email marketing
}
\`\`\`

### 4. Validate consent payload

\`\`\`php
use Blamodex\Consent\Validators\ConsentValidator;

$validator = new ConsentValidator();
if ($validator->passes($payload)) {
    $consents = $user->createConsents($payload);
} else {
    $errors = $validator->errors();
}
\`\`\`

### 5. Use the ConsentService directly

\`\`\`php
use Blamodex\Consent\Services\ConsentService;

$consentService = app(ConsentService::class);
$consents = $consentService->createConsents($user, $payload);
$hasConsented = $consentService->consentedToByConsentable($user, 'email-marketing');
$formatted = $consentService->formatConsentsFrontend($user);
\`\`\`

---

## 🧪 Testing

Run tests:

\`\`\`bash
composer test
\`\`\`

Check code style:

\`\`\`bash
composer lint
\`\`\`

Check code style and fix:

\`\`\`bash
composer lint:fix
\`\`\`

Check static analysis:

\`\`\`bash
composer analyze
\`\`\`

Check coverage:

\`\`\`bash
composer test:coverage
\`\`\`

---

## 📁 Project Structure

\`\`\`
src/
├── Models/
│   ├── Consent.php
│   ├── ConsentType.php
│   └── ConsentSource.php
├── Services/
│   └── ConsentService.php
├── Validators/
│   └── ConsentValidator.php
├── Traits/
│   └── Consentable.php
├── Contracts/
│   └── ConsentableInterface.php
├── config/
│   └── consent.php
└── database/
    ├── migrations/
    ├── factories/
    └── seeders/

tests/
├── Unit/
│   ├── ConsentServiceTest.php
│   ├── ConsentTest.php
│   ├── ConsentTypeTest.php
│   ├── ConsentSourceTest.php
│   ├── ConsentValidatorTest.php
│   └── ConsentableTraitTest.php
├── Fixtures/
│   └── DummyConsentUser.php
└── TestCase.php
\`\`\`

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

---

## 📄 License

MIT © [Blamodex](https://github.com/blamodex)

For more information, see the [LICENSE](LICENSE) file.

---

## 🔗 Links

- [Report a Bug](https://github.com/blamodex/laravel-consents/issues)
- [Request a Feature](https://github.com/blamodex/laravel-consents/issues)
- [View Changelog](CHANGELOG.md)
