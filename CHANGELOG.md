# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-12-12

### Added
- Initial release
- Consent model with polymorphic relationships
- ConsentType model for managing consent types
- ConsentSource model for tracking consent sources
- Consentable trait for any Eloquent model
- ConsentService for consent creation, verification, and management
- ConsentValidator for payload validation
- Automatic UUID generation for consents
- Soft deletes with revocation tracking
- Auto-timestamps for consent and revocation dates
- Configurable consent types and sources
- Complete test coverage (124 tests, 240 assertions)
- Migrations, factories, and seeders
- Laravel service provider with auto-discovery
- GDPR-compliant consent tracking

[Unreleased]: https://github.com/blamodex/laravel-consents/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/blamodex/laravel-consents/releases/tag/v1.0.0
