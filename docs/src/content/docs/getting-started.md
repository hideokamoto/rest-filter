---
title: Getting Started
description: インストールから filter パラメータの動作確認まで
---

## これは何か

WordPress 4.7 で REST API がコアにマージされた際、任意の `WP_Query` 引数を通せる `filter` パラメータは「サポート不能な設計」として意図的に削除されました（[trac #38378](https://core.trac.wordpress.org/ticket/38378) / [WP-API#2799](https://github.com/WP-API/WP-API/issues/2799)）。

このプラグインは `filter` を復元しつつ、通すクエリ変数をホワイトリストで制御できるようにしたものです。

## 動作環境

- WordPress 4.7 以上
- PHP 7.4 以上

## インストール

[GitHub Releases](https://github.com/hideokamoto/wp-api-rest-filter/releases) から最新の `wp-api-rest-filter-vX.Y.Z.zip` をダウンロードし、`wp-content/plugins/` に展開します（ディレクトリ名は `wp-api-rest-filter/`）。

または wp-cli で：

```bash
wp plugin install https://github.com/hideokamoto/wp-api-rest-filter/releases/download/v1.2.0/wp-api-rest-filter-v1.2.0.zip --activate
```

## 動作確認

```bash
curl "https://example.com/wp-json/wp/v2/posts?filter[order]=ASC"
```

`filter[...]` に入れたクエリ変数が `WP_Query` 引数としてコレクションのクエリにマージされます。

## 次に読む

- [Whitelist](/wp-api-rest-filter/whitelist/) — 許可するクエリ変数の決まり方
- [wp-admin](/wp-api-rest-filter/guides/wp-admin/) / [wp-cli](/wp-api-rest-filter/guides/wp-cli/) / [wp-api](/wp-api-rest-filter/guides/wp-api/) — 3つの設定面
