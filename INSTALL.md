# Installing locally using Vagrant.

I recommned the drupal-vm and easy to setup vm which is highly configurable.

- [drupal-vm](https://github.com/geerlingguy/drupal-vm)

Clone the repo:

`git clone https://github.com/geerlingguy/drupal-vm opengistvm`

`cd opengistvm`

Copy the following config file in the root of opengistvm folder.

`http://dashboard.opengist.dev/` << more configuration information will be available here.

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

![should_see_something_like](https://www.evernote.com/l/Ar-EAIR7_rRKeJAoHnl83oeu4bY4gkKg22UB/image.png)

# Test in the browser

![browser](https://www.evernote.com/l/Ar8lAuSaZ6VC46XqPv4SoCHOh_0AxJruI7sB/image.png)

# Creating the opengist Database:

you should be login into the vagrant vm.

if not use `vagrant ssh` and then, inside your vagrant machine go to the root of your web folder:

`cd /var/www/opengist`

`php app/console doctrine:schema:create`

```
Creating database schema...
Database schema created successfully!
```

# Creating your first user

`php app/console fos:user:create`

Fill the questionarie with your own information.

```
Please choose a username:admin
Please choose an email:admin@example.com
Please choose a password:
Created user admin
```
Now you should be able to access the app:

![gist_screenshot](https://www.evernote.com/l/Ar-AtgP6M41K045pR-lSD48ZWEaCUExxrDMB/image.png)

# Development environment.

If you are using this instructions you should be able to access `http://opengist.dev/app_dev.php/`
