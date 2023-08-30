# Override Translation

Override app translation


## Instructions

For now only have the command line version with the follow commands:


```
occ l10n-override:add
occ l10n-override:list
occ l10n-override:delete
```

## Use case

In your Nextcloud instance, you will use a special code as login, on the Nextcloud login screen the phrase "Account name or email" no longer makes sense, so you will need to change it to "Your code".

How does this app works and how to change this text?

In Nextcloud is possible to overwrite translations, page templates and much more using the theme feature. For this you will need to use a theme. Nextcloud themes work like this: when loading an X file, will first checks if exists a version of this file in the theme folder, if not, then it will load the original app file.

So we'll define the default theme to store the overwritten files:

```
occ config:system:set theme --value default
```
This command will create the folder `themes/default` and set the name of current theme as `default` at the `config/config.php` file as a system setting.

Identify who this phrase belongs to in your Nextcloud by going to the root folder of your Nextcloud and executing the command:
```
find . -name l10n -type d -exec grep -R "Account name or email" {} \;
```
You should find some occurrences of this phrase in several languages. Example:
```
./core/l10n/mk.js:    "Account name or email" : "Корисничко име или е-пошта",
```
The files found will be inside a folder called `l10n`, before `l10n` you will find the name of the "app" that has this sentence (ex.: core, lib, themes, settings, calendar, etc). In this example, the root directory is `core`.

Let's say you want to change the phrase in Portuguese, then in the grep command you should find files `core/l10n/pt_BR.json` and `core/l10n/pt_BR.js`. The code of the language "Brazilian Portuguese" is `pt_BR`.

Given the information above, to change this phrase in Portuguese to "Your code" execute:
```
occ l10n-override default core "Account name or email" "Seu código" pt_BR
```

### Changing a text form English to English

To do this you will need to use `en` as language code:
```
occ l10n-override default core "Account name or email" "Your code" en
```

## Notes
* Only is possible to add a translation to core, lib or to an enabled app
