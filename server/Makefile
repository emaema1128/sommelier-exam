# 開発用の接続先。compose.yaml の設定と合わせている(本番では環境変数で上書きする)
DATABASE_URL ?= postgres://sommelier:sommelier@localhost:5432/sommelier?sslmode=disable
export DATABASE_URL

.PHONY: run test lint

run:
	go run ./cmd/api

test:
	go test ./...

lint:
	go vet ./...
	gofmt -l .
