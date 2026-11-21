---
title: Whitelist の仕組み
description: filter[...] に入れられるクエリ変数の決まり方
---

`filter[name]=value` は、ホワイトリストに含まれるクエリ変数だけが `WP_Query` 引数としてマージされます。含まれない変数は無言で無視されます。

## デフォルト（option 未設定時）

`rest_filter_allowed_vars` option が未設定の場合、upstream 互換の挙動です:

- `WP::parse_request()` と同じ `query_vars` フィルタ適用後の公開クエリ変数すべて（bogo の `lang` のようなプラグイン追加変数も含む）
- `posts_per_page`（private var のため明示追加、1–100 にクランプ）
- `meta_query` / `meta_key` / `meta_value` / `meta_compare`

## option 設定時

`rest_filter_allowed_vars` option に配列が保存されている場合、**デフォルトを丸ごと置き換えます**（マージではない）。サイトごとに必要最小限に絞るのが推奨です:

```
lang
order
posts_per_page
```

空の配列は保存できません（誤って全変数を無効化しないよう、空入力は拒否して前回値を保持します）。

## コードによる上書き

`rest_query_vars` フィルタは常に最後に適用されます。option を壊した場合でも mu-plugin などで復旧できます:

```php
add_filter( 'rest_query_vars', fn( $vars ) => [ 'lang', 'order' ] );
```

## 設定できる3つの面

どの面から設定しても同じ option を読み書きします。

| 面 | 方法 | 権限 |
| --- | --- | --- |
| wp-admin | Settings → REST Filter | `manage_options` |
| wp-cli | `wp rest-filter vars ...` | CLI 実行権限 |
| wp-api | `GET`/`PATCH /wp-json/rest-filter/v1/vars` | `manage_options`（未認証は不可） |
