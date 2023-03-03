# T3AM - TYPO3 Authentication Manager

(Pron.: /tiːm/)

## What does it do?

T3AM is a tiny extension which adds another TYPO3 as a source of backend user accounts.
This means you can log into any configured TYPO3 with T3AM using your account and password.
T3AM is intended for teams and agencies where many people work on many projects and need a backend account on any of these systems.
If installed and configured you will no longer need to create accounts for your colleagues.

`t3am_server` is deprecated replaced with t3am 4.0 and later.

## Installation & Configuration

You need one TYPO3 to be configured as a server and one as a client.

### Server installation

1. Install T3AM in the TYPO3 instance you want to use as your authentication management system. (e.g. `auth.acme.inc`)
  a) Composer: `composer require in2code/t3am:^4.0`
  b) TER download: [extensions.typo3.org](https://extensions.typo3.org/extension/t3am)
  c) github dowload [https://github.com/in2code-de/t3am](https://github.com/in2code-de/t3am/releases/latest)
1. Activate T3AM in the Extension Manager.
1. Activate `isServer` in the T3AM extension settings

Your instance can now be used as T3AM server.
For each client that should be able to connect to your server you need to create an access token.

1. Create a new T3AM Client record on the root page (ID 0)
1. Enter a name and description for the client instance (e.g. `www.example.com`)
1. Click on save. You can now copy the generated token from the `token` field and configure your client with it.

### Client installation

1. Install T3AM in the TYPO3 instance you want to T3AM-enable. (e.g. `www.example.com`)
  a) Composer: `composer require in2code/t3am:^4.0`
  b) TER download: [extensions.typo3.org](https://extensions.typo3.org/extension/t3am)
  c) github dowload [https://github.com/in2code-de/t3am](https://github.com/in2code-de/t3am/releases/latest)
1. Activate T3AM in the Extension Manager.
1. Configure the T3AM extension settings:
   1.1. Leave `isServer` unchecked
   1.2. Enter the full T3AM server url with scheme in `server` (e.g. `https://auth.acme.inc`)
   1.3. Get the generated token for this client from your T3AM server instance and paste it into `token`
   1.4. If you want to synchronize avatars you can define a location where they should be saved on the local file system in `avatarFolder`
   1.5. Leave `selfSigned` unchecked. This is a development option. Check only if you know what you are doing.

T3am Version 4.0 (Server) supports all 

## User synchronizing

Backend Users are synchronized by their username.
This means that any user that logs in to the backend will be primarily fetched from the T3AM Server instance.
If the user does not exist TYPO3 will fall back to its own authentication mechanism (you can still log in with any other account that exists in the system).

If the account got deleted in T3AM Server it will be removed from the client upon login attempt.

To enable **avatar synchronization** you have to configure T3AM.
1. Got to the extension manager
2. Click on T3AM
3. Enter a valid "combined folder identifier", which is the UID of the FAL Storage (in most cases "1" for fileadmin) followed by a colon ":" and the path to the folder where the image should be stored (e.g. "/avatars/"). The full configuration value should look like this: "1:/avatars/".
4. Log out and in again and your backend user avatar should be synchronized.

## Compatibility

Version 4.0 supports TYPO3 11 as Server version.

T3AM Clients are available for:
* TYPO3 7 and 8: Version 1.0
* TYPO3 9: Version 2.0
* TYPO3 10: Version 3.0
* TYPO3 11: Version 4.0

## Changelog

v4.0
* TYPO3 v11 support
* Feature: t3am_server is obsolte - t3am can be server and client now

v3.0
* TYPO3 v10 support

v2.0.2
* Support TYPO3 V8 & 9
* Various Bugfixes

v2.0
* Support for TYPO3 9.0

v1.2
* show message, if t3am is active on login error

v1.1
* Synchronize only relevant fields
* Synchronize the user's avatar

v1.0:
* Synchronize the full user record

## Sponsors

* in2code GmbH (https://www.in2code.de)

## Credits

* Resources/Public/Icons/Extension.svg: <div>Icons made by <a href="http://www.freepik.com" title="Freepik">Freepik</a> from <a href="https://www.flaticon.com/" title="Flaticon">www.flaticon.com</a> is licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0" target="_blank">CC 3.0 BY</a></div>
* Resources/Public/Icons/tx_t3am_client.svg: Icons made by Smashicons from www.flaticon.com is licensed by CC 3.0 BY
