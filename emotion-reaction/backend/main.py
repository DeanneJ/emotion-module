"""
Main Application Entry Point
Emotion-Aware Social Media Platform Backend
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
import logging
from pathlib import Path
import sys
from pathlib import Path

# Add current and parent directories to path
backend_dir = Path(__file__).parent
sys.path.insert(0, str(backend_dir))
sys.path.insert(0, str(backend_dir.parent))

from image_api import app as image_app, load_model as load_image_api_model
from config import config
from app.api.routes import router, set_services
from app.services.text_prediction_service import TextPredictionService
from app.services.image_prediction_service import ImagePredictionService
from app.services.reaction_service import ReactionService
from app.services.filtering_service import EthicalFilteringService
from app.services.multimodal_service import MultiModalService
from emotion_config import emotion_config

# Configure logging with UTF-8 encoding for Windows
logging.basicConfig(
    level=getattr(logging, config.LOG_LEVEL),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
        logging.FileHandler(config.LOG_FILE, encoding='utf-8') if config.LOG_FILE.parent.exists() else logging.NullHandler()
    ]
)

# Set UTF-8 encoding for stdout on Windows (if not already wrapped)
if sys.platform.startswith('win') and hasattr(sys.stdout, 'buffer'):
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')
    if hasattr(sys.stderr, 'buffer'):
        sys.stderr = codecs.getwriter('utf-8')(sys.stderr.buffer, 'strict')

logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Application lifespan manager for startup and shutdown events"""
    # Startup
    try:
        logger.info("=" * 70)
        logger.info("[*] Starting Emotion-Aware Social Media Platform")
        logger.info("=" * 70)
        
        # Log configuration
        config_summary = config.get_config_summary()
        logger.info("[Config] Configuration:")
        logger.info(f"   Server: {config_summary['server']['host']}:{config_summary['server']['port']}")
        logger.info(f"   Debug Mode: {config_summary['server']['debug']}")
        logger.info(f"   Device: {config_summary['models']['device']}")
        logger.info(f"   Text Model: {config_summary['models']['text_model']}")
        logger.info(f"   Image Backbone: {config_summary['models']['image_backbone']}")
        
        # Initialize Text Prediction Service
        logger.info("\n[Text] Initializing Text Prediction Service...")
        text_service = TextPredictionService(
            models_dir=config.TEXT_MODELS_DIR,
            emotions=emotion_config.GOEMOTIONS_EMOTIONS,
            device=config.DEVICE
        )
        logger.info("   [OK] Text Prediction Service initialized")
        
        # Initialize Image Prediction Service
        logger.info("\n[Image] Initializing Image Prediction Service...")
        image_service = ImagePredictionService(
            models_dir=config.IMAGE_MODELS_DIR,
            emotions=emotion_config.CORE_EMOTIONS,
            device=config.DEVICE
        )
        logger.info("   [OK] Image Prediction Service initialized")
        
        # Load default models
        logger.info("\n[Models] Loading default models...")
        try:
            text_loaded = text_service.load_model("default")
            if text_loaded:
                logger.info("   [OK] Text model 'default' loaded")
            else:
                logger.warning("   [WARN] Text model 'default' not found or failed to load")
        except Exception as e:
            logger.warning(f"   [WARN] Could not load text model: {e}")
        
        try:
            image_loaded = image_service.load_model("default")
            if image_loaded:
                logger.info("   [OK] Image model 'default' loaded")
            else:
                logger.warning("   [WARN] Image model 'default' not found or failed to load")
        except Exception as e:
            logger.warning(f"   [WARN] Could not load image model: {e}")
        
        # Initialize Reaction Service
        logger.info("\n[Reaction] Initializing Reaction Service...")
        reaction_service = ReactionService()
        logger.info("   [OK] Reaction Service initialized")
        
        # Initialize Ethical Filtering Service
        logger.info("\n[Filter] Initializing Ethical Filtering Service...")
        filtering_service = EthicalFilteringService(
            toxicity_threshold=config.TOXICITY_THRESHOLD
        )
        filter_stats = filtering_service.get_statistics()
        logger.info(f"   Keywords: {filter_stats['total_keywords']} across {filter_stats['total_keyword_categories']} categories")
        logger.info(f"   Patterns: {filter_stats['total_patterns']}")
        logger.info("   [OK] Ethical Filtering Service initialized")
        
        # Initialize Multi-Modal Service
        logger.info("\n[MultiModal] Initializing Multi-Modal Integration Service...")
        multimodal_service = MultiModalService(
            text_service=text_service,
            image_service=image_service,
            reaction_service=reaction_service
        )
        logger.info("   [OK] Multi-Modal Service initialized")
        
        # Initialize Emoji Service
        logger.info("\n[Emoji] Initializing Emoji Suggestion Service...")
        from app.services.emoji_service import emoji_service
        logger.info("   [OK] Emoji Service initialized")
        
        # Initialize Safe Search Service
        logger.info("\n[SafeSearch] Initializing Safe Search Service...")
        from app.services.safe_search_service import SafeSearchService
        safe_search_service = SafeSearchService(threshold=0.6)
        safe_search_service.load_model()
        logger.info("   [OK] Safe Search Service initialized")
        
        # Initialize Emotional Assistant Chat Service
        logger.info("\n[Chat] Initializing Emotional Assistant (MoodBuddy)...")
        from app.services.chat_service import ChatService
        from dotenv import load_dotenv
        load_dotenv(Path(__file__).parent.parent / '.env')
        chat_service_instance = ChatService(
            text_service=text_service,
            filtering_service=filtering_service,
        )
        chat_status = chat_service_instance.get_status()
        if chat_status['available']:
            logger.info(f"   Model: {chat_status['model']}")
            logger.info("   [OK] Emotional Assistant initialized")
        else:
            logger.warning("   [WARN] GROQ_API_KEY not set - chat service unavailable")
        
        # Set services in routes
        set_services(
            text_service,
            image_service,
            reaction_service,
            filtering_service,
            multimodal_service,
            emoji_service,
            safe_search_service,
            chat_service_instance
        )
        
        logger.info("\n[Image API] Initializing standalone Image API...")
        try:
            load_image_api_model()
            logger.info("   [OK] Image API model loaded")
        except Exception as e:
            logger.error(f"   [FAIL] Failed to load Image API model: {e}")

        logger.info("\n" + "=" * 70)
        logger.info("[SUCCESS] All services initialized successfully!")
        logger.info("=" * 70)
        logger.info(f"\n[Features] Platform Features:")
        logger.info(f"   * Real-time emotion detection (Text & Image)")
        logger.info(f"   * Multi-modal content analysis")
        logger.info(f"   * Context-aware emoji suggestions")
        logger.info(f"   * Context-aware reaction filtering")
        logger.info(f"   * Proactive ethical content filtering")
        logger.info(f"   * ML-based safe search (toxic-bert)")
        logger.info(f"   * MoodBuddy emotional assistant (Groq LLM)")
        logger.info(f"   * {len(emotion_config.GOEMOTIONS_EMOTIONS)} fine-grained emotions (Text)")
        logger.info(f"   * {len(emotion_config.CORE_EMOTIONS)} core emotions (Image)")
        logger.info(f"\n[API] Documentation: http://{config.HOST}:{config.PORT}/docs")
        logger.info(f"[API] Health Check: http://{config.HOST}:{config.PORT}/api/v1/health")
        logger.info(f"[API] Models: http://{config.HOST}:{config.PORT}/api/v1/models")
        logger.info("=" * 70)
        logger.info("\n[NOTE] If default models are not loaded:")
        logger.info("   1. Check that model files exist in models/text/default and models/image/default")
        logger.info("   2. Train models using the scripts in text-project and image-project folders")
        logger.info("   3. Use POST /api/v1/models/load endpoint to load models dynamically")
        logger.info("=" * 70 + "\n")
        
    except Exception as e:
        logger.error(f"\n[ERROR] Failed to initialize services: {e}")
        logger.error("   The application may not function correctly")
        raise e
    
    yield
    
    # Shutdown
    logger.info("[*] Shutting down Emotion-Aware Social Media Platform...")


