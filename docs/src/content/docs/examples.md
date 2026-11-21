---
title: 使用例
description: filter パラメータとホワイトリスト設定の実用例
---

## filter パラメータの例

```
# 昇順ソート
GET /wp-json/wp/v2/posts?filter[order]=ASC

# Bogo/Polylang の lang 変数で言語絞り込み
GET /wp-json/wp/v2/posts?filter[lang]=ja

# 件数上書き（1–100 にクランプ）
GET /wp-json/wp/v2/posts?filter[posts_per_page]=20

# メタフィールド絞り込み（高コストになり得るので注意）
GET /wp-json/wp/v2/posts?filter[meta_key]=color&filter[meta_value]=blue
```

## 最小ホワイトリストへの絞り込み

サイトで `lang` と `order` しか使わないなら、最小化が推奨です：

```bash
# wp-cli
wp rest-filter vars set lang order posts_per_page
```

または Settings → REST Filter で同じ3行を保存。REST 経由なら:

```bash
curl -X PATCH "$SITE/wp-json/rest-filter/v1/vars" \
  -u user:app-password -H 'Content-Type: application/json' \
  -d '{"vars":["lang","order","posts_per_page"]}'
```

## bogo（多言語）での利用

bogo は `lang` を `query_vars` フィルタで公開変数に追加します。本プラグインのデフォルト計算は同じフィルタを適用するため、`lang` はデフォルトで利用可能です。whitelist を保存する場合は `lang` を忘れずに含めてください——含めないと `filter[lang]` が無言で無視されます。

## コードでの強制上書き

option を誤っても mu-plugin 等で復旧できます（`rest_query_vars` は常に最後に適用）:

```php
add_filter( 'rest_query_vars', fn( $vars ) => [ 'lang', 'order' ] );
```
