// api は、ソムリエ試験対策アプリの JSON API サーバー。
package main

import (
	"context"
	"errors"
	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"

	"sommelier-exam/server/internal/handler"
)

func main() {
	if err := run(); err != nil {
		slog.Error("サーバーが異常終了しました", "error", err)
		os.Exit(1)
	}
}

// run は、設定の読み込み → DB接続 → HTTPサーバー起動 → 終了待ち、を行う。
// main から分けておくと、エラーを return で返せて、defer の後始末も確実に走る。
func run() error {
	dbURL := os.Getenv("DATABASE_URL")
	if dbURL == "" {
		return errors.New("環境変数 DATABASE_URL が設定されていません")
	}
	addr := os.Getenv("ADDR")
	if addr == "" {
		addr = ":8080"
	}

	// Ctrl+C(SIGINT)や SIGTERM を受けたら ctx が終了する。
	ctx, stop := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer stop()

	pool, err := pgxpool.New(ctx, dbURL)
	if err != nil {
		return err
	}
	defer pool.Close()

	mux := http.NewServeMux()
	mux.Handle("GET /api/health", handler.Health(pool))

	srv := &http.Server{
		Addr:              addr,
		Handler:           mux,
		ReadHeaderTimeout: 5 * time.Second,
	}

	// ListenAndServe は止まるまで戻らないので、goroutine で動かす。
	errCh := make(chan error, 1)
	go func() {
		slog.Info("サーバーを起動しました", "addr", addr)
		errCh <- srv.ListenAndServe()
	}()

	select {
	case err := <-errCh:
		return err
	case <-ctx.Done():
	}

	// 処理中のリクエストを最大10秒待ってから止める。
	slog.Info("サーバーを停止しています")
	shutdownCtx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()
	return srv.Shutdown(shutdownCtx)
}
