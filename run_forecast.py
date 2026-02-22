#!/usr/bin/env python
# coding: utf-8

# In[2]:


import os
import math
import pandas as pd
import numpy as np
from datetime import datetime, timedelta

from sqlalchemy import create_engine, text
from sklearn.ensemble import RandomForestRegressor

# ----------------------------
# CONFIG
# ----------------------------
FORECAST_DAYS = 7
LOOKBACK_DAYS = 365

# Set this env var if you want:
# set ML_DATABASE_URL=mysql+pymysql://root:@127.0.0.1:3306/esports_db?charset=utf8mb4
DB_URL = os.getenv("ML_DATABASE_URL")
if not DB_URL:
    DB_URL = "mysql+pymysql://root:@127.0.0.1:3306/esports_db?charset=utf8mb4"

engine = create_engine(DB_URL)

# ----------------------------
# HELPERS
# ----------------------------
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
    feat = {
        "dow": int(day.dayofweek),
        "roll_7": float(np.mean(vals[-7:])) if len(vals) >= 7 else float(np.mean(vals)) if len(vals) else 0.0,
        "roll_30": float(np.mean(vals[-30:])) if len(vals) >= 30 else float(np.mean(vals)) if len(vals) else 0.0,
        "trend": float((np.mean(vals[-7:]) - np.mean(vals[-30:])) if len(vals) >= 30 else 0.0) if len(vals) else 0.0,
        "days_hist": int(len(vals)),
    }
    return feat

# ----------------------------
# 1) LOAD DATA (includes new orders every run)
# ----------------------------
since = datetime.now() - timedelta(days=LOOKBACK_DAYS)

with engine.connect() as conn:
    items = conn.execute(text("""
        SELECT oi.product_id, o.created_at, oi.quantity
        FROM order_item oi
        INNER JOIN `order` o ON o.id = oi.order_ref_id
        WHERE o.created_at >= :since
    """), {"since": since}).fetchall()

    df_items = pd.DataFrame(items, columns=["product_id", "created_at", "quantity"])

    products = conn.execute(text("""
        SELECT id AS product_id, category, stock
        FROM product
    """)).fetchall()

    df_products = pd.DataFrame(products, columns=["product_id", "category", "stock"])

if df_products.empty:
    print("No products found. Abort.")
    raise SystemExit(0)

df_daily = build_daily_sales(df_items) if not df_items.empty else pd.DataFrame(columns=["product_id", "day", "qty"])

# ----------------------------
# 2) FALLBACKS (category avg daily qty, global avg daily qty)
# ----------------------------
if not df_daily.empty:
    df_daily_with_cat = df_daily.merge(df_products[["product_id", "category"]], on="product_id", how="left")
    cat_daily_avg = df_daily_with_cat.groupby("category")["qty"].mean().to_dict()
    global_daily_avg = float(df_daily["qty"].mean())
else:
    cat_daily_avg = {}
    global_daily_avg = 0.0

# ----------------------------
# 3) TRAIN GLOBAL MODEL
# ----------------------------
train_rows = []
feature_cols = ["dow", "roll_7", "roll_30", "trend", "days_hist"]
model = None

if not df_daily.empty:
    for pid, grp in df_daily.groupby("product_id"):
        grp = grp.sort_values("day")
        s = grp.set_index("day")["qty"].asfreq("D", fill_value=0)

        days = s.index.tolist()
        for i in range(1, len(days)):
            day = days[i]
            hist = s.iloc[:i]
            feat = make_features(hist, day)
            train_rows.append((
                feat["dow"], feat["roll_7"], feat["roll_30"], feat["trend"], feat["days_hist"],
                float(s.iloc[i])
            ))

if len(train_rows) >= 30:
    train = pd.DataFrame(train_rows, columns=feature_cols + ["y"])
    X = train[feature_cols]
    y = train["y"]

    model = RandomForestRegressor(
        n_estimators=300,
        random_state=42,
        min_samples_leaf=2,
        n_jobs=-1
    )
    model.fit(X, y)

# ----------------------------
# 4) FORECAST ALL PRODUCTS (new products included)
# ----------------------------
today = pd.Timestamp(datetime.now().date())
forecast_rows = []

for _, pr in df_products.iterrows():
    pid = int(pr["product_id"])
    cat = pr["category"] if pd.notna(pr["category"]) else None

    # product history
    if not df_daily.empty and (df_daily["product_id"] == pid).any():
        grp = df_daily[df_daily["product_id"] == pid].sort_values("day")
        s = grp.set_index("day")["qty"].asfreq("D", fill_value=0)
    else:
        s = pd.Series(dtype=float)

    # Use model if possible
    if model is not None and len(s) >= 7:
        preds = []
        hist = s.copy()

        for d in range(1, FORECAST_DAYS + 1):
            day = today + pd.Timedelta(days=d)
            feat = make_features(hist, day)
            Xf = pd.DataFrame([[feat[c] for c in feature_cols]], columns=feature_cols)

            yhat = float(model.predict(Xf)[0])
            yhat = max(0.0, yhat)
            preds.append(yhat)
            hist.loc[day] = yhat

        predicted_qty = float(np.sum(preds))
    else:
        # cold-start fallback
        if cat and cat in cat_daily_avg:
            daily_avg = float(cat_daily_avg[cat])
        else:
            daily_avg = float(global_daily_avg)

        predicted_qty = max(0.0, daily_avg * FORECAST_DAYS)

    stock = int(pr["stock"]) if pd.notna(pr["stock"]) else 0
    buffer = 1
    reorder = ceil_int((predicted_qty + buffer) - stock)

    forecast_rows.append({
        "product_id": pid,
        "forecast_days": FORECAST_DAYS,
        "predicted_qty": round(predicted_qty, 2),
        "recommended_reorder_qty": reorder,
        "generated_at": datetime.now()
    })

df_out = pd.DataFrame(forecast_rows)

# ----------------------------
# 5) UPSERT INTO product_forecast
# ----------------------------
upsert_sql = """
INSERT INTO product_forecast (product_id, forecast_days, predicted_qty, recommended_reorder_qty, generated_at)
VALUES (:product_id, :forecast_days, :predicted_qty, :recommended_reorder_qty, :generated_at)
ON DUPLICATE KEY UPDATE
    predicted_qty = VALUES(predicted_qty),
    recommended_reorder_qty = VALUES(recommended_reorder_qty),
    generated_at = VALUES(generated_at)
"""

with engine.begin() as conn:
    for _, row in df_out.iterrows():
        conn.execute(text(upsert_sql), {
            "product_id": int(row["product_id"]),
            "forecast_days": int(row["forecast_days"]),
            "predicted_qty": float(row["predicted_qty"]),
            "recommended_reorder_qty": int(row["recommended_reorder_qty"]),
            "generated_at": row["generated_at"],
        })

print(f"✅ Forecast updated for {len(df_out)} products (next {FORECAST_DAYS} days).")


# In[ ]:




