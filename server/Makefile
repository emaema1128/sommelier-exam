# 開発用の接続先。compose.yaml の設定と合わせている(本番では環境変数で上書きする)
DATABASE_URL ?= postgres://sommelier:sommelier@localhost:5432/sommelier?sslmode=disable
export DATABASE_URL

# goose(マイグレーションの道具)の設定。go.mod の tool に入っているので go tool goose で動く
export GOOSE_DRIVER = postgres
export GOOSE_DBSTRING = $(DATABASE_URL)
export GOOSE_MIGRATION_DIR = db/migrations
GOOSE = go tool goose

.PHONY: run test lint migrate-up migrate-down migrate-status migrate-new

# 未適用のマイグレーションをすべて適用する
migrate-up:
	$(GOOSE) up

# 最後に適用した1つだけ取り消す
migrate-down:
	$(GOOSE) down

# どこまで適用済みかを表示する
migrate-status:
	$(GOOSE) status

# 新しいマイグレーションの空ファイルを作る(例: make migrate-new name=add_sort_order)
migrate-new:
	$(GOOSE) -s create $(name) sql

run:
	go run ./cmd/api

test:
	go test ./...

lint:
	go vet ./...
	gofmt -l .
