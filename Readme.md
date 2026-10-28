# Joomlacli

## Comandos

### install-url

```bash
joomlacli /var/www/html install-url https://joomlaupdater.furg.br/pip.zip
```

### uninstall

```bash
joomlacli /var/www/html uninstall pip
```

### disableplugin

```bash
joomlacli /var/www/html disableplugin plg_editors_codemirror
```

### deletemodule

```bash
joomlacli /var/www/html deletemodule "Privacy Dashboard" 1
```

### setparams

```bash
joomlacli /var/www/html setparams com_finder '{
  "show_advanced": 0
}'
```

### setconfiguration

```bash
joomlacli /var/www/html setconfiguration '{
  "sef_rewrite": 1,
  "sef_suffix": 0
}'
```

### setfiltertext

```bash
joomlacli /var/www/html setfiltertext 7 NONE
```

### setpermission

```bash
joomlacli /var/www/html core.edit 6 1
```

### settheme

```bash
joomlacli /var/www/html settheme pip
```

### setlang

```bash
joomlacli /var/www/html setlang pt-BR
```

### createuser

```bash
joomlacli /var/www/html createuser 'CGTI' 'cgti' 'cgti.servicos@furg.br' 'xxx' 8
```
