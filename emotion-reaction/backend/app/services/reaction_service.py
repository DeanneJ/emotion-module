"""
Reaction Service
Handles emoji reaction suggestions based on emotion predictions
"""

from typing import Dict, List, Any, Set
import logging
import sys
from pathlib import Path

# Add shared directory to path
sys.path.insert(0, str(Path(__file__).parent.parent.parent.parent / "shared"))
from emotion_config import emotion_config


class ReactionService:
    """Manages emoji reaction suggestions based on detected emotions"""
    
    def __init__(self):
        self.logger = logging.getLogger(__name__)
        self.emotion_config = emotion_config
    
    def suggest_reactions(self, emotion_probabilities: Dict[str, float], 
                         text: str = "", threshold: float = 0.3,
                         context: str = "post") -> Dict[str, Any]:
        """
        Suggest appropriate emoji reactions based on emotion probabilities
        
        Args:
            emotion_probabilities: Dictionary of emotion: probability
            text: Optional text content for additional context
            threshold: Minimum probability to consider an emotion significant
            context: Type of content (post, comment, reply)
        
        Returns:
            Dictionary with allowed, blocked, and suggested reactions
        """
        
        try:
            # Filter significant emotions
            significant_emotions = {
                emotion: prob for emotion, prob in emotion_probabilities.items()
                if prob >= threshold
            }
            
            if not significant_emotions:
                # Fallback to top emotion
                top_emotion = max(emotion_probabilities, key=emotion_probabilities.get)
                significant_emotions = {top_emotion: emotion_probabilities[top_emotion]}
            
            # Get top emotion
            top_emotion = max(significant_emotions, key=significant_emotions.get)
            top_probability = significant_emotions[top_emotion]
            
            # Determine emotion category
            emotion_category = self._categorize_emotions(significant_emotions)
            
            # Get allowed and blocked reactions
            allowed_emojis = set()
            blocked_emojis = set()
            
            for emotion in significant_emotions.keys():
                if emotion in self.emotion_config.EMOTION_TO_EMOJIS:
                    allowed_emojis.update(self.emotion_config.EMOTION_TO_EMOJIS[emotion])
                
                if emotion in self.emotion_config.EMOTION_BLOCKED_EMOJIS:
                    blocked_emojis.update(self.emotion_config.EMOTION_BLOCKED_EMOJIS[emotion])
            
            # Remove blocked from allowed
            final_allowed = sorted(list(allowed_emojis - blocked_emojis))
            final_blocked = sorted(list(blocked_emojis))
            
            # Get sentiment
            sentiment = self._get_sentiment(significant_emotions)
            
            # Create suggestions with reasoning
            suggestions = self._create_suggestions(
                significant_emotions, 
                top_emotion,
                emotion_category,
                sentiment
            )
            
            return {
                "allowed_reactions": final_allowed,
                "blocked_reactions": final_blocked,
                "suggested_reactions": suggestions,
                "top_emotion": top_emotion,
                "top_probability": round(top_probability, 4),
                "emotion_category": emotion_category,
                "sentiment": sentiment,
                "significant_emotions": {
                    k: round(v, 4) for k, v in significant_emotions.items()
                },
                "reasoning": self._generate_reasoning(
                    top_emotion, emotion_category, sentiment
                )
            }
            
        except Exception as e:
            self.logger.error(f"Failed to suggest reactions: {e}")
            # Return safe defaults
            return {
                "allowed_reactions": ["❤️", "👍", "😊"],
                "blocked_reactions": [],
                "suggested_reactions": [],
                "error": str(e)
            }
    
    def _categorize_emotions(self, emotions: Dict[str, float]) -> str:
        """Categorize emotions as positive, negative, neutral, or mixed"""
        positive = ["happy", "joy", "amusement", "excitement", "love", "gratitude", 
                   "pride", "admiration", "optimism", "relief"]
        negative = ["sad", "angry", "fear", "disgust", "sadness", "anger", 
                   "disappointment", "grief", "nervousness"]
        
        pos_score = sum(prob for emotion, prob in emotions.items() if emotion in positive)
        neg_score = sum(prob for emotion, prob in emotions.items() if emotion in negative)
        
        if pos_score > neg_score * 1.5:
            return "positive"
        elif neg_score > pos_score * 1.5:
            return "negative"
        elif abs(pos_score - neg_score) < 0.2:
            return "mixed"
        else:
            return "neutral"
    
    def _get_sentiment(self, emotions: Dict[str, float]) -> str:
        """Get overall sentiment"""
        sentiment_scores = {"positive": 0.0, "negative": 0.0, "neutral": 0.0}
        
        for emotion, prob in emotions.items():
            if emotion in self.emotion_config.SENTIMENT_MAPPING.get("positive", []):
                sentiment_scores["positive"] += prob
            elif emotion in self.emotion_config.SENTIMENT_MAPPING.get("negative", []):
                sentiment_scores["negative"] += prob
            else:
                sentiment_scores["neutral"] += prob
        
        return max(sentiment_scores, key=sentiment_scores.get)
    
    def _create_suggestions(self, emotions: Dict[str, float], top_emotion: str,
                           category: str, sentiment: str) -> List[Dict[str, Any]]:
        """Create prioritized emoji suggestions with reasoning"""
        suggestions = []
        
        # Suggestion rules based on emotion category
        if category == "negative":
            suggestions = [
                {"emoji": "❤️", "reason": "Show empathy and support", "priority": 1},
                {"emoji": "🫂", "reason": "Offer comfort", "priority": 1},
                {"emoji": "💙", "reason": "Express care", "priority": 2},
                {"emoji": "💪", "reason": "Encourage strength", "priority": 3},
            ]
        elif category == "positive":
            if top_emotion in ["happy", "joy", "excitement"]:
                suggestions = [
                    {"emoji": "🎉", "reason": "Celebrate with them", "priority": 1},
                    {"emoji": "😊", "reason": "Share happiness", "priority": 1},
                    {"emoji": "❤️", "reason": "Show appreciation", "priority": 2},
                    {"emoji": "👍", "reason": "Approve", "priority": 3},
                ]
            else:
                suggestions = [
                    {"emoji": "❤️", "reason": "Show appreciation", "priority": 1},
                    {"emoji": "👍", "reason": "Express approval", "priority": 2},
                    {"emoji": "😊", "reason": "Share positivity", "priority": 2},
                ]
        else:  # neutral or mixed
            suggestions = [
                {"emoji": "👍", "reason": "Acknowledge", "priority": 1},
                {"emoji": "❤️", "reason": "Show support", "priority": 2},
                {"emoji": "🤔", "reason": "Thoughtful response", "priority": 3},
            ]
        
        return suggestions[:5]  # Return top 5
    
    def _generate_reasoning(self, top_emotion: str, category: str, 
                           sentiment: str) -> str:
        """Generate human-readable reasoning for reaction filtering"""
        
        if category == "negative":
            return (f"The content expresses {top_emotion}. Humorous or celebratory "
                   f"reactions are blocked to maintain empathy and respect.")
        elif category == "positive":
            return (f"The content expresses {top_emotion}. Supportive and positive "
                   f"reactions are encouraged.")
        else:
            return (f"The content has {category} emotional tone. "
                   f"Most reactions are appropriate.")
    
    def filter_user_reactions(self, available_reactions: List[str],
                             emotion_probabilities: Dict[str, float],
                             threshold: float = 0.3) -> Dict[str, List[str]]:
        """
        Filter a list of available reactions based on detected emotions
        
        Args:
            available_reactions: List of emoji reactions available to filter
            emotion_probabilities: Detected emotions with probabilities
            threshold: Emotion threshold
        
        Returns:
            Dictionary with 'allowed' and 'blocked' reaction lists
        """
        
        result = self.suggest_reactions(emotion_probabilities, threshold=threshold)
        
        allowed_set = set(result["allowed_reactions"])
        blocked_set = set(result["blocked_reactions"])
        
        filtered_allowed = [r for r in available_reactions if r in allowed_set]
        filtered_blocked = [r for r in available_reactions if r in blocked_set]
        
        return {
            "allowed": filtered_allowed,
            "blocked": filtered_blocked,
            "reasoning": result.get("reasoning", "")
        }
    
    def get_emoji_mappings(self) -> Dict[str, Any]:
        """
        Get complete emotion-to-emoji mappings
        
        Returns:
            Dictionary with all emotion-emoji mappings and blocked emojis
        """
        
        return {
            "emotion_to_emojis": {
                emotion: list(emojis) 
                for emotion, emojis in self.emotion_config.EMOTION_TO_EMOJIS.items()
            },
            "emotion_blocked_emojis": {
                emotion: list(emojis)
                for emotion, emojis in self.emotion_config.EMOTION_BLOCKED_EMOJIS.items()
                if emojis  # Only include if there are blocked emojis
            },
            "all_emojis": sorted(list(self.emotion_config.ALL_EMOJIS)),
            "total_emotions": len(self.emotion_config.EMOTION_TO_EMOJIS),
            "total_emojis": len(self.emotion_config.ALL_EMOJIS)
        }
    
    def get_reaction_guidelines(self) -> Dict[str, Any]:
        """
        Get explanation of reaction filtering guidelines
        
        Returns:
            Dictionary explaining filtering rules and guidelines
        """
        
        return {
            "positive_emotions": {
                "description": "Joy, love, excitement, admiration, gratitude, optimism, etc.",
                "typical_reactions": ["❤️", "👍", "😊", "🎉", "✨", "😍", "👏", "🙏", "💖"],
                "blocked_reactions": [],
                "reasoning": "Most reactions are appropriate for positive content"
            },
            "negative_emotions": {
                "description": "Sadness, anger, fear, disgust, grief, disappointment, etc.",
                "typical_reactions": ["❤️", "🫂", "💙", "💪", "🙏"],
                "blocked_reactions": ["😂", "😆", "🎉", "😄", "😁"],
                "reasoning": "Only supportive reactions to avoid insensitivity"
            },
            "neutral_emotions": {
                "description": "Neutral, surprise, curiosity, confusion, realization",
                "typical_reactions": ["❤️", "👍", "😊", "🤔", "💭", "🧐"],
                "blocked_reactions": [],
                "reasoning": "Most reactions allowed with contextual awareness"
            },
            "filtering_rules": [
                "Laughing emojis (😂😆😄) are blocked for sad/distressing content",
                "Celebration emojis (🎉🥳) are blocked for negative emotions",
                "Heart reactions (❤️💙💖) are generally always appropriate",
                "Supportive emojis (🫂💪🙏) are recommended for difficult emotions",
                "Thinking emojis (🤔💭) are good for neutral/curious content"
            ],
            "best_practices": [
                "Always consider the emotional context before reacting",
                "Use supportive reactions for vulnerable sharing",
                "Avoid celebratory reactions for negative content",
                "When in doubt, heart reactions (❤️) are safe",
                "Consider the poster's emotional state"
            ],
            "sentiment_mapping": {
                "positive": ["❤️", "👍", "😊", "🎉", "✨", "😍", "👏"],
                "negative": ["❤️", "🫂", "💙", "💪", "🙏"],
                "neutral": ["❤️", "👍", "😊", "🤔", "💭"],
                "mixed": ["❤️", "💙", "🤗", "💭"]
            },
            "total_guidelines": 5
        }
