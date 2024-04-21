# G Suite Integration

### INTRODUCTION

Light-weight G Suite integration that uses the
[PHP client library](https://github.com/googleapis/google-api-php-client)
to provide developer friendly services giving access to G Suite features.

This module is intended to be used with a
[G Suite service account](https://support.google.com/a/answer/7378726?hl=en)
to authenticate with the G Suite services.

### REQUIREMENTS

 - Google's [PHP client library]
  (https://github.com/googleapis/google-api-php-client)

### INSTALLATION

The module should be installed via composer using
`composer require drupal/gsuite`
This will ensure that the correct Google API client dependency is installed.

### CONFIGURATION

No UI is provided for this module, configuration is done via the
`local.settings.php` file.

You must add the path to the json credentials you downloaded as part of your
service account creation.

```
$settings['gsuite'] = [
  'auth_config' => '../gsuite_credentials.json'
];
```
