# Instrucciones para Generar el Favicon

## Opción 1: Usar un Generador Online (Recomendado)

1. Ve a uno de estos sitios web:
   - https://realfavicongenerator.net/
   - https://www.favicon-generator.org/
   - https://favicon.io/

2. Sube el archivo `logo-concilio.png` que está en esta carpeta

3. Descarga los archivos generados y colócalos aquí en `assets/img/`:
   - `favicon.ico` (16x16, 32x32, 48x48)
   - `favicon-16x16.png`
   - `favicon-32x32.png`
   - `apple-touch-icon.png` (180x180)

## Opción 2: Crear Manualmente con Herramientas

Si tienes Photoshop, GIMP o cualquier editor de imágenes:

1. Abre `logo-concilio.png`
2. Redimensiona a 32x32 píxeles y guarda como `favicon-32x32.png`
3. Redimensiona a 16x16 píxeles y guarda como `favicon-16x16.png`
4. Redimensiona a 180x180 píxeles y guarda como `apple-touch-icon.png`
5. Usa un conversor online para crear `favicon.ico` con múltiples tamaños

## Opción 3: Usar ImageMagick (Línea de Comandos)

Si tienes ImageMagick instalado:

```bash
# Crear favicon.ico con múltiples tamaños
magick logo-concilio.png -resize 256x256 -define icon:auto-resize=256,128,96,64,48,32,16 favicon.ico

# Crear PNG específicos
magick logo-concilio.png -resize 32x32 favicon-32x32.png
magick logo-concilio.png -resize 16x16 favicon-16x16.png
magick logo-concilio.png -resize 180x180 apple-touch-icon.png
```

## Verificación

Una vez colocados los archivos, recarga tu navegador (Ctrl+F5) para ver el favicon en las pestañas.

Los archivos deben estar en:
- `c:\xampp\htdocs\concilio\assets\img\favicon.ico`
- `c:\xampp\htdocs\concilio\assets\img\favicon-16x16.png`
- `c:\xampp\htdocs\concilio\assets\img\favicon-32x32.png`
- `c:\xampp\htdocs\concilio\assets\img\apple-touch-icon.png`
