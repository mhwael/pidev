import os
import math
from datetime import datetime, timedelta

import numpy as np
import pandas as pd
import joblib
from sqlalchemy import text
from sklearn.metrics import mean_absolute_error
from sklearn.ensemble import RandomForestRegressor, HistGradientBoostingRegressor

from db import engine


# ---------------- Helpers ----------------
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

def make_features_v2(history: pd.Series, day: pd.Timestamp) -> dict:
    vals = history.values.astype(float)

    def lag(n): return float(vals[-n]) if len(vals) >= n else 0.0
    def roll(n): return float(np.mean(vals[-n:])) if len(vals) >= n else float(np.mean(vals)) if len(vals) else 0.0
    def std(n): return float(np.std(vals[-n:])) if len(vals) >= n else float(np.std(vals)) if len(vals) else 0.0
    def zero_rate(n):
        if len(vals) == 0:
            return 1.0
        window = vals[-n:] if len(vals) >= n else vals
        return float(np.mean(window == 0.0))

    return {
        "dow": int(day.dayofweek),
        "lag_1": lag(1),
        "lag_7": lag(7),
        "lag_14": lag(14),
        "roll_7": roll(7),
        "roll_14": roll(14),
        "roll_30": roll(30),
        "std_7": std(7),
        "zero_rate_30": zero_rate(30),
        "days_hist": int(len(vals)),
    }

FEATURE_COLS_V2 = ["dow","lag_1","lag_7","lag_14","roll_7","roll_14","roll_30","std_7","zero_rate_30","days_hist"]


# ---------------- Model persistence ----------------
MODEL_DIR = os.getenv("ML_MODEL_DIR", os.path.join(os.path.dirname(__file__), "models"))
os.makedirs(MODEL_DIR, exist_ok=True)

FORECAST_MODEL_PATH = os.path.join(MODEL_DIR, "forecast_model.joblib")
FORECAST_META_PATH  = os.path.join(MODEL_DIR, "forecast_meta.joblib")

def save_forecast(model, meta: dict):
    joblib.dump(model, FORECAST_MODEL_PATH)
    joblib.dump(meta, FORECAST_META_PATH)

def load_forecast():
    if os.path.exists(FORECAST_MODEL_PATH) and os.path.exists(FORECAST_META_PATH):
        try:
            return joblib.load(FORECAST_MODEL_PATH), joblib.load(FORECAST_META_PATH)
        except Exception:
            return None, None
    return None, None


# ---------------- Training + Refresh ----------------
def build_training_rows_v2(df_daily: pd.DataFrame, eval_holdout_days: int):
    if df_daily.empty:
        return None, None

    cutoff = pd.Timestamp(datetime.now().date()) - pd.Timedelta(days=eval_holdout_days)
    rows = []

    for _, grp in df_daily.groupby("product_id"):
        grp = grp.sort_values("day")
        s = grp.set_index("day")["qty"].asfreq("D", fill_value=0.0)
        days = s.index.tolist()

        for i in range(1, len(days)):
            day = days[i]
            hist = s.iloc[:i]
            feat = make_features_v2(hist, day)
            y = float(s.iloc[i])
            rows.append([feat[c] for c in FEATURE_COLS_V2] + [y, day])

    if len(rows) < 60:
        return None, None

    df = pd.DataFrame(rows, columns=FEATURE_COLS_V2 + ["y", "day"])
    return df, cutoff


