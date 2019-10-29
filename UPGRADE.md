# Upgrade Guide

## Upgrading To 8.0 From 7.0

### Minimum & Upgraded Versions

Commit: https://github.com/laravel/passport/commit/97e3026790d953d7a67fe487e30775cd995e93df

The minimum Laravel version is now v6.0. The underlying `league/oauth2-server` was also updated to v8.

### Renderable Exceptions for OAuth Errors

PR: https://github.com/laravel/passport/pull/1066

OAuth exceptions can now be rendered. They will first be converted to Passport exceptions. If you are explicitly handling `League\OAuth2\Server\Exception\OAuthServerException` in your exception handler's report method you will need to check for an instance of `Laravel\Passport\Exceptions\OAuthServerException` instead.

### Fixed Credential Checking

PR: https://github.com/laravel/passport/pull/1040

In the previous version of Passport you could technically pass tokens granted by a different client type to the `CheckClientCredential` and `CheckClientCredentialForAnyScope` middlewares. This behavior is now fixed and an exception will be thrown if you attempt to pass an token generated by a different client type.