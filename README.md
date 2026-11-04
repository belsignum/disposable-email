# disposable-email
A service extension to compile lists of disposable and free mail providers, offering a simple way to validate email addresses against them.

## install
````
composer req belsignum/disposable-email
````

## Tests
Run tests from extension directory:

````
vendor/bin/phpunit -c Build/phpunit/UnitTests.xml
vendor/bin/phpunit -c Build/phpunit/FunctionalTests.xml
````

Alternatively via composer scripts:

````
composer test:unit
composer test:functional
````

The CI workflow runs both test suites (`Unit` and `Functional`).

## Extension configuration options
**basic.type - Lists to use**
- Disable validation
- Disposable email provider
- Free email provider
- Disposable & free email provider
- Custom lists only

**basic.customLists - Custom Lists (comma separated list of public URIs)**
Only absolute `https://` URLs are supported.

**powermail.overloadEmailValidation - Overload Email Validation, else adds additional validation rule**

## Powermail validation rule
- Disposable Email
- Or overload Email validation rule - Set extension configuration powermail.overloadEmailValidation=1

## Powermail list type override via TypoScript
Use TypoScript to override the list type for Powermail validation.

```typoscript
plugin.tx_powermail.settings.setup.tx_disposableemail.overrideExtensionSettings {
  # one override for current Powermail context
  type = disposable

  # optional form specific mapping
  typeByForm {
    # disable disposable email validation for this form
    100 = disable

    123 = disposable
    456 = freemail
  }
}
```

`typeByForm` has priority over `type`.
In `typeByForm`, use the UID of the default language form record. Localized forms are resolved via `l10n_parent`.
Supported values are `disable`, `disposable`, `freemail`, `both`, `customListsOnly`.

## CMS-Form
- Validator for E-Mail Field comparing E-Mail Address against lists of disposable and free mail providers
- Validator for E-Mail Field comparing username or email address in fe_users
- Optional validator option `listType` (`disable|disposable|freemail|both|customListsOnly`) to override extension configuration per field

## Whitelist and blacklist (backend module)
Module **System > Email domain rules** maintains domains that are always accepted or always rejected:

- **Whitelist** (`allow`): the domain is accepted even if an imported provider list contains it.
- **Blacklist** (`deny`): the domain is rejected even if no provider list contains it.
- Rules apply to Powermail and CMS-Form validation. With list type `disable` no rule is applied.
- Input may be a domain or a full email address; it is stored as normalized domain (lowercase, punycode).
  Invalid and duplicate domains are rejected on save. Matching is exact, subdomains need their own entry.
- Hidden rules are ignored. Rules are stored in `tx_disposableemail_domain_rule` on the root level (pid 0)
  and are not touched by `disposable-email:update`.
- **Check domain** shows which imported lists contain a domain, whether a rule exists, and the effective result
  for the configured list type.

Editors need access to the module and modify permission for the table `tx_disposableemail_domain_rule`.

## List storage and deduplication
- Domains are stored with `provider_type` (`disposable`, `freemail`, `custom`).
- Deduplication is done per `provider_type`.
- Custom lists are stored as `custom` and are checked in addition to selected built-in list types.
- DB-level uniqueness is enforced by `uniq_domain_provider_type (domain, provider_type)`.

## Update list

### CLI
````
php vendor/bin/typo3 disposable-email:update
````

### Scheduler
1. Add new Scheduler Task
2. Choose Class "Execute console commands"
3. Choose Schedulable Command "disposable-email:update: Updates the current list with the remote endpoint. updates are provided usually weekly."

## Changelog
See [CHANGELOG.md](./CHANGELOG.md).
