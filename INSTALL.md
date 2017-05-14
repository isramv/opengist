# Install local development

I recommned the drupal-vm and easy to setup vm which is highly configurable.

- [drupal-vm](https://github.com/geerlingguy/drupal-vm)

Clone the repo:

`git clone https://github.com/geerlingguy/drupal-vm opengistvm`

`cd opengistvm`

Copy the following config file in the root of opengistvm folder.

`composer install`

Answer the questions:

- `database_host`: 127.0.0.1
- `database_port`: null
- `database_name`: opengist
- `database_user`: opengist
- `database_password`: opengist
- `mailer_transport`: smtp
- `mailer_transport (smtp)`:
- `mailer_host (127.0.0.1)`:
- `mailer_user (null)`:
- `mailer_password (null)`:
- `mailer_port (null)`:
- `mailer_encryption (null)`:
- `mailer_auth_mode (login)`:
- `token_phrase`: ChangeThisForASecureStringForJWTAuthentication
- `secret (ThisTokenIsNotSoSecretChangeIt)`: AlsoChangeThisForASecureString


