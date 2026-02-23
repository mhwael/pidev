from datetime import datetime
from fastapi import FastAPI, HTTPException

from forecast import train_forecast_model, refresh_forecasts
from recommendations import refresh_recommendations, get_recommendations_for_product
from db import engine
from sqlalchemy import text

app = FastAPI(title="LevelUp ML API", version="3.0")

@app.get("/health")
def health():
    return {"ok": True, "time": datetime.now().isoformat()}

# ---- Forecast endpoints ----
@app.post("/train/forecast")
def api_train_forecast(lookback_days: int = 365, eval_holdout_days: int = 30):
    return train_forecast_model(lookback_days, eval_holdout_days)

@app.post("/refresh/forecasts")
def api_refresh_forecasts(forecast_days: int = 7, lookback_days: int = 365):
    return refresh_forecasts(forecast_days, lookback_days)

@app.get("/forecast/{product_id}")
def api_get_forecast(product_id: int, days: int = 7):
    with engine.connect() as conn:
        row = conn.execute(text("""
            SELECT product_id, forecast_days, predicted_qty, recommended_reorder_qty, generated_at
            FROM product_forecast
            WHERE product_id = :pid AND forecast_days = :days
            ORDER BY generated_at DESC
            LIMIT 1
        """), {"pid": product_id, "days": days}).fetchone()

    if not row:
        raise HTTPException(status_code=404, detail="No forecast for this product. Refresh forecasts first.")

    return {
        "product_id": int(row[0]),
        "forecast_days": int(row[1]),
        "predicted_qty": float(row[2]),
        "recommended_reorder_qty": int(row[3]),
        "generated_at": str(row[4]),
    }

# ---- Recommendation endpoints ----
@app.post("/refresh/recommendations")
def api_refresh_recommendations(k: int = 6):
    return refresh_recommendations(k)

@app.get("/recommend/{product_id}")
def api_get_recommendations(product_id: int, k: int = 6):
    out = get_recommendations_for_product(product_id, k)
    if not out["items"]:
        raise HTTPException(status_code=404, detail="No recommendations. Refresh recommendations first.")
    return out