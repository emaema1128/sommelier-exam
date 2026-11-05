-- 一次の4択問題と、その分類(国・地域・分野)のテーブル

-- +goose Up
CREATE TABLE countries (
    id   integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name text NOT NULL UNIQUE                 -- 例: フランス
);

CREATE TABLE regions (
    id         integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    country_id integer NOT NULL REFERENCES countries (id),
    name       text NOT NULL,                 -- 例: ボルドー
    UNIQUE (country_id, name)                 -- 同じ国に同じ名前の地域は1つだけ
);

CREATE TABLE categories (
    id   integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name text NOT NULL UNIQUE                 -- 例: 格付け、品種
);

CREATE TABLE questions (
    id            integer PRIMARY KEY CHECK (id > 0),  -- CSV の連番をそのまま使う
    region_id     integer NOT NULL REFERENCES regions (id),
    category_id   integer NOT NULL REFERENCES categories (id),
    qualification text NOT NULL CHECK (qualification IN ('common', 'sommelier', 'wine_expert')),
    body          text NOT NULL,
    choices       text[] NOT NULL CHECK (cardinality(choices) = 4),
    answer        smallint NOT NULL CHECK (answer BETWEEN 1 AND 4),
    explanation   text NOT NULL,
    textbook_year smallint NOT NULL,
    difficulty    smallint NOT NULL CHECK (difficulty BETWEEN 1 AND 3),
    status        text NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published')),
    created_at    timestamptz NOT NULL DEFAULT now(),
    updated_at    timestamptz NOT NULL DEFAULT now()
);

-- PostgreSQL は外部キーに索引を自動で作らない。地域・分野で絞り込むので作っておく
CREATE INDEX regions_country_id_idx ON regions (country_id);
CREATE INDEX questions_region_id_idx ON questions (region_id);
CREATE INDEX questions_category_id_idx ON questions (category_id);

-- +goose Down
DROP TABLE questions;
DROP TABLE categories;
DROP TABLE regions;
DROP TABLE countries;
