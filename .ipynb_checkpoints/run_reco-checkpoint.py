import pandas as pd
import numpy as np
from datetime import datetime
from sqlalchemy import create_engine, text
from sklearn.metrics.pairwise import cosine_similarity

# ✅ DB config
DB_USER = "root"
DB_PASS = ""
DB_HOST = "127.0.0.1"
DB_NAME = "esports_db"

TOP_K = 6

def main():
    engine = create_engine(f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}/{DB_NAME}?charset=utf8mb4")

    df = pd.read_sql("""
        SELECT 
            oi.order_ref_id AS order_id,
            oi.product_id,
            oi.quantity
        FROM order_item oi
    """, engine)

    if df.empty:
        print("⚠️ No order_item data. No recommendations generated.")
        return

    basket = df.pivot_table(
        index="order_id",
        columns="product_id",
        values="quantity",
        aggfunc="sum",
        fill_value=0
    )

    X = basket.T.values
    sim = cosine_similarity(X)
    prod_ids = basket.columns.to_list()
    sim_df = pd.DataFrame(sim, index=prod_ids, columns=prod_ids)

    # Popular fallback
    popularity = df.groupby("product_id")["quantity"].sum().sort_values(ascending=False)
    top_popular = popularity.index.tolist()

    rows_to_insert = []
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # main recs
    for pid in prod_ids:
        scores = sim_df.loc[pid].copy()
        scores.loc[pid] = -1
        top = scores.sort_values(ascending=False).head(TOP_K)

        for rec_pid, score in top.items():
            if score <= 0:
                continue
            rows_to_insert.append((int(pid), int(rec_pid), float(score), now))

    # include all products even new ones
    all_products = pd.read_sql("SELECT id FROM product", engine)["id"].tolist()
    existing_pid = set([r[0] for r in rows_to_insert])

    for pid in all_products:
        if pid in existing_pid:
            continue
        c = 0
        for rec in top_popular:
            if rec == pid:
                continue
            rows_to_insert.append((int(pid), int(rec), 0.1, now))
            c += 1
            if c >= TOP_K:
                break

    # save (fresh each run)
    with engine.begin() as conn:
        conn.execute(text("DELETE FROM product_recommendation"))
        insert_sql = text("""
            INSERT INTO product_recommendation (product_id, recommended_product_id, score, generated_at)
            VALUES (:p, :r, :s, :g)
            ON DUPLICATE KEY UPDATE score=VALUES(score), generated_at=VALUES(generated_at)
        """)
        conn.execute(insert_sql, [
            {"p": p, "r": r, "s": s, "g": g}
            for (p, r, s, g) in rows_to_insert
        ])

    print(f"✅ Recommendations updated for {len(all_products)} products (Top {TOP_K}).")

if __name__ == "__main__":
    main()