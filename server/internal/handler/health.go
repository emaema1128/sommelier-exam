// Package handler は、HTTPリクエストを受けてレスポンスを返す層。
package handler

import (
	"context"
	"encoding/json"
	"log/slog"
	"net/http"
	"time"
)

// Pinger は、DBに接続できるかを確かめられるもの。
// pgxpool.Pool はこのメソッドを持っているので、何も書かなくてもこの interface を満たす。
// テストでは、本物のDBの代わりに偽物を渡せる。
type Pinger interface {
	Ping(ctx context.Context) error
}

type healthResponse struct {
	Status string `json:"status"`
	DB     string `json:"db"`
}

// Health は、サーバーとDBが動いているかを返すハンドラー。
// DBにつながらないときは 503 を返す。
func Health(db Pinger) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		ctx, cancel := context.WithTimeout(r.Context(), 2*time.Second)
		defer cancel()

		status := http.StatusOK
		res := healthResponse{Status: "ok", DB: "ok"}
		if err := db.Ping(ctx); err != nil {
			slog.Warn("DBに接続できません", "error", err)
			status = http.StatusServiceUnavailable
			res = healthResponse{Status: "error", DB: "unreachable"}
		}

		writeJSON(w, status, res)
	})
}

func writeJSON(w http.ResponseWriter, status int, v any) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(status)
	if err := json.NewEncoder(w).Encode(v); err != nil {
		slog.Error("レスポンスの書き込みに失敗しました", "error", err)
	}
}
