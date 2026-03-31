# Notiblock

Conditional notification blocks for WordPress with dashboard widget configuration.

## Description

Notiblock adds two blocks to the editor: a conditional wrapper that shows or hides its contents based on a date range, and a message block that displays a globally configured notification. Set the message and date range once in the dashboard widget or settings page, then place the blocks wherever the notification should appear.

 ## Installation

1. Download the latest `notiblock.zip` from the [releases page](https://github.com/philhoyt/notiblock/releases).
2. Go to **Plugins → Add New → Upload Plugin** and upload the zip file.
3. Activate through the Plugins screen.
4. Go to **Dashboard → Notiblock Settings** or **Settings → Notiblock** to configure your message and date range.

## Blocks

**Notiblock Conditional** — The wrapper block. Handles the date logic and conditionally renders its contents.

**Notiblock Message** — Displays the globally configured notification message. Only available inside a Conditional block.

## Development
```bash
npm install
```
```bash
npm start          # Development build with watch
npm run build      # Production build
npm run plugin-zip # Create plugin zip
```

## License

GPL-2.0-or-later