def train_forecast_model(lookback_days: int = 365, eval_holdout_days: int = 30) -> dict:
    since = datetime.now() - timedelta(days=lookback_days)

    with engine.connect() as conn:
        items = conn.execute(text("""
            SELECT oi.product_id, o.created_at, oi.quantity
            FROM order_item oi
            INNER JOIN `order` o ON o.id = oi.order_ref_id
            WHERE o.created_at >= :since
        """), {"since": since}).fetchall()

    df_items = pd.DataFrame(items, columns=["product_id","created_at","quantity"])
    df_daily = build_daily_sales(df_items) if not df_items.empty else pd.DataFrame(columns=["product_id","day","qty"])

    train_df, cutoff = build_training_rows_v2(df_daily, eval_holdout_days)
    if train_df is None:
        return {"trained": False, "message": "Not enough data to train (need more orders)."}

    train_fit = train_df[train_df["day"] < cutoff]
    eval_df  = train_df[train_df["day"] >= cutoff]

    if len(train_fit) < 60 or len(eval_df) < 10:
        return {"trained": False, "message": "Not enough split data for training/eval."}

    X_train = train_fit[FEATURE_COLS_V2]
    y_train = train_fit["y"].astype(float).values
    X_eval  = eval_df[FEATURE_COLS_V2]
    y_eval  = eval_df["y"].astype(float).values

    baseline_pred = eval_df["roll_7"].astype(float).values
    mae_baseline = float(mean_absolute_error(y_eval, baseline_pred))

    models = {
        "HGBR": HistGradientBoostingRegressor(random_state=42),
        "RF": RandomForestRegressor(n_estimators=300, random_state=42, min_samples_leaf=2, n_jobs=-1),
    }

    best_name = None
    best_model = None
    best_mae = None

    for name, mdl in models.items():
        mdl.fit(X_train, y_train)
        pred = np.maximum(mdl.predict(X_eval), 0.0)
        mae = float(mean_absolute_error(y_eval, pred))
        if best_mae is None or mae < best_mae:
            best_mae = mae
            best_name = name
            best_model = mdl

    meta = {
        "trained_at": datetime.now().isoformat(),
        "model_name": best_name,
        "mae_model": best_mae,
        "mae_baseline": mae_baseline,
        "beats_baseline": (best_mae is not None and best_mae < mae_baseline),
        "use_model": (best_mae is not None and best_mae < mae_baseline),
        "feature_set": "v2",
    }

    save_forecast(best_model, meta)
    return {"trained": True, **meta}


def refresh_forecasts(forecast_days: int = 7, lookback_days: int = 365) -> dict:
    since = datetime.now() - timedelta(days=lookback_days)

    with engine.connect() as conn:
        items = conn.execute(text("""
            SELECT oi.product_id, o.created_at, oi.quantity
            FROM order_item oi
            INNER JOIN `order` o ON o.id = oi.order_ref_id
            WHERE o.created_at >= :since
        """), {"since": since}).fetchall()

        products = conn.execute(text("""
            SELECT id AS product_id, category, stock
            FROM product
        """)).fetchall()

    df_items = pd.DataFrame(items, columns=["product_id","created_at","quantity"])
    df_products = pd.DataFrame(products, columns=["product_id","category","stock"])

    if df_products.empty:
        return {"updated": 0, "message": "No products"}

    df_daily = build_daily_sales(df_items) if not df_items.empty else pd.DataFrame(columns=["product_id","day","qty"])

    if not df_daily.empty:
        df_daily_with_cat = df_daily.merge(df_products[["product_id","category"]], on="product_id", how="left")
        cat_daily_avg = df_daily_with_cat.groupby("category")["qty"].mean().to_dict()
        global_daily_avg = float(df_daily["qty"].mean())
    else:
        cat_daily_avg = {}
        global_daily_avg = 0.0

    model, meta = load_forecast()
    use_model = bool(meta.get("use_model")) if meta else False

    today = pd.Timestamp(datetime.now().date())
    rows = []

    for _, pr in df_products.iterrows():
        pid = int(pr["product_id"])
        cat = pr["category"] if pd.notna(pr["category"]) else None

        if not df_daily.empty and (df_daily["product_id"] == pid).any():
            grp = df_daily[df_daily["product_id"] == pid].sort_values("day")
            s = grp.set_index("day")["qty"].asfreq("D", fill_value=0.0)
        else:
            s = pd.Series(dtype=float)

        preds = []
        hist = s.copy()

        for d in range(1, forecast_days + 1):
            day = today + pd.Timedelta(days=d)
            feat = make_features_v2(hist, day)
            baseline_daily = max(0.0, float(feat["roll_7"]))

            if model is not None and use_model and len(hist) >= 14:
                Xf = pd.DataFrame([[feat[c] for c in FEATURE_COLS_V2]], columns=FEATURE_COLS_V2)
                yhat = max(0.0, float(model.predict(Xf)[0]))
            else:
                if len(hist) >= 7:
                    yhat = baseline_daily
                else:
                    daily_avg = float(cat_daily_avg.get(cat, global_daily_avg))
                    yhat = max(0.0, daily_avg)

            preds.append(yhat)
            hist.loc[day] = yhat

        predicted_qty = float(np.sum(preds))
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
        "used_saved_model": bool(model is not None),
        "model_name": meta.get("model_name") if meta else None,
        "use_model": use_model,
        "mae_model": meta.get("mae_model") if meta else None,
        "mae_baseline": meta.get("mae_baseline") if meta else None,
        "beats_baseline": meta.get("beats_baseline") if meta else None,
    }