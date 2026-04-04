"""
Multi-Modal Integration Service
Combines text and image emotion analysis for comprehensive content understanding
"""

from typing import Dict, List, Any, Optional
import logging
from PIL import Image


class MultiModalService:
    """
    Integrates text and image emotion analysis for posts with multiple modalities
    """
    
    def __init__(self, text_service, image_service, reaction_service):
        self.text_service = text_service
        self.image_service = image_service
        self.reaction_service = reaction_service
        self.logger = logging.getLogger(__name__)
        
        self.logger.info("MultiModalService initialized")
    
    def analyze_post(self, text: Optional[str] = None, 
                    image: Optional[Image.Image] = None,
                    threshold: float = 0.3) -> Dict[str, Any]:
        """
        Analyze post with both text and image content
        
        Args:
            text: Post text/caption (optional)
            image: Post image (optional)
            threshold: Emotion detection threshold
        
        Returns:
            Unified analysis with combined emotion predictions
        """
        
        if text is None and image is None:
            raise ValueError("At least one of text or image must be provided")
        
        results = {
            "has_text": text is not None,
            "has_image": image is not None,
            "modalities": []
        }
        
        text_emotions = None
        image_emotions = None
        
        # Analyze text if present
        if text:
            try:
                text_result = self.text_service.predict_emotions(text, threshold)
                text_emotions = text_result['emotions']
                results['text_analysis'] = text_result
                results['modalities'].append('text')
            except Exception as e:
                self.logger.error(f"Text analysis failed: {e}")
                results['text_error'] = str(e)
        
        # Analyze image if present
        if image:
            try:
                image_result = self.image_service.predict_from_image(image, threshold)
                image_emotions = image_result['emotions']
                results['image_analysis'] = image_result
                results['modalities'].append('image')
            except Exception as e:
                self.logger.error(f"Image analysis failed: {e}")
                results['image_error'] = str(e)
        
        # Combine emotions if both modalities present
        if text_emotions and image_emotions:
            combined = self._combine_emotions(text_emotions, image_emotions)
            results['combined_emotions'] = combined
            results['fusion_method'] = 'weighted_average'
        elif text_emotions:
            results['combined_emotions'] = text_emotions
            results['fusion_method'] = 'text_only'
        elif image_emotions:
            results['combined_emotions'] = image_emotions
            results['fusion_method'] = 'image_only'
        else:
            results['error'] = 'No successful analysis'
            return results
        
        # Extract top emotion from combined results
        sorted_combined = sorted(
            results['combined_emotions'],
            key=lambda x: x['probability'],
            reverse=True
        )
        
        results['top_emotion'] = sorted_combined[0]['emotion']
        results['top_probability'] = sorted_combined[0]['probability']
        
        # Get emotion probabilities dict for reaction suggestions
        emotion_probs = {
            item['emotion']: item['probability']
            for item in results['combined_emotions']
        }
        
        # Generate reaction suggestions
        try:
            reactions = self.reaction_service.suggest_reactions(
                emotion_probs,
                text=text or "",
                threshold=threshold
            )
            results['reaction_suggestions'] = reactions
        except Exception as e:
            self.logger.error(f"Reaction suggestion failed: {e}")
            results['reaction_error'] = str(e)
        
        return results
    
    def _combine_emotions(self, text_emotions: List[Dict[str, Any]],
                         image_emotions: List[Dict[str, Any]],
                         text_weight: float = 0.6,
                         image_weight: float = 0.4) -> List[Dict[str, Any]]:
        """
        Combine emotion predictions from text and image
        
        Uses weighted averaging, giving more weight to text as it typically
        provides richer emotional context in social media posts
        """
        
        # Create emotion probability dictionaries
        text_probs = {item['emotion']: item['probability'] for item in text_emotions}
        image_probs = {item['emotion']: item['probability'] for item in image_emotions}
        
        # Get all unique emotions
        all_emotions = set(text_probs.keys()) | set(image_probs.keys())
        
        # Combine with weighted average
        combined = []
        for emotion in all_emotions:
            text_prob = text_probs.get(emotion, 0.0)
            image_prob = image_probs.get(emotion, 0.0)
            
            # Weighted average
            combined_prob = (text_prob * text_weight) + (image_prob * image_weight)
            
            # Determine confidence level
            confidence = self._get_confidence_level(combined_prob)
            
            combined.append({
                'emotion': emotion,
                'probability': round(combined_prob, 4),
                'confidence_level': confidence,
                'text_probability': round(text_prob, 4),
                'image_probability': round(image_prob, 4)
            })
        
        # Sort by probability
        combined.sort(key=lambda x: x['probability'], reverse=True)
        
        return combined
    
    def _get_confidence_level(self, probability: float) -> str:
        """Map probability to confidence level"""
        if probability >= 0.8:
            return "very_high"
        elif probability >= 0.6:
            return "high"
        elif probability >= 0.4:
            return "medium"
        elif probability >= 0.2:
            return "low"
        else:
            return "very_low"
    
    def analyze_conversation(self, messages: List[Dict[str, Any]],
                           threshold: float = 0.3) -> Dict[str, Any]:
        """
        Analyze emotional flow in a conversation thread
        
        Args:
            messages: List of messages with 'text' and optional 'image' fields
            threshold: Emotion detection threshold
        
        Returns:
            Conversation-level analysis with emotional progression
        """
        
        message_analyses = []
        emotion_timeline = []
        
        for idx, message in enumerate(messages):
            text = message.get('text')
            image = message.get('image')
            
            try:
                analysis = self.analyze_post(text, image, threshold)
                analysis['message_index'] = idx
                message_analyses.append(analysis)
                
                if 'top_emotion' in analysis:
                    emotion_timeline.append({
                        'index': idx,
                        'emotion': analysis['top_emotion'],
                        'probability': analysis['top_probability']
                    })
            except Exception as e:
                self.logger.error(f"Failed to analyze message {idx}: {e}")
        
        # Analyze emotional progression
        progression = self._analyze_emotional_progression(emotion_timeline)
        
        return {
            'total_messages': len(messages),
            'analyzed_messages': len(message_analyses),
            'message_analyses': message_analyses,
            'emotion_timeline': emotion_timeline,
            'emotional_progression': progression
        }
    
    def _analyze_emotional_progression(self, timeline: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Analyze how emotions change throughout a conversation"""
        
        if not timeline:
            return {"status": "no_data"}
        
        emotions = [item['emotion'] for item in timeline]
        
        # Count emotion frequencies
        emotion_counts = {}
        for emotion in emotions:
            emotion_counts[emotion] = emotion_counts.get(emotion, 0) + 1
        
        # Determine dominant emotion
        dominant_emotion = max(emotion_counts, key=emotion_counts.get)
        
        # Check for emotional shifts
        shifts = []
        for i in range(1, len(timeline)):
            if timeline[i]['emotion'] != timeline[i-1]['emotion']:
                shifts.append({
                    'from': timeline[i-1]['emotion'],
                    'to': timeline[i]['emotion'],
                    'at_message': i
                })
        
        return {
            'dominant_emotion': dominant_emotion,
            'emotion_distribution': emotion_counts,
            'emotional_shifts': len(shifts),
            'shift_details': shifts,
            'emotional_stability': 'stable' if len(shifts) <= 1 else 'variable'
        }
