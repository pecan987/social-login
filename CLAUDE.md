# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nette Framework addon for social login authentication with Facebook, Google, and Seznam.cz. Provides unified interface for OAuth authentication flows.

## Commands

```bash
# Install dependencies
composer install

# Run tests (Nette Tester)
./vendor/bin/tester -p php-cgi -c ./tests/php.ini -s ./tests/
```

## Architecture

The library uses a class hierarchy under the `Vencax` namespace in `src/`:

- **SocialLogin** - Main entry point, instantiates and holds references to individual login providers based on config
- **BaseLogin** - Base class with shared functionality (cookie management for tracking last-used service)
- **FacebookLogin** - Facebook OAuth using `facebook/graph-sdk`, includes constants for Graph API user fields
- **GoogleLogin** - Google OAuth using `google/apiclient`
- **SeznamLogin** - Seznam.cz OAuth using cURL, scope `identity` returns oauth_user_id, email, firstname, lastname

Each provider follows the same pattern:
1. `setScope()` - Configure requested permissions
2. `getLoginUrl()` - Get OAuth redirect URL
3. `getMe()` - Exchange callback code for user info (sets "last login" cookie)
4. `isThisServiceLastLogin()` - Check if this provider was last used

## Configuration

Service is registered in Nette config.neon with provider credentials (appId/clientId, appSecret/clientSecret, callbackURL) and a unique cookie name identifier.
