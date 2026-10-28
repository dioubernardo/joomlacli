# Joomlacli

## Comandos

### install-url

```bash
/usr/src/joomlacli/joomlacli /var/www/html install-url https://joomlaupdater.furg.br/pip.zip
```

### uninstall

```bash
/usr/src/joomlacli/joomlacli /var/www/html uninstall pip
```

### disableplugin

```bash
/usr/src/joomlacli/joomlacli /var/www/html disableplugin plg_editors_codemirror
```

### deletemodule

```bash
/usr/src/joomlacli/joomlacli /var/www/html deletemodule "Privacy Dashboard" 1
```

### setparams

```bash
/usr/src/joomlacli/joomlacli /var/www/html setparams com_finder '{
  "show_advanced": 0
}'
```

### setconfiguration

```bash
/usr/src/joomlacli/joomlacli /var/www/html setconfiguration '{
  "sef_rewrite": 1,
  "sef_suffix": 0
}'
```
