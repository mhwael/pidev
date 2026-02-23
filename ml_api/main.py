import os
import math
from datetime import datetime, timedelta
import pandas as pd
import numpy as np
from sqlalchemy import create_engine, text
from fastapi import FastAPI, HTTPException, Query
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error
from sklearn.metrics.pairwise import cosine_similarity

app = FastAPI(title="LevelUp ML API")

DB_URL = os.getenv("ML_DATABASE_URL", "mysql+pymysql://root:@127.0.0.1:3306/esports_db?charset=utf8mb4")
engine = create_engine(DB_URL)

# ---------- helpers ----------
def ceil_int(x: float) -> int:
    return int(math.ceil(x)) if x > 0 else 0

def build_daily_sales(df_items: pd.DataFrame) -> pd.DataFrame:
    df = df_items.copy()
    df["day"] = pd.to_datetime(df["created_at"]).dt.date
    daily = (
        df.groupby(["product_id", "day"], as_index=False)["quantity"]
        .sum()
        .rename(columns={"quantity": "qty"})
    )
    daily["day"] = pd.to_datetime(daily["day"])
    return daily

def make_features(history_series: pd.Series, day: pd.Timestamp) -> dict:
    vals = history_series.values
    roll_7 = float(np.mean(vals[-7:])) if len(vals) >= 7 else float(np.mean(vals)) if len(vals) else 0.0
    roll_30 = float(np.mean(vals[-30:])) if len(vals) >= 30 else float(np.mean(vals)) if len(vals) else 0.0
    trend = float(roll_7 - roll_30) if len(vals) else 0.0
    return {"dow": int(day.dayofweek), "roll_7": roll_7, "roll_30": roll_30, "trend": trend, "days_hist": int(len(vals))}

FEATURE_COLS = ["dow", "roll_7", "roll_30", "trend", "days_hist"]

@app.get("/health")
def health():
    return {"ok": True, "time": datetime.now().isoformat()}

# ---------- 1) forecasts ----------
@app.post("/refresh/forecasts")
def refresh_forecasts(
    forecast_days: int = 7,
    lookback_days: int = 365,
    eval_holdout_days: int = 30
):
    since = datetime.now() - timedelta(days=lookback_days)

    with engine.connect() as conn:
        items = conn.execute(text("""
            SELECT oi.product_id, o.created_at, oi.quantity
            FROM order_item oi
            INNER JOIN `order` o ON o.id = oi.order_ref_id
            WHERE o.created_at >= :since
        """), {"since": since}).fetchall()

        df_items = pd.DataFrame(items, columns=["product_id", "created_at", "quantity"])

        products = conn.execute(text("""SELECT id AS product_id, category, stock FROM product""")).fetchall()
        df_products = pd.DataFrame(products, columns=["product_id", "category", "stock"])

    if df_products.empty:
        return {"updated": 0, "message": "No products"}

    df_daily = build_daily_sales(df_items) if not df_items.empty else pd.DataFrame(columns=["product_id","day","qty"])

    # fallbacks
    if not df_daily.empty:
        df_daily_with_cat = df_daily.merge(df_products[["product_id","category"]], on="product_id", how="left")
        cat_daily_avg = df_daily_with_cat.groupby("category")["qty"].mean().to_dict()
        global_daily_avg = float(df_daily["qty"].mean())
    else:
        cat_daily_avg = {}
        global_daily_avg = 0.0

    # train + eval (time split)
    train_rows = []
    model = None
    mae_model = None
    mae_baseline = None

    if not df_daily.empty:
        cutoff = pd.Timestamp(datetime.now().date()) - pd.Timedelta(days=eval_holdout_days)

        for pid, grp in df_daily.groupby("product_id"):
            grp = grp.sort_values("day")
            s = grp.set_index("day")["qty"].asfreq("D", fill_value=0)
            days = s.index.tolist()

            for i in range(1, len(days)):
                day = days[i]
                hist = s.iloc[:i]
                feat = make_features(hist, day)
                y = float(s.iloc[i])
                train_rows.append([feat[c] for c in FEATURE_COLS] + [y, day])

        if len(train_rows) >= 30:
            train = pd.DataFrame(train_rows, columns=FEATURE_COLS + ["y","day"])
            train_fit = train[train["day"] < cutoff]

            if len(train_fit) >= 30:
                model = RandomForestRegressor(n_estimators=300, random_state=42, min_samples_leaf=2, n_jobs=-1)
                model.fit(train_fit[FEATURE_COLS], train_fit["y"])

                eval_rows = train[train["day"] >= cutoff].copy()
                eval_rows = eval_rows[eval_rows["days_hist"] >= 7]
                if not eval_rows.empty:
                    y_true = eval_rows["y"].astype(float).values
                    y_pred = np.maximum(model.predict(eval_rows[FEATURE_COLS]), 0.0)
                    mae_model = float(mean_absolute_error(y_true, y_pred))
                    mae_baseline = float(mean_absolute_error(y_true, eval_rows["roll_7"].astype(float).values))

    # forecast + upsert
    today = pd.Timestamp(datetime.now().date())
    rows = []
    for _, pr in df_products.iterrows():
        pid = int(pr["product_id"])
        cat = pr["category"] if pd.notna(pr["category"]) else None

        if not df_daily.empty and (df_daily["product_id"] == pid).any():
            grp = df_daily[df_daily["product_id"] == pid].sort_values("day")
            s = grp.set_index("day")["qty"].asfreq("D", fill_value=0)
        else:
            s = pd.Series(dtype=float)

        if model is not None and len(s) >= 7:
            preds = []
            hist = s.copy()
            for d in range(1, forecast_days+1):
                day = today + pd.Timedelta(days=d)
                feat = make_features(hist, day)
                Xf = pd.DataFrame([[feat[c] for c in FEATURE_COLS]], columns=FEATURE_COLS)
                yhat = max(0.0, float(model.predict(Xf)[0]))
                preds.append(yhat)
                hist.loc[day] = yhat
            predicted_qty = float(np.sum(preds))
        else:
            daily_avg = float(cat_daily_avg.get(cat, global_daily_avg))
            predicted_qty = max(0.0, daily_avg * forecast_days)

        stock = int(pr["stock"]) if pd.notna(pr["stock"]) else 0
        reorder = ceil_int((predicted_qty + 1) - stock)

        rows.append({
            "product_id": pid,
            "forecast_days": forecast_days,
            "predicted_qty": round(predicted_qty, 2),
            "recommended_reorder_qty": reorder,
            "generated_at": datetime.now()
        })

    upsert = text("""
        INSERT INTO product_forecast (product_id, forecast_days, predicted_qty, recommended_reorder_qty, generated_at)
        VALUES (:product_id, :forecast_days, :predicted_qty, :recommended_reorder_qty, :generated_at)
        ON DUPLICATE KEY UPDATE
          predicted_qty=VALUES(predicted_qty),
          recommended_reorder_qty=VALUES(recommended_reorder_qty),
          generated_at=VALUES(generated_at)
    """)

    with engine.begin() as conn:
        for r in rows:
            conn.execute(upsert, r)

    return {
        "updated": len(rows),
        "mae_model": mae_model,
        "mae_baseline": mae_baseline,
        "beats_baseline": (mae_model is not None and mae_baseline is not None and mae_model < mae_baseline)
    }

