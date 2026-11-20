# WP REST API — `filter` parameter for posts endpoints

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
Plugins can extend the whitelist via the `rest_query_vars` filter.

## Security note

`filter` exposes every *public* `WP_Query` var plus meta queries to
unauthenticated API callers — that includes `post_status` and potentially
expensive `meta_query` conditions. That is exactly why core removed it. Keep
the whitelist tight via `rest_query_vars` if you don't need the full set.

## Development

```
composer install
composer test    # PHPUnit, WP functions stubbed — no WP install required
```
