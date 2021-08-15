# Thunar Custom Actions
Useful actions with right click on files or directories in Thunar file manager.

This is mainly written so, I can have the same actions on all my xubuntu installations.
If you find this useful, be my guest and use it.
If you have any questions use the issues tab :)

# Features
* Contains gui interfaces through Yad (and xterm/pkexec if needed).
* Self-updater (using GitHub tags) if a new tag is released.
* Automatically adding/installing/updating the actions in Thunar (uca.xml).
* Asks for installing any 3rd party programs when running an action that requires them.

# Requirements
Because I'm a PHP developer all these actions run through PHP, sorry ;)
* PHP Version 7.4.0 or newer
  * ext-curl
  * ext-json
  * ext-dom
* xterm (installed in any ubuntu installation by default)
* pkexec (installed in any ubuntu installation by default)
* Yad and 3rd party requirements will be asked for if needed on first run.

# Installation
###### PHP (only if you don't have it already)
```bash
pkexec bash -c 'apt update && apt install -y php7.4-common php7.4-curl php7.4-xml'
```

###### TCA (Thunar Custom Actions phar file)
Note: Install as local user!
```bash
wget -O ~/.local/bin/tca https://github.com/devorto/thunar-custom-actions/releases/latest/download/tca.phar
chmod ~/.local/bin/tca
```