@app.get("/forecast/{product_id}")
def get_forecast(product_id: int, days: int = 7):
    with engine.connect() as conn:
        row = conn.execute(text("""
            SELECT product_id, forecast_days, predicted_qty, recommended_reorder_qty, generated_at
            FROM product_forecast
            WHERE product_id=:pid AND forecast_days=:days
            ORDER BY generated_at DESC
            LIMIT 1
        """), {"pid": product_id, "days": days}).fetchone()

    if not row:
        raise HTTPException(404, "No forecast, run POST /refresh/forecasts first")
    return {
        "product_id": int(row[0]),
        "forecast_days": int(row[1]),
        "predicted_qty": float(row[2]),
        "recommended_reorder_qty": int(row[3]),
        "generated_at": str(row[4]),
    }

# ---------- 2) recommendations ----------
@app.post("/refresh/recommendations")
def refresh_recommendations(k: int = 6):
    df = pd.read_sql("""
        SELECT oi.order_ref_id AS order_id, oi.product_id, oi.quantity
        FROM order_item oi
    """, engine)

    if df.empty:
        return {"updated_products": 0, "message": "No order_item data"}

    popularity = df.groupby("product_id")["quantity"].sum().sort_values(ascending=False)
    top_popular = popularity.index.astype(int).tolist()

    basket = df.pivot_table(index="order_id", columns="product_id", values="quantity", aggfunc="sum", fill_value=0)
    prod_ids = basket.columns.astype(int).tolist()

    sim_df = pd.DataFrame(cosine_similarity(basket.T.values), index=prod_ids, columns=prod_ids)

    all_products = pd.read_sql("SELECT id FROM product", engine)["id"].astype(int).tolist()
    now = datetime.now()

    rows = []
    for pid in all_products:
        if pid in sim_df.index:
            scores = sim_df.loc[pid].copy()
            scores.loc[pid] = -1
            top = scores.sort_values(ascending=False).head(k)
            recs = [(int(rid), float(sc)) for rid, sc in top.items() if float(sc) > 0]
            if not recs:
                recs = [(int(p), 0.1) for p in top_popular if int(p) != pid][:k]
        else:
            recs = [(int(p), 0.1) for p in top_popular if int(p) != pid][:k]

        for rec_pid, score in recs:
            if rec_pid == pid:
                continue
            rows.append({"p": pid, "r": rec_pid, "s": score, "g": now})

    with engine.begin() as conn:
        conn.execute(text("DELETE FROM product_recommendation"))
        conn.execute(text("""
            INSERT INTO product_recommendation (product_id, recommended_product_id, score, generated_at)
            VALUES (:p,:r,:s,:g)
            ON DUPLICATE KEY UPDATE score=VALUES(score), generated_at=VALUES(generated_at)
        """), rows)

    return {"updated_products": len(all_products), "top_k": k}

@app.get("/recommend/{product_id}")
def get_recommendations(product_id: int, k: int = 6):
    with engine.connect() as conn:
        rows = conn.execute(text("""
            SELECT recommended_product_id, score, generated_at
            FROM product_recommendation
            WHERE product_id=:pid
            ORDER BY score DESC
            LIMIT :k
        """), {"pid": product_id, "k": k}).fetchall()

    if not rows:
        raise HTTPException(404, "No recommendations, run POST /refresh/recommendations first")

    return {
        "product_id": product_id,
        "k": k,
        "generated_at": str(rows[0][2]),
        "items": [{"product_id": int(r[0]), "score": float(r[1])} for r in rows]
    }