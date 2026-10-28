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

### setfiltertext

```bash
/usr/src/joomlacli/joomlacli /var/www/html setfiltertext 7 NONE
```

### setpermission

```bash
/usr/src/joomlacli/joomlacli /var/www/html core.edit 6 1
```

### settheme

```bash
/usr/src/joomlacli/joomlacli /var/www/html settheme pip
```

### setlang

```bash
/usr/src/joomlacli/joomlacli /var/www/html setlang pt-BR
```
