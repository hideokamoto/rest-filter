---
title: wp-api での設定
description: REST エンドポイント経由でのホワイトリスト管理
---

`/wp-json/rest-filter/v1/vars` でホワイトリストを REST 経由管理できます。wp-cli の HTTP 版と考えてください。

:::caution[認証必須]
全メソッドに `manage_options` 権限が必要です。未認証リクエストは `401`/`403` を返します——匿名利用者がホワイトリストを広げることができないのは、コアが `filter` を削除した理由の再発防止です。
:::

## `GET /wp-json/rest-filter/v1/vars`

```json
{
  "vars": ["lang", "order", "posts_per_page"],
  "source": "option"
}
```

`source` は `option`（保存済み）または `default`（未設定でデフォルト使用中）。

## `PATCH /wp-json/rest-filter/v1/vars`

`POST`/`PUT`/`PATCH` いずれでも可。`vars` に配列を渡すと option を置き換えます。

```bash
curl -X PATCH "https://example.com/wp-json/rest-filter/v1/vars" \
  -u user:app-password \
  -H "Content-Type: application/json" \
  -d '{"vars": ["lang", "order", "posts_per_page"]}'
```

- `vars` が配列でない、または空配列 → `400`（`rest_filter_invalid` / `rest_filter_empty`）
- 配列内の無効な名前（`^[a-zA-Z0-9_-]+$` 不適合）は除去されます

## ユースケース

他サイトの設定スクリプトや、複数サイトへの同じホワイトリスト展開など、wp-admin を開かずにプログラムから設定したい場合に使います。
