"""
Face Detection Service
Handles face detection for multi-person emotion analysis
"""

import cv2
import numpy as np
from typing import Dict, List, Any, Optional, Tuple
import logging
from pathlib import Path
from PIL import Image
import io


class FaceDetectionService:
    """Face detection service using OpenCV's DNN face detector or Haar cascades"""
    
    def __init__(self, detector_type: str = "haar"):
        """
        Initialize face detection service
        
        Args:
            detector_type: Type of detector - 'haar', 'dnn', or 'mtcnn'
        """
        self.logger = logging.getLogger(__name__)
        self.detector_type = detector_type
        
        # Initialize detector
        self._init_detector()
        
        self.logger.info(f"FaceDetectionService initialized with '{detector_type}' detector")
    
    def _init_detector(self):
        """Initialize the face detector"""
        if self.detector_type == "haar":
            # Use Haar Cascade (lightweight, fast)
            cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
            self.face_cascade = cv2.CascadeClassifier(cascade_path)
            
            if self.face_cascade.empty():
                raise RuntimeError("Failed to load Haar cascade classifier")
            
            self.logger.info("✅ Haar cascade face detector loaded")
            
        elif self.detector_type == "dnn":
            # Use OpenCV DNN face detector (more accurate, heavier)
            model_path = Path(__file__).parent.parent.parent / "models" / "face_detector"
            
            prototxt = model_path / "deploy.prototxt"
            weights = model_path / "res10_300x300_ssd_iter_140000.caffemodel"
            
            if prototxt.exists() and weights.exists():
                self.face_net = cv2.dnn.readNetFromCaffe(str(prototxt), str(weights))
                self.logger.info("✅ DNN face detector loaded")
            else:
                self.logger.warning("DNN model not found, falling back to Haar cascade")
                self.detector_type = "haar"
                self._init_detector()
    
    def detect_faces(self, image: Image.Image, min_confidence: float = 0.5, 
                     min_face_size: int = 30) -> Dict[str, Any]:
        """
        Detect faces in an image
        
        Args:
            image: PIL Image to analyze
            min_confidence: Minimum confidence threshold for detections
            min_face_size: Minimum face size in pixels
        
        Returns:
            Dictionary containing:
                - num_faces: Number of faces detected
                - faces: List of face information (bbox, confidence, size)
                - image_size: Original image dimensions
        """
        
        # Convert PIL to OpenCV format
        img_array = np.array(image)
        
        # Handle different image modes
        if len(img_array.shape) == 2:
            # Grayscale
            gray = img_array
            bgr = cv2.cvtColor(img_array, cv2.COLOR_GRAY2BGR)
        elif img_array.shape[2] == 4:
            # RGBA
            bgr = cv2.cvtColor(img_array, cv2.COLOR_RGBA2BGR)
            gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
        else:
            # RGB
            bgr = cv2.cvtColor(img_array, cv2.COLOR_RGB2BGR)
            gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
        
        height, width = bgr.shape[:2]
        
        faces = []
        
        if self.detector_type == "haar":
            faces = self._detect_haar(gray, min_face_size)
        elif self.detector_type == "dnn":
            faces = self._detect_dnn(bgr, min_confidence)
        
        # Calculate additional metrics
        face_data = []
        total_face_area = 0
        
        for i, face in enumerate(faces):
            x, y, w, h = face['bbox']
            area = w * h
            total_face_area += area
            
            # Calculate relative position (center point as percentage of image)
            center_x = (x + w/2) / width * 100
            center_y = (y + h/2) / height * 100
            
            # Calculate face size relative to image
            relative_size = (area / (width * height)) * 100
            
            face_data.append({
                'id': i + 1,
                'bbox': face['bbox'],  # [x, y, width, height]
                'confidence': face.get('confidence', 1.0),
                'size': {
                    'width': w,
                    'height': h,
                    'area': area,
                    'relative_percentage': round(relative_size, 2)
                },
                'position': {
                    'center_x': round(center_x, 1),
                    'center_y': round(center_y, 1),
                    'quadrant': self._get_quadrant(center_x, center_y)
                }
            })
        
        # Sort faces by area (largest first = likely most prominent person)
        face_data.sort(key=lambda f: f['size']['area'], reverse=True)
        
        # Re-assign IDs based on prominence
        for i, face in enumerate(face_data):
            face['id'] = i + 1
            face['is_primary'] = (i == 0)
        
        return {
            'num_faces': len(face_data),
            'faces': face_data,
            'image_size': {
                'width': width,
                'height': height,
                'aspect_ratio': round(width / height, 2)
            },
            'coverage': {
                'total_face_area': total_face_area,
                'face_coverage_percentage': round((total_face_area / (width * height)) * 100, 2) if face_data else 0
            },
            'detector': self.detector_type
        }
    
    def _detect_haar(self, gray: np.ndarray, min_face_size: int) -> List[Dict[str, Any]]:
        """Detect faces using Haar cascade"""
        
        # Detect faces
        detections = self.face_cascade.detectMultiScale(
            gray,
            scaleFactor=1.1,
            minNeighbors=5,
            minSize=(min_face_size, min_face_size),
            flags=cv2.CASCADE_SCALE_IMAGE
        )
        
        faces = []
        for (x, y, w, h) in detections:
            faces.append({
                'bbox': [int(x), int(y), int(w), int(h)],
                'confidence': 1.0  # Haar doesn't provide confidence
            })
        
        return faces
    
    def _detect_dnn(self, bgr: np.ndarray, min_confidence: float) -> List[Dict[str, Any]]:
        """Detect faces using DNN"""
        
        h, w = bgr.shape[:2]
        
        # Create blob and run inference
        blob = cv2.dnn.blobFromImage(
            cv2.resize(bgr, (300, 300)), 
            1.0, (300, 300), 
            (104.0, 177.0, 123.0)
        )
        
        self.face_net.setInput(blob)
        detections = self.face_net.forward()
        
        faces = []
        for i in range(detections.shape[2]):
            confidence = detections[0, 0, i, 2]
            
            if confidence > min_confidence:
                box = detections[0, 0, i, 3:7] * np.array([w, h, w, h])
                x1, y1, x2, y2 = box.astype(int)
                
                faces.append({
                    'bbox': [int(x1), int(y1), int(x2-x1), int(y2-y1)],
                    'confidence': float(confidence)
                })
        
        return faces
    
    def _get_quadrant(self, center_x: float, center_y: float) -> str:
        """Get the quadrant/region of the face in the image"""
        
        if center_x < 33:
            h_pos = "left"
        elif center_x > 66:
            h_pos = "right"
        else:
            h_pos = "center"
        
        if center_y < 33:
            v_pos = "top"
        elif center_y > 66:
            v_pos = "bottom"
        else:
            v_pos = "middle"
        
        return f"{v_pos}-{h_pos}"
    
    def extract_faces(self, image: Image.Image, detection_result: Dict[str, Any],
                      padding: float = 0.2, target_size: Tuple[int, int] = (224, 224)) -> List[Dict[str, Any]]:
        """
        Extract individual face images from detected faces
        
        Args:
            image: Original PIL Image
            detection_result: Result from detect_faces()
            padding: Percentage of face size to add as padding (0.2 = 20%)
            target_size: Target size for extracted faces
        
        Returns:
            List of dictionaries with 'face_id', 'image', and 'bbox'
        """
        
        img_array = np.array(image)
        height, width = img_array.shape[:2]
        
        extracted = []
        
        for face in detection_result['faces']:
            x, y, w, h = face['bbox']
            
            # Add padding
            pad_w = int(w * padding)
            pad_h = int(h * padding)
            
            x1 = max(0, x - pad_w)
            y1 = max(0, y - pad_h)
            x2 = min(width, x + w + pad_w)
            y2 = min(height, y + h + pad_h)
            
            # Extract face region
            face_region = img_array[y1:y2, x1:x2]
            
            # Convert to PIL and resize
            face_pil = Image.fromarray(face_region)
            
            if target_size:
                face_pil = face_pil.resize(target_size, Image.Resampling.LANCZOS)
            
            extracted.append({
                'face_id': face['id'],
                'image': face_pil,
                'original_bbox': face['bbox'],
                'extracted_bbox': [x1, y1, x2-x1, y2-y1],
                'is_primary': face.get('is_primary', False)
            })
        
        return extracted


# Global instance
face_detection_service = None

def get_face_detection_service() -> FaceDetectionService:
    """Get or create face detection service instance"""
    global face_detection_service
    if face_detection_service is None:
        face_detection_service = FaceDetectionService(detector_type="haar")
    return face_detection_service
