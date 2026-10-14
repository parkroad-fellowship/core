---
paths:
  - '{app,database,routes}/**/*.php'
---

# Appdatabaseroutes

## Use Eloquent and the query builder, not DB::
Write data access with Eloquent models and the Schema/query builder, including in migrations. Avoid the DB facade (DB::statement, DB::select, DB::table, raw SQL) unless there's no builder API for what's needed (e.g. Postgres RLS policies), and say why in a PHPDoc line where it's used. Prefer designs that fit the builder (e.g. restore a soft-deleted row instead of needing a partial index).
