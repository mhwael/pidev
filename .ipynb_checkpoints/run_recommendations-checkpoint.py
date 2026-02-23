import pandas as pd
import mysql.connector
from datetime import datetime

DB = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",     # put your mysql password if you have one
    "database": "esports_db",
}

TOP_K = 6  # how many recommended products per product

cnx = mysql.connector.connect(**DB)
cur = cnx.cursor(dictionary=True)

# 1) Load order-item pairs per order
cur.execute("""
SELECT oi.order_ref_id AS order_id, oi.product_id
FROM order_item oi
""")
rows = cur.fetchall()
df = pd.DataFrame(rows)

if df.empty:
    print("❌ No order_item data -> cannot build recommendations.")
    exit()

# 2) Build co-occurrence counts: (A,B) appear together in an order
pairs = []
for order_id, g in df.groupby("order_id"):
    items = sorted(g["product_id"].unique().tolist())
    for i in range(len(items)):
        for j in range(i+1, len(items)):
            a, b = items[i], items[j]
            pairs.append((a, b))
            pairs.append((b, a))

pairs_df = pd.DataFrame(pairs, columns=["product_id", "recommended_product_id"])
co = pairs_df.value_counts().reset_index(name="co_count")

# 3) Popularity per product (how often product appears)
pop = df["product_id"].value_counts().reset_index()
pop.columns = ["product_id", "pop_count"]

# 4) Score = co_count / pop_count(product)  (simple normalized association)
co = co.merge(pop, on="product_id", how="left")
co["score"] = co["co_count"] / co["pop_count"]

# 5) Keep top K per product
co = co.sort_values(["product_id", "score"], ascending=[True, False])
top = co.groupby("product_id").head(TOP_K).copy()

# 6) Write to DB: upsert into product_recommendation
now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

# Clear old recs (optional but recommended)
cur.execute("DELETE FROM product_recommendation")
cnx.commit()

insert_sql = """
INSERT INTO product_recommendation (product_id, recommended_product_id, score, generated_at)
VALUES (%s, %s, %s, %s)
"""

data = [(int(r.product_id), int(r.recommended_product_id), float(r.score), now)
        for r in top.itertuples(index=False)]

cur.executemany(insert_sql, data)
cnx.commit()

print(f"✅ Recommendations updated: {len(data)} rows written.")
cur.close()
cnx.close()