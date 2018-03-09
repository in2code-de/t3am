# T3AM - TYPO3 Authentication Manager

(Pron.: /tiːm/)

## What does it do?

T3AM is a tiny extension which adds another TYPO3 as a source of backend user accounts.
This means you can log into any configured TYPO3 with T3AM using only one account.
T3AM is intended for teams and agencies where many people work on many projects and need a backend account on any of these systems.
If installed and configured you will no longer need to create accounts for your colleagues.

[T3AM Server](https://github.com/in2code-de/t3am_server)  is required if you want to use T3AM.

## Installation & Configuration

Prerequisite: You should have installed T3AM Server in another TYPO3 instance already!

1. Get T3AM
  a) Composer: `composer require in2code/t3am`
  b) TER download: [extensions.typo3.org](https://extensions.typo3.org/extension/t3am)
  c) github dowload [https://github.com/in2code-de/t3am_server](https://github.com/in2code-de/t3am/releases/latest)
2. Activate T3AM in the Extension Manager.
3. Go to your T3AM Server instance and create a new T3AM Client (See the T3AM Server documentation)).
4. Copy the token to the T3AM extension configuration and also add the T3AM Server URL (with "https"!)
5. Ask your buddy to test the login.

## User synchronizing

Backend Users are synchronized by their username.
This means that any user that logs in to the backend will be primarily fetched from the T3AM Server instance.
If the user does not exist TYPO3 will fall back to its own authentication mechanism (you can still log in with any other account that exists in the system).

If the account got deleted in T3AM Server it will be removed from the client upon login attempt.

## Credits

ext_icon.svg: <div>Icons made by <a href="http://www.freepik.com" title="Freepik">Freepik</a> from <a href="https://www.flaticon.com/" title="Flaticon">www.flaticon.com</a> is licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0" target="_blank">CC 3.0 BY</a></div>
