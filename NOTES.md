# ERP City Client - Work Log

- Created package skeleton in `/Users/vladalbescu/Desktop/Projects/erp-city-client`.
- Moved ERP API client logic to `ERPClient\\Api\\ERP` and added mail hooks controlled by config.
- Moved ERP auth session helper to `ERPClient\\Services\\ERPAuthService`.
- Moved ERP auth request validators to `ERPClient\\Http\\Requests`.
- Added package controllers (ERP auth, orders, favorites, cart, checkout) and package routes.
- Added package mailables, order model, enums, and publishable email views.
- Added package config `config/erp.php` and publish tag `erp-city-client-config`.
- Added route/view publishing + config toggles in the service provider.
- Updated `prosys-lp` to depend on the package and switched namespaces to the package classes.