def create_app() -> FastAPI:
    """Create and configure the FastAPI application"""
    
    # Ensure directories exist
    config.ensure_directories()
    
    # Create FastAPI app with lifespan
    app = FastAPI(
        title=config.API_TITLE,
        description=config.API_DESCRIPTION,
        version=config.API_VERSION,
        docs_url="/docs",
        redoc_url="/redoc",
        openapi_url="/openapi.json",
        lifespan=lifespan
    )
    
    # Add CORS middleware
    app.add_middleware(
        CORSMiddleware,
        allow_origins=config.ALLOWED_ORIGINS,
        allow_credentials=True,
        allow_methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"],
        allow_headers=["*"],
    )
    
    # Include API routes
    app.include_router(router)
    
    # Mount Image API on /image-api
    app.mount("/image-api", image_app)
    
    # Root endpoint
    @app.get("/")
    async def root():
        """API root endpoint"""
        return {
            "name": "Emotion-Aware Social Media Platform API",
            "version": "1.0.0",
            "status": "operational",
            "description": "An ethical and emotionally intelligent social media platform using responsible AI",
            "features": [
                "Real-time emotion detection (text & image)",
                "Multi-modal content analysis",
                "Context-aware reaction filtering",
                "Proactive ethical content filtering",
                "Explainable AI support"
            ],
            "docs_url": "/docs",
            "health_url": "/api/v1/health",
            "research_project": "25-26J-137",
            "author": "D S Jayawardena"
        }
    
    return app


# Create application instance
app = create_app()


if __name__ == "__main__":
    import uvicorn
    
    uvicorn.run(
        "main:app",
        host=config.HOST,
        port=config.PORT,
        reload=config.DEBUG,
        log_level=config.LOG_LEVEL.lower()
    )

