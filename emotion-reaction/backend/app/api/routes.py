"""
API Routes
Unified endpoints for emotion-aware social media platform
"""

from fastapi import APIRouter, HTTPException, UploadFile, File, Form
from fastapi.responses import JSONResponse
from typing import Dict, Any, Optional
import logging
import base64
from PIL import Image
import io

from ..schemas.requests import (
    TextPredictionRequest, TextPredictionResponse,
    ImagePredictionRequest, ImagePredictionResponse,
    MultiModalRequest, MultiModalResponse,
    ReactionSuggestRequest, ReactionResponse,
    FilterContentRequest, FilterResponse,
    FilterSearchRequest, SearchFilterResponse,
    ModelsListResponse, HealthResponse, ErrorResponse,
    TextExplainRequest, ImageExplainRequest,
    EmojiSuggestionRequest, EmojiSuggestionResponse,
    SarcasmDetectionRequest, SarcasmDetectionResponse,
    SlangDetectionRequest, SlangDetectionResponse,
    EnhancedTextAnalysisRequest, EnhancedTextAnalysisResponse,
    SafeSearchRequest, SafeSearchResponse,
    ChatRequest, ChatResponse, ChatStatusResponse
)

# Initialize router
router = APIRouter(prefix="/api/v1")
logger = logging.getLogger(__name__)

# Service instances (will be set from main.py)
text_service = None
image_service = None
reaction_service = None
filtering_service = None
multimodal_service = None
emoji_service = None
safe_search_service = None
chat_service = None


def set_services(text_svc, image_svc, reaction_svc, filtering_svc, multimodal_svc, emoji_svc=None, safe_search_svc=None, chat_svc=None):
    """Set service instances"""
    global text_service, image_service, reaction_service, filtering_service, multimodal_service, emoji_service, safe_search_service, chat_service
    text_service = text_svc
    image_service = image_svc
    reaction_service = reaction_svc
    filtering_service = filtering_svc
    multimodal_service = multimodal_svc
    emoji_service = emoji_svc
    safe_search_service = safe_search_svc
    chat_service = chat_svc


# ===== Emotion Detection Endpoints =====

@router.post("/emotions/text", response_model=TextPredictionResponse, tags=["Emotion Detection"])
async def analyze_text_emotions(request: TextPredictionRequest):
    """
    Analyze emotions in text content
    
    Detects emotional tones in social media posts, comments, or replies.
    Returns emotion probabilities, sentiment, and top emotions.
    """
    try:
        if not text_service:
            raise HTTPException(status_code=500, detail="Text service not initialized")
        
        result = text_service.predict_emotions(request.text, request.threshold)
        return TextPredictionResponse(**result)
        
    except Exception as e:
        logger.error(f"Text emotion analysis failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/emotions/image", response_model=ImagePredictionResponse, tags=["Emotion Detection"])
async def analyze_image_emotions(
    image: Optional[UploadFile] = File(None),
    image_base64: Optional[str] = None,
    threshold: float = 0.35,
    model_name: str = "default"
):
    """
    Analyze emotions in image content
    
    Detects emotional expressions in images (faces, scenes).
    Accepts either:
    - Uploaded file (multipart/form-data with 'image' field)
    - Base64-encoded image data (JSON with 'image_base64' field)
    """
    try:
        if not image_service:
            raise HTTPException(status_code=500, detail="Image service not initialized")
        
        # Handle file upload
        if image:
            contents = await image.read()
            img = Image.open(io.BytesIO(contents)).convert("RGB")
            result = image_service.predict_from_image(img, threshold, model_name)
            return ImagePredictionResponse(**result)
        
        # Handle base64
        elif image_base64:
            result = image_service.predict_from_base64(image_base64, threshold, model_name)
            return ImagePredictionResponse(**result)
        
        else:
            raise HTTPException(
                status_code=400, 
                detail="Either 'image' file or 'image_base64' string must be provided"
            )
        
    except Exception as e:
        logger.error(f"Image emotion analysis failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/emotions/image/upload", response_model=ImagePredictionResponse, tags=["Emotion Detection"])
