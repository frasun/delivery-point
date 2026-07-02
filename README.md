# Delivery Point — WooCommerce Plugin

Adds pickup point selection to WooCommerce checkout for shipping methods that require it.
Currently implements InPost Parcel Locker (standard, cash on delivery, weekend).

Delivery point type is configured per shipping method in WooCommerce settings.
Selection is stored in WC session, validated at checkout, saved to order meta,
and exposed via the WooCommerce REST API. Uses InPost GeoWidget for the picker UI.

## Features

- Extensible via `chocante_delivery_point_options` filter — add new providers without modifying the plugin
- Supports InPost Parcel Locker (standard, CoD, weekend)
- WPML-aware widget language (pl/en/uk)
- Displays selected pickup point in order shipping address summary
- Exposes pickup point data in WooCommerce REST API order response

## Scripts

```bash
npm start      # development build (watch)
npm run build  # production build
```
