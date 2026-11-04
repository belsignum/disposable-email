# Changelog

This changelog documents changes for `belsignum/disposable-email`.

## Unreleased

### Added
- Backend module *System > Email domain rules* to maintain a whitelist (always accept) and a blacklist
  (always reject) of email domains, stored in `tx_disposableemail_domain_rule`.
- Domain check in the module: imported lists containing a domain, matching rule and effective result.
- `DisposableEmailService::findDomainRule()` and `DisposableEmailService::getProviderTypesForDomain()`.

### Changed
- `DisposableEmailService::checkDomain()` applies whitelist and blacklist rules before the provider lists.
- Free email source `okutbay/free_email_provider_domains` (~96,600 domains, many non-freemail domains such as `sfs.com`)
  replaced by [Kikobeats/free-email-domains](https://github.com/Kikobeats/free-email-domains)
  (HubSpot free domains plus curated additions, DNS validated, rebuilt daily).
- Provider lists may be JSON arrays of strings (`application/json` accepted), parsed by `ProviderListParser`.

## 12.2.0 - 2026-02-25

### Ported from v10
- Feature set from `10.1.0` (`372da51`):
  - Powermail list type overrides via TypoScript:
    - `plugin.tx_powermail.settings.setup.tx_disposableemail.overrideExtensionSettings.type`
    - `plugin.tx_powermail.settings.setup.tx_disposableemail.overrideExtensionSettings.typeByForm.<formUid>`
  - Provider type storage in `tx_disposableemail_list.provider_type` with unique key
    `uniq_domain_provider_type (domain, provider_type)`.
- Bugfix from `10.1.1` (`1e1563d`):
  - Prevented scheduler import failures on duplicate `(domain, provider_type)` entries
    caused by edge-case domain normalization and DB collation behavior.

### Added
- Unit tests for `CMS-Form` disposable email validator:
  - `listType` override handling
  - `disable` behavior
  - list type specific error key handling
- Functional tests for:
  - provider type aware domain checks in `DisposableEmailService`
  - schema uniqueness behavior for `(domain, provider_type)`

### Changed
- `DisposableEmailService` supports optional list type filtering while defaulting to extension configuration.
- Import command persists domains by `provider_type` and deduplicates per type (`disposable`, `freemail`, `custom`).
- `typeByForm` resolves localized Powermail forms via default language record (`l10n_parent`).
- `typeByForm.<formUid> = disable` disables validation for a specific form.
- Global `basic.type = disable` is supported.
- Overload validation error messages vary by effective list type (`disposable`, `freemail`, `both`, `custom`).
- Provider list endpoints are restricted to absolute HTTPS URLs.
- Functional test bootstrap now provides SQLite defaults when no explicit test DB environment variables are set.
- GitHub Actions CI now runs both unit tests and functional tests.