async def analyze_image_upload(
    file: UploadFile = File(...), 
    threshold: float = 0.35,
    model_name: str = "default"
):
    """
    Analyze emotions from uploaded image file
    
    Upload image directly instead of base64 encoding.
    Supports: JPG, PNG, GIF, BMP
    """
    try:
        if not image_service:
            raise HTTPException(status_code=500, detail="Image service not initialized")
        
        # Read image
        contents = await file.read()
        image = Image.open(io.BytesIO(contents)).convert("RGB")
        
        result = image_service.predict_from_image(image, threshold, model_name)
        return ImagePredictionResponse(**result)
        
    except Exception as e:
        logger.error(f"Image upload analysis failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/emotions/multimodal", response_model=MultiModalResponse, tags=["Emotion Detection"])
async def analyze_multimodal(request: MultiModalRequest):
    """
    Analyze emotions from multi-modal content (text + image)
    
    Combines text and image analysis for posts with both modalities.
    Uses fusion algorithm to create unified emotion prediction.
    """
    try:
        if not multimodal_service:
            raise HTTPException(status_code=500, detail="Multimodal service not initialized")
        
        # Decode image if provided
        image = None
        if request.image_base64:
            image_bytes = base64.b64decode(request.image_base64.split(',')[-1])
            image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
        
        result = multimodal_service.analyze_post(
            text=request.text,
            image=image,
            threshold=request.threshold
        )
        
        return MultiModalResponse(**result)
        
    except Exception as e:
        logger.error(f"Multimodal analysis failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/emotions/image/advanced", tags=["Emotion Detection"])
async def analyze_image_advanced(
    file: UploadFile = File(...),
    threshold: float = 0.35,
    model_name: str = "default",
    min_face_size: int = 30,
    padding: float = 0.2
):
    """
    Advanced image emotion analysis with face detection
    
    Detects multiple faces in an image and analyzes emotions for each person.
    Returns:
    - Image metadata (size, format)
    - Face detection results (bounding boxes, positions, sizes)
    - Per-person emotion analysis
    - Summary statistics (emotion distribution, dominant emotion)
    
    Ideal for:
    - Group photos
    - Social media posts with multiple people
    - Crowd emotion analysis
    """
    try:
        if not image_service:
            raise HTTPException(status_code=500, detail="Image service not initialized")
        
        # Read image
        contents = await file.read()
        image = Image.open(io.BytesIO(contents)).convert("RGB")
        
        result = image_service.predict_with_face_detection(
            image=image,
            threshold=threshold,
            model_name=model_name,
            min_face_size=min_face_size,
            padding=padding
        )
        
        return JSONResponse(content=result)
        
    except Exception as e:
        logger.error(f"Advanced image analysis failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/emotions/image/detect-faces", tags=["Emotion Detection"])
