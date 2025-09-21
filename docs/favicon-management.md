# Favicon Management Guide for AngelAdmin2

## Current Setup
The application uses a comprehensive favicon setup that supports multiple browsers and devices.

## Files Location
All favicon files should be placed in the `webroot/` directory:

- `favicon.ico` (16x16, 32x32, 48x48 - multi-size ICO format) ? Already exists
- `favicon-16x16.png` (16x16 PNG) - **Need to create**
- `favicon-32x32.png` (32x32 PNG) - **Need to create**  
- `apple-touch-icon.png` (180x180 PNG for iOS devices) - **Need to create**
- `site.webmanifest` (Web App Manifest) ? Created

## How to Update Favicons

### 1. Create Your Icon
Start with a high-resolution square image (at least 512x512px) of your logo/icon.

### 2. Generate Multiple Formats
Use an online favicon generator like:
- https://favicon.io/
- https://realfavicongenerator.net/
- https://www.favicon-generator.org/

### 3. Replace Files
Replace the files in the `webroot/` directory with your new favicon files.

### 4. Clear Browser Cache
After updating, users may need to clear their browser cache or hard refresh (Ctrl+F5) to see the new favicon.

## Configuration Location
The favicon configuration is in `templates/layout/default.php` around line 65-71.

## Supported Browsers
- ? Chrome/Edge (favicon.ico, PNG formats)
- ? Firefox (favicon.ico, PNG formats)  
- ? Safari (favicon.ico, PNG formats, apple-touch-icon)
- ? iOS Safari (apple-touch-icon.png)
- ? Android Chrome (manifest + PNG icons)

## Testing
To test if favicons are working:
1. Open your site in different browsers
2. Check browser tabs for the icon
3. Add to home screen on mobile devices
4. Use browser developer tools to check for 404 errors on favicon requests
