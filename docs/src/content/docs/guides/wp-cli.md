---
title: wp-cli での設定
description: wp rest-filter vars コマンドリファレンス
---

WP-CLI コマンド `wp rest-filter vars` でホワイトリストを操作できます。

## サブコマンド

### `list`

有効な変数一覧と、その出所（`option` か `default`）を表示します。

```bash
wp rest-filter vars list
# Source: default
# order
# orderby
# lang
# ...
```

### `add`

変数を追加します。未保存なら現在の有効リストを option 化してから追加します。

```bash
wp rest-filter vars add posts_per_page
```

### `remove`

変数を削除します。結果が空になる場合は拒否されます。

```bash
wp rest-filter vars remove meta_query meta_key meta_value meta_compare
```

### `set`

リストを丸ごと置き換えます。

```bash
wp rest-filter vars set lang order posts_per_page
```

### `reset`

option を削除し、デフォルト（upstream 互換）に戻します。

```bash
wp rest-filter vars reset
```

## 変数名のルール

`^[a-zA-Z0-9_-]+$` に一致する名前のみ有効です。重複は自動で除去されます。

## `wp rest-filter post-types`

`filter` を受け付ける投稿タイプのスコープを操作します。

```bash
wp rest-filter post-types list      # 有効なタイプと出所 (option/default)
wp rest-filter post-types set post page   # スコープを丸ごと置き換え
wp rest-filter post-types add page        # 追加
wp rest-filter post-types remove event    # 削除
wp rest-filter post-types disable         # 全タイプで filter 無効（空配列を保存）
wp rest-filter post-types reset           # option 削除 → 全タイプに戻す
```

REST 非対応のタイプ名（`show_in_rest` が false、未登録）は自動で落とされます。
