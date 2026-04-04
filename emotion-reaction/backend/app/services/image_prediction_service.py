"""
Image Prediction Service
Handles image-based emotion detection and prediction
"""

import torch
from typing import Dict, List, Any, Optional
import time
import logging
from pathlib import Path
from PIL import Image
import io
import base64
import sys

# Add shared directory to path
sys.path.insert(0, str(Path(__file__).parent.parent.parent.parent / "shared"))
from emotion_config import emotion_config

from ..models.image_emotion_classifier import ImageModelManager, ImageEmotionClassifier
from ..utils.device_manager import device_manager


class ImagePredictionService:
    """Handles image emotion prediction"""
    
    def __init__(self, models_dir: Path, emotions: List[str], device: str = "auto"):
        self.models_dir = Path(models_dir)
        self.emotions = emotions
        self.logger = logging.getLogger(__name__)
        
        # Initialize components
        self.model_manager = ImageModelManager(models_dir, emotions)
        self.device = device_manager.get_device(device)
        
        self.logger.info(f"ImagePredictionService initialized on {self.device}")
    
    def load_model(self, model_name: str = "default") -> bool:
        """Load an image emotion model"""
        try:
            model = self.model_manager.load_model(model_name, self.device)
            
            self.logger.info(f"✅ Loaded image model: {model_name}")
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to load image model: {e}")
            return False
    
    def predict_from_file(self, image_path: str, threshold: float = 0.35) -> Dict[str, Any]:
        """
        Predict emotions from image file
        
        Args:
            image_path: Path to image file
            threshold: Minimum probability threshold
        
        Returns:
            Dictionary with emotion predictions
        """
        
        try:
            image = Image.open(image_path).convert("RGB")
            return self.predict_from_image(image, threshold)
            
        except Exception as e:
            self.logger.error(f"Failed to load image: {e}")
            raise RuntimeError(f"Image loading failed: {e}") from e
    
    def predict_from_bytes(self, image_bytes: bytes, threshold: float = 0.35) -> Dict[str, Any]:
        """
        Predict emotions from image bytes
        
        Args:
            image_bytes: Image data as bytes
            threshold: Minimum probability threshold
        
        Returns:
            Dictionary with emotion predictions
        """
        
        try:
            image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
            return self.predict_from_image(image, threshold)
            
        except Exception as e:
            self.logger.error(f"Failed to decode image: {e}")
            raise RuntimeError(f"Image decoding failed: {e}") from e
    
    def predict_from_base64(self, image_base64: str, threshold: float = 0.35, model_name: str = "default") -> Dict[str, Any]:
        """
        Predict emotions from base64-encoded image
        
        Args:
            image_base64: Base64-encoded image string
            threshold: Minimum probability threshold
            model_name: Name of the model to use
        
        Returns:
            Dictionary with emotion predictions
        """
        
        try:
            # Remove data URI prefix if present
            if ',' in image_base64:
                image_base64 = image_base64.split(',')[1]
            
            image_bytes = base64.b64decode(image_base64)
            image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
            return self.predict_from_image(image, threshold, model_name)
            
        except Exception as e:
            self.logger.error(f"Failed to decode base64 image: {e}")
            raise RuntimeError(f"Base64 decoding failed: {e}") from e
    
    def predict_from_image(self, image: Image.Image, threshold: float = 0.35, model_name: str = "default") -> Dict[str, Any]:
        """
        Predict emotions from PIL Image
        
        Args:
            image: PIL Image object
            threshold: Minimum probability threshold
            model_name: Name of the model to use
        
        Returns:
            Dictionary with emotion predictions and metadata
        """
        
        start_time = time.time()
        
        try:
            # Load model if not loaded or different from current
            if self.model_manager.current_model is None:
                self.logger.info(f"No model loaded, loading '{model_name}'...")
                self.model_manager.load_model(model_name, self.device)
            elif self.model_manager.current_model_name != model_name:
                self.logger.info(f"Switching model from '{self.model_manager.current_model_name}' to '{model_name}'...")
                self.model_manager.load_model(model_name, self.device)
            
            if self.model_manager.current_model is None:
                raise RuntimeError("No model loaded. Please load a model first.")
            
            # Make prediction
            result = self.model_manager.predict_from_image(image, threshold)
            
            # Get emotion probabilities
            emotion_probs = result['emotion_probabilities']
            
            # Filter significant emotions
            significant_emotions = {
                emotion: prob for emotion, prob in emotion_probs.items()
                if prob >= threshold
            }
            
            # Get top emotions
            sorted_emotions = sorted(
                emotion_probs.items(),
                key=lambda x: x[1],
                reverse=True
            )
            
            top_emotion = sorted_emotions[0][0]
            top_probability = sorted_emotions[0][1]
            
            # Get sentiment (using core emotion mapping)
            sentiment = emotion_config.get_sentiment(emotion_probs, threshold)
            
            # Format results
            emotion_list = []
            for emotion, probability in sorted_emotions:
                confidence_level = self._get_confidence_level(probability)
                emotion_list.append({
                    'emotion': emotion,
                    'probability': round(probability, 4),
                    'confidence_level': confidence_level
                })
            
            processing_time = time.time() - start_time
            
            return {
                'emotions': emotion_list,
                'significant_emotions': {k: round(v, 4) for k, v in significant_emotions.items()},
                'top_emotion': top_emotion,
                'top_probability': round(top_probability, 4),
                'sentiment': sentiment,
                'processing_time': round(processing_time, 4),
                'model_used': self.model_manager.current_model_name or "default",
                'threshold': threshold,
                'modality': 'image',
                'image_size': f"{image.width}x{image.height}"
            }
            
        except Exception as e:
            self.logger.error(f"Image prediction failed: {e}")
            raise RuntimeError(f"Prediction failed: {e}") from e
    
    def predict_batch(self, images: List[Image.Image], threshold: float = 0.35) -> List[Dict[str, Any]]:
        """Predict emotions for multiple images"""
        results = []
        for idx, image in enumerate(images):
            try:
                result = self.predict_from_image(image, threshold)
                result['image_index'] = idx
                results.append(result)
            except Exception as e:
                self.logger.error(f"Batch prediction failed for image {idx}: {e}")
                results.append({
                    'image_index': idx,
                    'error': str(e)
                })
        return results
    
    def predict_with_face_detection(self, image: Image.Image, threshold: float = 0.35,
                                    model_name: str = "default", min_face_size: int = 30,
                                    padding: float = 0.2) -> Dict[str, Any]:
        """
        Advanced prediction with face detection for multi-person emotion analysis
        
        Args:
            image: PIL Image to analyze
            threshold: Minimum probability threshold
            model_name: Name of the model to use
            min_face_size: Minimum face size in pixels to detect
            padding: Percentage of face size to add as padding when extracting
        
        Returns:
            Dictionary with:
                - image_info: Original image metadata
                - face_detection: Face detection results
                - person_emotions: List of emotion predictions per person
                - summary: Overall summary statistics
        """
        import time
        start_time = time.time()
        
        try:
            from .face_detection_service import get_face_detection_service
            
            face_service = get_face_detection_service()
            
            # Step 1: Detect faces
            detection_result = face_service.detect_faces(
                image, 
                min_confidence=0.5,
                min_face_size=min_face_size
            )
            
            num_faces = detection_result['num_faces']
            
            # Step 2: Handle different scenarios
            if num_faces == 0:
                # No faces detected - analyze entire image
                self.logger.info("No faces detected, analyzing entire image")
                full_image_result = self.predict_from_image(image, threshold, model_name)
                
                return {
                    'image_info': {
                        'width': image.width,
                        'height': image.height,
                        'mode': image.mode,
                        'format': getattr(image, 'format', 'unknown')
                    },
                    'face_detection': {
                        'num_faces': 0,
                        'faces': [],
                        'detector': detection_result['detector'],
                        'message': 'No faces detected - analyzed entire image'
                    },
                    'person_emotions': [],
                    'full_image_analysis': full_image_result,
                    'summary': {
                        'primary_emotion': full_image_result['top_emotion'],
                        'primary_confidence': full_image_result['top_probability'],
                        'sentiment': full_image_result['sentiment'],
                        'analysis_type': 'full_image'
                    },
                    'processing_time': round(time.time() - start_time, 4),
                    'model_used': model_name
                }
            
            # Step 3: Extract and analyze each face
            extracted_faces = face_service.extract_faces(
                image, 
                detection_result, 
                padding=padding,
                target_size=(224, 224)
            )
            
            person_emotions = []
            emotion_aggregates = {}
            
            for face_data in extracted_faces:
                face_image = face_data['image']
                face_id = face_data['face_id']
                
                # Predict emotions for this face
                try:
                    face_result = self.predict_from_image(face_image, threshold, model_name)
                    
                    # Get corresponding face detection info
                    face_info = next(
                        (f for f in detection_result['faces'] if f['id'] == face_id), 
                        {}
                    )
                    
                    person_emotion = {
                        'person_id': face_id,
                        'is_primary': face_data['is_primary'],
                        'bbox': face_data['original_bbox'],
                        'face_size': face_info.get('size', {}),
                        'position': face_info.get('position', {}),
                        'detection_confidence': face_info.get('confidence', 1.0),
                        'emotion': face_result['top_emotion'],
                        'emotion_confidence': face_result['top_probability'],
                        'all_emotions': face_result['emotions'],
                        'sentiment': face_result['sentiment']
                    }
                    
                    person_emotions.append(person_emotion)
                    
                    # Aggregate emotions for summary
                    for emotion_data in face_result['emotions']:
                        emotion = emotion_data['emotion']
                        prob = emotion_data['probability']
                        if emotion not in emotion_aggregates:
                            emotion_aggregates[emotion] = []
                        emotion_aggregates[emotion].append(prob)
                        
                except Exception as e:
                    self.logger.error(f"Failed to analyze face {face_id}: {e}")
                    person_emotions.append({
                        'person_id': face_id,
                        'is_primary': face_data['is_primary'],
                        'bbox': face_data['original_bbox'],
                        'error': str(e)
                    })
            
            # Step 4: Create summary
            # Get primary person's emotion
            primary_person = next((p for p in person_emotions if p.get('is_primary')), 
                                  person_emotions[0] if person_emotions else None)
            
            # Calculate average emotions across all faces
            avg_emotions = {}
            for emotion, probs in emotion_aggregates.items():
                avg_emotions[emotion] = round(sum(probs) / len(probs), 4)
            
            # Sort by average probability
            sorted_avg = sorted(avg_emotions.items(), key=lambda x: x[1], reverse=True)
            
            # Determine overall sentiment
            overall_sentiment = "neutral"
            if primary_person and 'sentiment' in primary_person:
                overall_sentiment = primary_person['sentiment']
            
            # Count emotions
            emotion_counts = {}
            for person in person_emotions:
                if 'emotion' in person:
                    emotion = person['emotion']
                    emotion_counts[emotion] = emotion_counts.get(emotion, 0) + 1
            
            summary = {
                'num_people': num_faces,
                'primary_person': {
                    'person_id': primary_person.get('person_id') if primary_person else None,
                    'emotion': primary_person.get('emotion') if primary_person else None,
                    'confidence': primary_person.get('emotion_confidence') if primary_person else None
                },
                'emotion_distribution': emotion_counts,
                'average_emotions': dict(sorted_avg[:5]),  # Top 5 average emotions
                'dominant_emotion': sorted_avg[0][0] if sorted_avg else None,
                'overall_sentiment': overall_sentiment,
                'analysis_type': 'multi_face' if num_faces > 1 else 'single_face'
            }
            
            processing_time = round(time.time() - start_time, 4)
            
            return {
                'image_info': {
                    'width': image.width,
                    'height': image.height,
                    'mode': image.mode,
                    'format': getattr(image, 'format', 'unknown')
                },
                'face_detection': {
                    'num_faces': num_faces,
                    'faces': detection_result['faces'],
                    'detector': detection_result['detector'],
                    'coverage': detection_result['coverage']
                },
                'person_emotions': person_emotions,
                'summary': summary,
                'processing_time': processing_time,
                'model_used': model_name
            }
            
        except Exception as e:
            self.logger.error(f"Multi-face prediction failed: {e}")
            raise RuntimeError(f"Multi-face analysis failed: {e}") from e
    
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
    
    def get_available_models(self) -> List[Dict[str, Any]]:
        """List available image models"""
        return self.model_manager.list_models()
    
    def get_model_info(self) -> Dict[str, Any]:
        """Get information about currently loaded model"""
        if self.model_manager.current_model is None:
            return {"status": "no_model_loaded"}
        
        return {
            "model_name": self.model_manager.current_model_name,
            "model_info": self.model_manager.current_model.get_model_info(),
            "device": str(self.device),
            "emotions": self.emotions
        }
    
    def explain_prediction(self, image: Image.Image, model_name: str = "default",
                          method: str = "gradcam") -> Dict[str, Any]:
        """
        Generate explanation for image emotion prediction using visualization techniques
        
        Args:
            image: PIL Image object
            model_name: Model to use for prediction
            method: Explanation method (gradcam, lime, integrated_gradients)
        
        Returns:
            Dictionary with explanation data including visualization
        """
        try:
            # Ensure model is loaded
            if self.model_manager.current_model is None or \
               (model_name != "default" and model_name != self.model_manager.current_model_name):
                self.load_model(model_name)
            
            # Get prediction first
            prediction = self.predict_from_image(image, threshold=0.1)
            
            # Initialize explainer
            from ..models.explainer import ImageExplainer
            explainer = ImageExplainer(
                self.model_manager.current_model,
                self.device,
                self.emotions
            )
            
            # Generate explanation based on method
            if method == "gradcam":
                explanation = explainer.explain_with_gradcam(image)
            elif method == "lime":
                explanation = explainer.explain_with_lime(image)
            elif method == "integrated_gradients":
                explanation = explainer.explain_with_integrated_gradients(image)
            else:
                raise ValueError(f"Unknown explanation method: {method}")
            
            return {
                'prediction': prediction,
                'explanation': explanation,
                'method': method,
                'model_used': self.model_manager.current_model_name,
                'image_size': image.size
            }
            
        except Exception as e:
            self.logger.error(f"Image explanation failed: {e}")
            raise RuntimeError(f"Failed to generate explanation: {e}") from e
