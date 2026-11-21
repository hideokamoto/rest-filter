---
title: セキュリティノート
description: コアが filter を削除した経緯と、このプラグインの安全側の設計
---

## なぜコアに取り込まれなかったか

`filter` が WP 4.7 で削除された（[trac #38378](https://core.trac.wordpress.org/ticket/38378) / [WP-API#2799](https://github.com/WP-API/WP-API/issues/2799)、「NEVER EVER bring it back」）理由は、単なるバグではなく設計上の判断でした:

1. **仕様の一貫性**: 他パラメータはすべて個別登録・検証・スキーマ化されているのに、`filter` は任意の `WP_Query` 引数を通す逃げ口上だった
2. **保守不能**: `WP_Query` の内部仕様が API 契約として外部に固定される
3. **権限・安全性**: 匿名利用者に `post_status`（非公開データの覗き見）や高コスト `meta_query`（DB 負荷）を通せてしまう

## このプラグインの安全側の設計

- **whitelist 化**: 通す変数をサイトごとに最小化できる。デフォルトは upstream 互換だが、本番では `rest_filter_allowed_vars` で絞ることを推奨
- **管理面は `manage_options` 必須**: whitelist を変更できるのは管理者のみ。匿名が whitelist を広げる経路は存在しない
- **危険変数の警告**: `post_status`、`perm`、`has_password`、`meta_*` は wp-admin で警告表示（通すかはサイト主のポリシー判断）
- **posts_per_page は 1–100 にクランプ**: 巨大な件数指定での負荷を抑止
- **コードによる最終手段**: `rest_query_vars` フィルタでコードが常に上書きできる

## 推奨運用

1. 本番では必要な変数だけに絞る（例: `lang order posts_per_page`）
2. `post_status`・`meta_*` は本当に必要な場合だけ許可する
3. 高負荷が心配なら CDN/キャッシュ層でレート制限を併用する
