#Nette Webpack Assets

Installation
------------

1. In config.neon add extension [Extension.php](./src/Extension.php)

```neon
extensions:
    assets: Prosky\NetteWebpack\Extension
```

2. Add Macros
```neon
latte:
    macros:
        - Prosky\NetteWebpack\Macros::install('asset')
```

3. Configure assets. For Example

Local Development configuration
```neon
assets:
    publicPath: /output/dev
    devPort:  8099
    devServer:  true
```
Development configuration
```neon
assets:
    publicPath: /output/dev
```
Production configuration
```neon
assets:
    publicPath: /output/prod
```

Default configuration
```neon
assets:
    debugMode: %debugMode%
    wwwDir: %wwwDir%
    publicPath: null
    devServer:  null
    manifest: manifest.json
```

Usage
------------
```latte
<link type="text/css" href="{asset front.css}">
<script async src="{asset front.js}" type="application/javascript"></script>
```
