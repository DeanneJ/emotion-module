"""
Safe Search Service
ML-based toxicity detection for search queries using toxic-bert
"""

import logging
from transformers import pipeline


class SafeSearchService:
    """
    Uses the unitary/toxic-bert model to classify search queries
    as safe or harmful based on toxicity scores.
    """

    def __init__(self, threshold: float = 0.6):
        self.threshold = threshold
        self.logger = logging.getLogger(__name__)
        self.classifier = None

    def load_model(self):
        """Load the toxic-bert model (downloads weights on first run)."""
        self.logger.info("Loading toxic-bert model for safe search...")
        self.classifier = pipeline(
            "text-classification",
            model="unitary/toxic-bert",
            return_all_scores=True,
        )
        self.logger.info("toxic-bert model loaded successfully")

    def classify_query(self, query: str) -> dict:
        """
        Classify a search query as safe or harmful.

        Returns a dict with status, message, and detailed scores.
        """
        if self.classifier is None:
            raise RuntimeError("Safe search model not loaded. Call load_model() first.")

        query_text = query.strip()
        if not query_text:
            return {
                "query": query,
                "status": "error",
                "message": "Query cannot be empty.",
                "is_harmful": False,
                "scores": {},
            }

        raw_results = self.classifier(query_text, top_k=None)

        # Handle nested list output from some transformers versions
        ml_results = raw_results[0] if isinstance(raw_results[0], list) else raw_results

        scores = {r["label"]: round(r["score"], 4) for r in ml_results}

        is_harmful = any(r["score"] > self.threshold for r in ml_results)

        if is_harmful:
            return {
                "query": query_text,
                "status": "blocked",
                "message": "This search query is restricted because it may contain harmful or unethical content.",
                "is_harmful": True,
                "scores": scores,
            }

        return {
            "query": query_text,
            "status": "allowed",
            "message": "Search processed successfully.",
            "is_harmful": False,
            "scores": scores,
        }
