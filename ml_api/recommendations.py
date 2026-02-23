from datetime import datetime

import pandas as pd
from sqlalchemy import text
from sklearn.metrics.pairwise import cosine_similarity

from db import engine


def refresh_recommendations(k: int = 6) -> dict:
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


def get_recommendations_for_product(product_id: int, k: int = 6) -> dict:
    with engine.connect() as conn:
        rows = conn.execute(text("""
            SELECT recommended_product_id, score, generated_at
            FROM product_recommendation
            WHERE product_id = :pid
            ORDER BY score DESC
            LIMIT :k
        """), {"pid": product_id, "k": k}).fetchall()

    if not rows:
        return {"product_id": product_id, "k": k, "items": []}

    return {
        "product_id": product_id,
        "k": k,
        "generated_at": str(rows[0][2]),
        "items": [{"product_id": int(r[0]), "score": float(r[1])} for r in rows],
    }