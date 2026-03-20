# Architecture: ps_sharebuttons

## Purpose

A PrestaShop front-office widget module that adds social sharing buttons (Facebook, Twitter, Pinterest, etc.) to product pages, allowing customers to share products on social networks.

## Directory Structure

```
ps_sharebuttons.php            - Module class; hook listeners and widget rendering
views/templates/hook/          - Smarty template for the sharing buttons widget
tests/                         - PHPUnit test stubs and PHPStan bootstrap
upgrade/                       - SQL/PHP migration scripts
translations/                  - Locale string overrides
```

## Key Design Decisions

- **WidgetInterface**: Implements `WidgetInterface` for theme-editor positioning.
- **No server-side API calls**: Share buttons use standard social network share URLs with the current product URL as the share target — no OAuth or API keys required.
- **Minimal JavaScript**: Relies on each network's native share popup (window.open) rather than heavy SDK embeds.

## Extension Points

- Add new social networks by extending the button list in `getWidgetVariables()`.
- Override the Smarty template to change button styling.

## Dependency Flow

```
ps_sharebuttons (Module + WidgetInterface)
  └─> renderWidget()          — renders the share buttons template
        └─> getWidgetVariables()
              └─> context->link->getProductLink() — resolves the current product URL
```
