# WP API REST Filter

Restores the `filter` query parameter on REST API post-collection endpoints
(`/wp-json/wp/v2/posts`, `/wp-json/wp/v2/pages`, custom post types with
`show_in_rest => true`).

Fork of [WP-API/rest-filter](https://github.com/WP-API/rest-filter), which is
unmaintained. This fork is actively maintained for wp-kyoto.net.

## Why this plugin exists

In WordPress 4.7 the `filter` argument was deliberately removed when the REST
API was merged into core ([trac #38378](https://core.trac.wordpress.org/ticket/38378),
[WP-API#2799](https://github.com/WP-API/WP-API/issues/2799)). Core has never
restored it — the official position is to use first-party registered query
parameters or a plugin like this one for backwards compatibility.

The `filter` parameter lets a collection be filtered by any public `WP_Query`
query var:

```
GET /wp-json/wp/v2/posts?filter[order]=ASC
GET /wp-json/wp/v2/posts?filter[lang]=en            # e.g. Bogo/Polylang `lang` var
GET /wp-json/wp/v2/posts?filter[posts_per_page]=20  # clamped to 1–100
GET /wp-json/wp/v2/posts?filter[meta_key]=color&filter[meta_value]=blue
```

Additional allowed vars: `meta_query`, `meta_key`, `meta_value`, `meta_compare`.
Plugins can extend the whitelist via the `rest_query_vars` filter (always
applied last, so code can recover a broken option).

## Whitelist configuration (v1.1)

The set of query vars accepted inside `filter[...]` is configurable. All three
surfaces manage the same `rest_filter_allowed_vars` option — an array of var
names that **replaces** the default set (public `WP_Query` vars + meta vars)
when present:

- **wp-admin**: Settings → REST Filter (one var per line; risky vars like
  `post_status`/`meta_*` get a warning; empty input is refused)
- **wp-cli**: `wp rest-filter vars list|add|remove|set|reset`
- **wp-api**: `GET`/`PATCH /wp-json/rest-filter/v1/vars` — requires
  `manage_options`, so anonymous callers can never widen the whitelist

When the option is unset the plugin behaves exactly like the upstream original
(every public query var + meta vars). Tighten it per site, e.g. to
`lang`, `order`, `posts_per_page`.

## Naming

The plugin directory/slug is `wp-api-rest-filter`. Functional names are kept
stable for backwards compatibility: the WP-CLI command stays
`wp rest-filter vars`, the management route stays `/wp-json/rest-filter/v1/vars`,
and the option stays `rest_filter_allowed_vars`.

## Security note

`filter` exposes *public* `WP_Query` vars plus meta queries to unauthenticated
API callers — that includes `post_status` and potentially expensive
`meta_query` conditions. That is exactly why core removed it. The whitelist
option exists to scope this down per site; keep it tight.

## Development

```
composer install
composer test    # PHPUnit, WP functions stubbed — no WP install required
```
