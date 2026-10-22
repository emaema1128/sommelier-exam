# Googleログイン 設定手順

アプリ側の実装は済んでいる。Google Cloud で OAuth クライアントを作り、`.env` に値を入れれば動く。

## 1. Google Cloud でプロジェクトを作る

1. https://console.cloud.google.com/ を開く
2. 画面上部のプロジェクト選択 → 「新しいプロジェクト」→ 名前を付けて作成(例: `sommelier-exam`)

## 2. 同意画面(OAuth consent screen)を設定する

1. 左メニュー「APIとサービス」→「OAuth 同意画面」(または「Google Auth Platform」→「ブランディング」)
2. ユーザーの種類: **外部**
3. アプリ名、ユーザーサポートメール、デベロッパーの連絡先メールを入力
4. スコープ: `openid` / `email` / `profile` の3つだけ(Socialite の既定と同じ。審査不要の範囲)
5. 開発中は「テストユーザー」に自分のGoogleアカウントを追加する
6. 本番公開時は「アプリを公開」にする。このとき **プライバシーポリシーのURL** が必要

## 3. OAuth クライアントIDを作る

1. 「APIとサービス」→「認証情報」→「認証情報を作成」→「OAuth クライアント ID」
2. アプリケーションの種類: **ウェブ アプリケーション**
3. 承認済みのリダイレクト URI に次を登録する

| 環境 | URI |
|---|---|
| 開発 | `http://localhost:8000/auth/google/callback` |
| 本番 | `https://<本番ドメイン>/auth/google/callback`(ドメイン決定後に追加) |

4. 作成後に表示される **クライアントID** と **クライアントシークレット** を控える

## 4. `.env` に設定する

`app/.env` の次の行に値を入れる(シークレットはGitにコミットしない)。

```
GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxxxxx
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

## 5. 動作確認

```
cd app
composer run dev
```

http://localhost:8000/login を開き、「Continue with Google」で自分のアカウントでログインできればOK。

## アプリ側の仕様(実装済み)

- Googleで初めてログイン → アカウントを新規作成(パスワードなし、メール確認済み扱い)
- 同じメールでメール登録済みのアカウントがある → Google側でメール確認済みなら、そのアカウントに紐づける
- 2回目以降はGoogleのユーザーIDで本人を特定する(Gmailのアドレスを変えてもログインできる)
- Googleでメール未確認のアカウント、キャンセル、エラー → ログイン画面に戻してエラーを表示
