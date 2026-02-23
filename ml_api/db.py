import os
from sqlalchemy import create_engine

DB_URL = os.getenv("ML_DATABASE_URL", "mysql+pymysql://root:@127.0.0.1:3306/esports_db?charset=utf8mb4")
engine = create_engine(DB_URL)