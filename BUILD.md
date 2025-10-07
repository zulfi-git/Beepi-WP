# Building Tailwind CSS

This plugin uses Tailwind CSS for utility-first styling. The Tailwind CSS file is built locally and included in the repository.

## Prerequisites

- Node.js (v16 or later)
- npm

## Building Tailwind CSS

1. Install dependencies:
   ```bash
   npm install
   ```

2. Build Tailwind CSS:
   ```bash
   npm run build:css
   ```

This will scan all PHP and JS files in the project for Tailwind utility classes and generate a minified CSS file at `assets/css/tailwind.min.css`.

## Why Local Build Instead of CDN?

Tailwind CSS v3+ doesn't provide a pre-built CSS file via CDN. The CDN approach would require using the Play CDN (JavaScript runtime), which:
- Adds runtime overhead
- Is not recommended for production
- May be blocked by firewalls

Building Tailwind locally:
- ✅ Only includes the utility classes actually used in the project
- ✅ Results in a smaller CSS file (~16KB vs 3.5MB)
- ✅ No external CDN dependencies
- ✅ Better performance
- ✅ Works in all environments

## Development

If you're actively developing and adding new Tailwind classes, you can watch for changes:

```bash
npx tailwindcss -i ./assets/css/tailwind-input.css -o ./assets/css/tailwind.min.css --watch
```

After adding new utility classes to your PHP or JS files, rebuild the CSS to include them.
