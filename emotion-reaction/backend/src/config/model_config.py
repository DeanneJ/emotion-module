"""
Compatibility stub for src.config.model_config - provides config for legacy model loading
Matches the original training project structure.
"""

class ModelConfig:
    """Model configuration matching the original training project."""
    
    def __init__(
        self,
        backbone: str = "resnet50",
        num_classes: int = 8,
        pretrained: bool = True,
        dropout: float = 0.1,
        multi_label: bool = True,
        image_size: int = 224,
        emotions: list = None,
        mean: tuple = (0.485, 0.456, 0.406),
        std: tuple = (0.229, 0.224, 0.225),
    ):
        self.backbone = backbone
        self.num_classes = num_classes
        self.pretrained = pretrained
        self.dropout = dropout
        self.multi_label = multi_label
        self.image_size = image_size
        self.emotions = emotions or ['happy', 'sad', 'angry', 'fear', 'surprise', 'disgust', 'neutral', 'other']
        self.mean = mean
        self.std = std


# Default config instance
model_config = ModelConfig()
