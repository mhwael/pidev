from fastapi import FastAPI
from pydantic import BaseModel
from textblob import TextBlob

app = FastAPI(title="Game Guide AI Predictor")

class ReviewData(BaseModel):
    text: str

@app.post("/api/predict")
def predict_sentiment(review: ReviewData):
    analysis = TextBlob(review.text)
    score = analysis.sentiment.polarity
    
    if score > 0.2:
        sentiment = "HAPPY"
    elif score < -0.2:
        sentiment = "ANGRY"
    else:
        sentiment = "NEUTRAL"
        
    return {
        "sentiment": sentiment,
        "score": round(score, 2)
    }