async def detect_faces_only(
    file: UploadFile = File(...),
    min_face_size: int = 30,
    min_confidence: float = 0.5
):
    """
    Detect faces in an image without emotion analysis
    
    Returns face detection information:
    - Number of faces detected
    - Bounding boxes for each face
    - Face sizes and positions
    - Face coverage statistics
    
    Useful for:
    - Validating images before emotion analysis
    - Understanding image composition
    - Face counting
    """
    try:
        from ..services.face_detection_service import get_face_detection_service
        
        face_service = get_face_detection_service()
        
        # Read image
        contents = await file.read()
        image = Image.open(io.BytesIO(contents)).convert("RGB")
        
        result = face_service.detect_faces(
            image=image,
            min_confidence=min_confidence,
            min_face_size=min_face_size
        )
        
        # Add image metadata
        result['image_info'] = {
            'width': image.width,
            'height': image.height,
            'mode': image.mode
        }
        
        return JSONResponse(content=result)
        
    except Exception as e:
        logger.error(f"Face detection failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Reaction Management Endpoints =====

@router.post("/emojis/suggest", response_model=EmojiSuggestionResponse, tags=["Emojis"])
async def suggest_emojis(request: EmojiSuggestionRequest):
    """
    Suggest appropriate emojis based on text emotional context
    
    Analyzes text emotions and returns:
    - Allowed emojis (appropriate for the emotional context)
    - Blocked emojis (inappropriate/insensitive for the context)
    - Top suggestions (ranked by relevance)
    - Categorized emoji sets
    - Reasoning explanation
    
    Prevents emotionally tone-deaf reactions.
    """
    try:
        if not text_service:
            raise HTTPException(status_code=500, detail="Text service not initialized")
        if not emoji_service:
            raise HTTPException(status_code=500, detail="Emoji service not initialized")
        
        # Analyze text emotions
        emotion_result = text_service.predict_emotions(request.text, request.threshold)
        
        # Create emotion probabilities dictionary from emotions list
        emotion_probabilities = {
            item['emotion']: item['probability'] 
            for item in emotion_result['emotions']
        }
        
        # Get emoji suggestions
        emoji_result = emoji_service.suggest_emojis(
            text=request.text,
            emotion_probabilities=emotion_probabilities,
            core_emotions=emotion_result['core_emotions'],
            sentiment=emotion_result['sentiment'],
            threshold=request.threshold
        )
        
        return EmojiSuggestionResponse(
            text=request.text,
            top_emotion=emotion_result['top_emotion'],
            top_probability=emotion_result['top_probability'],
            detected_emotions=emotion_result['significant_emotions'],
            allowed_emojis=emoji_result['allowed_emojis'],
            blocked_emojis=emoji_result['blocked_emojis'],
            suggested_emojis=emoji_result['suggested_emojis'],
            emoji_categories=emoji_result['emoji_categories'],
            reasoning=emoji_result['reasoning'],
            sentiment=emotion_result['sentiment']
        )
        
    except Exception as e:
        logger.error(f"Emoji suggestion failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/reactions/suggest", response_model=ReactionResponse, tags=["Reactions"])
async def suggest_reactions(request: ReactionSuggestRequest):
    """
    Suggest appropriate emoji reactions based on detected emotions
    
    Returns allowed, blocked, and prioritized emoji suggestions with reasoning.
    Prevents emotionally insensitive interactions.
    """
    try:
        if not reaction_service:
            raise HTTPException(status_code=500, detail="Reaction service not initialized")
        
        result = reaction_service.suggest_reactions(
            request.emotion_probabilities,
            threshold=request.threshold,
            context=request.context
        )
        
        return ReactionResponse(**result)
        
    except Exception as e:
        logger.error(f"Reaction suggestion failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/reactions/filter", tags=["Reactions"])
async def filter_reactions(
    available_reactions: list,
    emotion_probabilities: Dict[str, float],
    threshold: float = 0.3
):
    """
    Filter a list of reactions based on emotion context
    
    Takes available emoji reactions and filters them based on
    emotional appropriateness.
    """
    try:
        if not reaction_service:
            raise HTTPException(status_code=500, detail="Reaction service not initialized")
        
        result = reaction_service.filter_user_reactions(
            available_reactions,
            emotion_probabilities,
            threshold
        )
        
        return result
        
    except Exception as e:
        logger.error(f"Reaction filtering failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Ethical Filtering Endpoints =====

@router.post("/filter/content", response_model=FilterResponse, tags=["Ethical Filtering"])
async def filter_content(request: FilterContentRequest):
    """
    Analyze content for harmful or toxic elements
    
    Proactively detects harmful content including:
    - Violence, harassment, hate speech
    - Self-harm content
    - Illegal activity promotion
    - Sexual explicit content
    """
    try:
        if not filtering_service:
            raise HTTPException(status_code=500, detail="Filtering service not initialized")
        
        result = filtering_service.analyze_content(request.text)
        return FilterResponse(**result)
        
    except Exception as e:
        logger.error(f"Content filtering failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/filter/search", response_model=SearchFilterResponse, tags=["Ethical Filtering"])
async def filter_search_query(request: FilterSearchRequest):
    """
    Filter search queries for harmful content
    
    Proactively blocks harmful search queries before execution.
    Provides alternative suggestions for blocked queries.
    """
    try:
        if not filtering_service:
            raise HTTPException(status_code=500, detail="Filtering service not initialized")
        
        result = filtering_service.filter_search_query(request.query)
        return SearchFilterResponse(**result)
        
    except Exception as e:
        logger.error(f"Search filtering failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Safe Search Endpoint =====

@router.post("/safe-search", response_model=SafeSearchResponse, tags=["Safe Search"])
async def safe_search(request: SafeSearchRequest):
    """
    ML-based safe search classification using toxic-bert.

    Accepts a search query and returns whether it is safe or harmful
    along with per-label toxicity scores.
    """
    try:
        if not safe_search_service:
            raise HTTPException(status_code=500, detail="Safe search service not initialized")

        result = safe_search_service.classify_query(request.query)
        return SafeSearchResponse(**result)

    except Exception as e:
        logger.error(f"Safe search classification failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Model Management Endpoints =====

@router.get("/models", response_model=ModelsListResponse, tags=["Models"])
async def list_models():
    """
    List all available emotion detection models
    
    Returns available text and image models with metadata.
    """
    try:
        text_models = text_service.get_available_models() if text_service else []
        image_models = image_service.get_available_models() if image_service else []
        
        return ModelsListResponse(
            text_models=text_models,
            image_models=image_models
        )
        
    except Exception as e:
        logger.error(f"Failed to list models: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/models/text/info", tags=["Models"])
async def get_text_model_info():
    """Get information about currently loaded text model"""
    try:
        if not text_service:
            raise HTTPException(status_code=500, detail="Text service not initialized")
        
        return text_service.get_model_info()
        
    except Exception as e:
        logger.error(f"Failed to get text model info: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/models/image/info", tags=["Models"])
async def get_image_model_info():
    """Get information about currently loaded image model"""
    try:
        if not image_service:
            raise HTTPException(status_code=500, detail="Image service not initialized")
        
        return image_service.get_model_info()
        
    except Exception as e:
        logger.error(f"Failed to get image model info: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== System Endpoints =====

@router.get("/health", response_model=HealthResponse, tags=["System"])
async def health_check():
    """
    System health check
    
    Returns service status and configuration info.
    """
    return HealthResponse(
        status="healthy",
        version="1.0.0",
        services={
            "text_service": "initialized" if text_service else "not_initialized",
            "image_service": "initialized" if image_service else "not_initialized",
            "reaction_service": "initialized" if reaction_service else "not_initialized",
            "filtering_service": "initialized" if filtering_service else "not_initialized",
            "multimodal_service": "initialized" if multimodal_service else "not_initialized"
        },
        device=str(text_service.device) if text_service else "unknown"
    )


@router.get("/", tags=["System"])
async def root():
    """API root endpoint"""
    return {
        "name": "Emotion-Aware Social Media Platform API",
        "version": "1.0.0",
        "description": "An ethical and emotionally intelligent social media platform using responsible AI",
        "docs_url": "/docs",
        "health_url": "/api/v1/health"
    }


# ===== Model Loading and Training Endpoints =====

@router.post("/models/text/load", tags=["Models"])
async def load_text_model(model_name: str = "default", device: str = "auto"):
    """
    Load a specific text emotion model
    
    Switch between different trained text models.
    """
    try:
        if not text_service:
            raise HTTPException(status_code=500, detail="Text service not initialized")
        
        result = text_service.load_model(model_name, device)
        
        return {
            "success": result.get('success', False),
            "message": result.get('message', 'Model loaded'),
            "model_info": result.get('model_info', {})
        }
        
    except Exception as e:
        logger.error(f"Failed to load text model: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/models/image/load", tags=["Models"])
async def load_image_model(model_name: str = "default"):
    """
    Load a specific image emotion model
    
    Switch between different trained image models (ResNet50, ViT, etc.).
    """
    try:
        if not image_service:
            raise HTTPException(status_code=500, detail="Image service not initialized")
        
        result = image_service.load_model(model_name)
        
        return {
            "success": result.get('success', False),
            "message": result.get('message', 'Model loaded'),
            "model_info": result.get('model_info', {})
        }
        
    except Exception as e:
        logger.error(f"Failed to load image model: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/models/text/available", tags=["Models"])
async def list_text_models():
    """List all available text models"""
    try:
        if not text_service:
            raise HTTPException(status_code=500, detail="Text service not initialized")
        
        return text_service.list_available_models()
        
    except Exception as e:
        logger.error(f"Failed to list text models: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/models/image/available", tags=["Models"])
async def list_image_models():
    """List all available image models"""
    try:
        if not image_service:
            raise HTTPException(status_code=500, detail="Image service not initialized")
        
        return image_service.list_available_models()
        
    except Exception as e:
        logger.error(f"Failed to list image models: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Explainability Endpoints =====

@router.post("/explain/text", tags=["Explainability"])
async def explain_text_prediction(request: TextExplainRequest):
    """
    Generate explainability report for text emotion predictions
    
    Returns word/token importance scores showing which parts of
    the text contributed to emotion detection.
    
    Methods: attention, lime, shap
    """
    try:
        if not text_service:
            raise HTTPException(status_code=500, detail="Text service not initialized")
        
        result = text_service.explain_prediction(
            text=request.text,
            model_name=request.model_name,
            method=request.method
        )
        
        return result
        
    except Exception as e:
        logger.error(f"Text explanation failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/explain/image", tags=["Explainability"])
async def explain_image_prediction(
    file: UploadFile = File(...),
    model_name: str = Form("default"),
    method: str = Form("gradcam")
):
    """
    Generate explainability report for image emotion predictions
    
    Upload an image file to get explainability visualization showing 
    which image regions contributed to emotion detection.
    
    Methods: gradcam, lime, shap
    """
    try:
        if not image_service:
            raise HTTPException(status_code=500, detail="Image service not initialized")
        
        # Read and convert to PIL Image
        contents = await file.read()
        image = Image.open(io.BytesIO(contents)).convert("RGB")
        
        result = image_service.explain_prediction(
            image=image,
            model_name=model_name,
            method=method
        )
        
        return result
        
    except Exception as e:
        logger.error(f"Image explanation failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Batch Processing Endpoints =====

@router.post("/batch/text", tags=["Batch Processing"])
async def batch_analyze_text(texts: list[str], threshold: float = 0.3):
    """
    Analyze multiple text samples in batch
    
    Efficient batch processing for multiple posts/comments.
    """
    try:
        if not text_service:
            raise HTTPException(status_code=500, detail="Text service not initialized")
        
        results = []
        for text in texts:
            result = text_service.predict_emotions(text, threshold)
            results.append(result)
        
        return {
            "count": len(results),
            "results": results
        }
        
    except Exception as e:
        logger.error(f"Batch text analysis failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Emoji and Emotion Configuration Endpoints =====

@router.get("/emotions/config", tags=["Configuration"])
async def get_emotions_config():
    """
    Get complete emotion configuration
    
    Returns:
    - Supported emotions for text (28 GoEmotions)
    - Supported emotions for images (8 core emotions)
    - Emotion mappings and taxonomies
    """
    from backend.emotion_config import (
        GOEMOTIONS_EMOTIONS, 
        CORE_EMOTIONS,
        get_ekman_mapping,
        get_sentiment_mapping
    )
    
    return {
        "text_emotions": {
            "emotions": GOEMOTIONS_EMOTIONS,
            "count": len(GOEMOTIONS_EMOTIONS),
            "description": "GoEmotions fine-grained emotion taxonomy"
        },
        "image_emotions": {
            "emotions": CORE_EMOTIONS,
            "count": len(CORE_EMOTIONS),
            "description": "Core emotion taxonomy (with 'other' class)"
        },
        "mappings": {
            "ekman": get_ekman_mapping(),
            "sentiment": get_sentiment_mapping()
        }
    }


@router.get("/emojis/mappings", tags=["Configuration"])
async def get_emoji_mappings():
    """
    Get emotion-to-emoji mappings
    
    Returns allowed emoji reactions for each emotion.
    """
    try:
        if not reaction_service:
            raise HTTPException(status_code=500, detail="Reaction service not initialized")
        
        return reaction_service.get_emoji_mappings()
        
    except Exception as e:
        logger.error(f"Failed to get emoji mappings: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/reactions/guidelines", tags=["Configuration"])
async def get_reaction_guidelines():
    """
    Get emoji reaction filtering guidelines
    
    Returns the rules and policies for emoji reaction filtering
    based on emotional context.
    """
    try:
        if not reaction_service:
            raise HTTPException(status_code=500, detail="Reaction service not initialized")
        
        return reaction_service.get_reaction_guidelines()
        
    except Exception as e:
        logger.error(f"Failed to get reaction guidelines: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Device and Performance Endpoints =====

@router.get("/device/info", tags=["System"])
async def get_device_info():
    """
    Get computing device information
    
    Returns available devices (CPU/GPU) and current device usage.
    """
    try:
        from ..utils.device_manager import device_manager
        
        return {
            "available_devices": device_manager.get_available_devices(),
            "current_device": str(device_manager.device),
            "cuda_available": device_manager.is_cuda_available(),
            "mps_available": device_manager.is_mps_available()
        }
        
    except Exception as e:
        logger.error(f"Failed to get device info: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/stats", tags=["System"])
async def get_system_stats():
    """
    Get system statistics and metrics
    
    Returns:
    - Request counts
    - Average processing times
    - Model performance metrics
    """
    # TODO: Implement metrics collection
    return {
        "status": "not_implemented",
        "message": "Metrics collection not yet implemented"
    }


# ===== Sarcasm & Slang Detection Endpoints =====

@router.post("/text/sarcasm", response_model=SarcasmDetectionResponse, tags=["Text Analysis"])
async def detect_sarcasm(request: SarcasmDetectionRequest):
    """
    Detect sarcasm in text
    
    Analyzes text to determine if it contains sarcastic language.
    Sarcasm typically reverses emotional polarity.
    """
    try:
        if not text_service or not text_service.enhanced_service:
            raise HTTPException(status_code=500, detail="Sarcasm detection service not available")
        
        sarcasm_service = text_service.enhanced_service.sarcasm_service
        if not sarcasm_service:
            raise HTTPException(status_code=500, detail="Sarcasm detection model not loaded")
        
        result = sarcasm_service.detect(request.text, threshold=request.threshold)
        return SarcasmDetectionResponse(**result)
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Sarcasm detection failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/text/slang", response_model=SlangDetectionResponse, tags=["Text Analysis"])
async def detect_slang(request: SlangDetectionRequest):
    """
    Detect slang terms in text
    
    Identifies informal language and slang terms with their definitions.
    Helps understand context in casual/social media text.
    """
    try:
        if not text_service or not text_service.enhanced_service:
            raise HTTPException(status_code=500, detail="Slang detection service not available")
        
        slang_service = text_service.enhanced_service.slang_service
        if not slang_service:
            raise HTTPException(status_code=500, detail="Slang detection service not loaded")
        
        result = slang_service.detect(request.text)
        return SlangDetectionResponse(**result)
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Slang detection failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/text/enhanced", response_model=EnhancedTextAnalysisResponse, tags=["Text Analysis"])
async def analyze_text_enhanced(request: EnhancedTextAnalysisRequest):
    """
    Comprehensive text analysis with emotion, sarcasm, and slang detection
    
    Combines multiple analysis layers:
    - Emotion detection (28 emotions)
    - Sarcasm detection (polarity reversal)
    - Slang detection (informal language)
    - Recommendations for interpretation
    """
    try:
        if not text_service:
            raise HTTPException(status_code=500, detail="Text service not initialized")
        
        import time
        start_time = time.time()
        
        result = {
            'text': request.text,
            'processing_time': 0
        }
        
        # Emotion detection
        if request.include_emotions:
            emotion_result = text_service.predict_emotions(
                request.text,
                threshold=request.threshold,
                include_sarcasm=False,
                include_slang=False
            )
            result['emotions'] = emotion_result.get('emotions')
            result['top_emotion'] = emotion_result.get('top_emotion')
            result['sentiment'] = emotion_result.get('sentiment')
        
        # Enhanced analysis (sarcasm + slang)
        if (request.include_sarcasm or request.include_slang) and text_service.enhanced_service:
            enhanced = text_service.enhanced_service.analyze_comprehensive(
                text=request.text,
                emotion_result=result if request.include_emotions else None
            )
            
            if request.include_sarcasm and enhanced.get('sarcasm'):
                result['sarcasm'] = enhanced['sarcasm']
            
            if request.include_slang and enhanced.get('slang'):
                result['slang'] = enhanced['slang']
            
            if enhanced.get('recommendations'):
                result['recommendations'] = enhanced['recommendations']
            
            if enhanced.get('emotion_adjustment'):
                result['emotion_adjustment'] = enhanced['emotion_adjustment']
        
        result['processing_time'] = round(time.time() - start_time, 4)
        
        return EnhancedTextAnalysisResponse(**result)
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Enhanced text analysis failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/text/slang/dictionary", tags=["Text Analysis"])
async def get_slang_dictionary():
    """
    Get the complete slang dictionary
    
    Returns all slang terms and their definitions.
    """
    try:
        if not text_service or not text_service.enhanced_service:
            raise HTTPException(status_code=500, detail="Slang detection service not available")
        
        slang_service = text_service.enhanced_service.slang_service
        if not slang_service:
            raise HTTPException(status_code=500, detail="Slang detection service not loaded")
        
        terms = slang_service.get_all_terms()
        info = slang_service.get_dictionary_info()
        
        return {
            "terms": terms,
            "info": info
        }
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Failed to get slang dictionary: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ===== Emotional Assistant Chat Endpoints =====

@router.post("/chat", response_model=ChatResponse, tags=["Emotional Assistant"])
async def chat_with_assistant(request: ChatRequest):
    """
    Chat with MoodBuddy, the emotional wellness assistant.

    Features:
    - Empathetic, emotion-aware responses powered by Groq LLM
    - PII is automatically stripped before sending to the LLM
    - Harmful content is blocked
    - Emotion detection enriches conversation context
    """
    try:
        if not chat_service:
            raise HTTPException(status_code=500, detail="Chat service not initialized")

        history = [{"role": m.role, "content": m.content} for m in request.conversation_history]

        result = await chat_service.chat(
            user_message=request.message,
            conversation_history=history,
            emotion_context=request.emotion_context,
        )

        return ChatResponse(**result)

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Chat failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/chat/status", response_model=ChatStatusResponse, tags=["Emotional Assistant"])
async def chat_status():
    """Get the status of the emotional assistant chat service."""
    try:
        if not chat_service:
            return ChatStatusResponse(
                available=False,
                model="none",
                has_emotion_context=False,
                has_safety_filter=False,
            )
        return ChatStatusResponse(**chat_service.get_status())
    except Exception as e:
        logger.error(f"Chat status check failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))
